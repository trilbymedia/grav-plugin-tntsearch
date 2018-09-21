<?php
namespace Grav\Plugin\TNTSearch;

use Grav\Common\Grav;
use Grav\Common\Page\Collection;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;
use Symfony\Component\Yaml\Yaml;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use TeamTNT\TNTSearch\TNTSearch;

class GravTNTSearch
{
    public $tnt;
    protected $options;
    protected $bool_characters = ['-', '(', ')', 'or'];
    protected $index = 'grav.index';

    public function __construct($options = [])
    {
        $search_type = Grav::instance()['config']->get('plugins.tntsearch.search_type', 'auto');
        $stemmer = Grav::instance()['config']->get('plugins.tntsearch.stemmer', 'default');
        $limit = Grav::instance()['config']->get('plugins.tntsearch.limit', 20);
        $snippet = Grav::instance()['config']->get('plugins.tntsearch.snippet', 300);
        $data_path = Grav::instance()['locator']->findResource('user://data', true) . '/tntsearch';


        if (!file_exists($data_path)) {
            mkdir($data_path);
        }

        $defaults = [
            'json' => false,
            'search_type' => $search_type,
            'stemmer' => $stemmer,
            'limit' => $limit,
            'as_you_type' => true,
            'snippet' => $snippet,
            'phrases' => true,
        ];

        $this->options = array_merge($defaults, $options);
        $this->tnt = new TNTSearch();
        $this->tnt->loadConfig([
            "storage"   => $data_path,
            "driver"    => 'sqlite',
        ]);
    }

    public function search($query) {
        $uri = Grav::instance()['uri'];
        $type = $uri->query('search_type');
        $this->tnt->selectIndex($this->index);
        $this->tnt->asYouType = $this->options['as_you_type'];

        if (isset($this->options['fuzzy']) && $this->options['fuzzy']) {
            $this->tnt->fuzziness = true;
        }

        $limit = intval($this->options['limit']);
        $type = isset($type) ? $type : $this->options['search_type'];

        $multiword = null;
        if (isset($this->options['phrases']) && $this->options['phrases']) {
            if (strlen($query) > 2) {
                if ($query[0] === "\"" && $query[strlen($query) - 1] === "\"") {
                    $multiword = substr($query, 1, strlen($query) - 2);
                    $type = 'basic';
                    $query = $multiword;
                }
            }
        }


        switch ($type) {
            case 'basic':
                $results = $this->tnt->search($query, $limit, $multiword);
                break;
            case 'boolean':
                $results = $this->tnt->searchBoolean($query, $limit);
                break;
            case 'default':
            case 'auto':
            default:
                $guess = 'search';
                foreach ($this->bool_characters as $char) {
                    if (strpos($query, $char) !== false) {
                        $guess = 'searchBoolean';
                        break;
                    }
                }

                $results = $this->tnt->$guess($query, $limit);
        }

        return $this->processResults($results, $query);
    }

    protected function processResults($res, $query)
    {
        $counter = 0;
        $data = new \stdClass();
        $data->number_of_hits = isset($res['hits']) ? $res['hits'] : 0;
        $data->execution_time = $res['execution_time'];
        $pages = Grav::instance()['pages'];

        foreach ($res['ids'] as $path) {

            if ($counter++ > $this->options['limit']) {
                break;
            }

            $page = $pages->dispatch($path);

            if ($page) {
                Grav::instance()->fireEvent('onTNTSearchQuery', new Event(['page' => $page, 'query' => $query, 'options' => $this->options, 'fields' => $data, 'gtnt' => $this]));
            }
        }

        if ($this->options['json']) {
            return json_encode($data, JSON_PRETTY_PRINT);
        } else {
            return $data;
        }
    }

    public static function getCleanContent($page)
    {
        $twig = Grav::instance()['twig'];
        $header = $page->header();

        if (isset($header->tntsearch['template'])) {
            $processed_page = $twig->processTemplate($header->tntsearch['template'] . '.html.twig', ['page' => $page]);
            $content =$processed_page;
        } else {
            $content = $page->content();
        }

        $content = preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", strip_tags($content)));

        return $content;
    }

    public function createIndex()
    {
        $this->tnt->setDatabaseHandle(new GravConnector);
        $indexer = $this->tnt->createIndex($this->index);

        // Set the stemmer language if set
        if ($this->options['stemmer'] != 'default') {
            $indexer->setLanguage($this->options['stemmer']);
        }

        $indexer->run();
    }

    public function selectIndex()
    {
        $this->tnt->selectIndex($this->index);
    }

    public function deleteIndex($obj)
    {
        if ($obj instanceof Page) {
            $page = $obj;
        } else {
            return;
        }

        $this->tnt->setDatabaseHandle(new GravConnector);

        try {
            $this->tnt->selectIndex($this->index);
        } catch (IndexNotFoundException $e) {
            return;
        }

        $indexer = $this->tnt->getIndex();

        // Delete existing if it exists
        $indexer->delete($page->route());
    }

    public function updateIndex($obj)
    {
        if ($obj instanceof Page) {
            $page = $obj;
        } else {
            return;
        }

        $this->tnt->setDatabaseHandle(new GravConnector);

        try {
            $this->tnt->selectIndex($this->index);
        } catch (IndexNotFoundException $e) {
            return;
        }

        $indexer = $this->tnt->getIndex();

        // Delete existing if it exists
        $indexer->delete($page->route());

        $filter = $config = Grav::instance()['config']->get('plugins.tntsearch.filter');
        if ($filter && array_key_exists('items', $filter)) {

            if (is_string($filter['items'])) {
                $filter['items'] = Yaml::parse($filter['items']);
            }

            $apage = new Page;
            /** @var Collection $collection */
            $collection = $apage->collection($filter, false);

            if (array_key_exists($page->path(), $collection->toArray())) {
                $fields = GravTNTSearch::indexPageData($page);
                $document = (array) $fields;

                // Insert document
                $indexer->insert($document);
            }
        }
    }

    public function indexPageData($page)
    {
        $header = (array) $page->header();
        $redirect = (bool) $page->redirect();

        if ($redirect || (isset($header['tntsearch']['index']) && $header['tntsearch']['index'] === false )) {
            throw new \RuntimeException('redirect only...');
        }

        $fields = new \stdClass();
        $fields->id = $page->route();
        $fields->name = $page->title();
        $fields->content = $this->getCleanContent($page);

        Grav::instance()->fireEvent('onTNTSearchIndex', new Event(['page' => $page, 'fields' => $fields]));

        return $fields;
    }

}
