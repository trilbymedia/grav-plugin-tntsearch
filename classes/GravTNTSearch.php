<?php
namespace Grav\Plugin\TNTSearch;

use Grav\Common\Grav;
use RocketTheme\Toolbox\Event\Event;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use TeamTNT\TNTSearch\TNTSearch;

class GravTNTSearch
{
    public $tnt;
    protected $options;
    protected $bool_characters = ['-', '(', ')', 'or'];

    public function __construct($options = [])
    {
        $search_type = Grav::instance()['config']->get('plugins.tntsearch.search_type');
        $stemmer = Grav::instance()['config']->get('plugins.tntsearch.stemmer');
        $data_path = Grav::instance()['locator']->findResource('user://data', true) . '/tntsearch';

        if (!file_exists($data_path)) {
            mkdir($data_path);
        }

        $defaults = [
            'json' => false,
            'search_type' => $search_type,
            'stemmer' => $stemmer,
            'limit' => 20,
            'as_you_type' => true,
            'snippet' => 300,
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
        $this->tnt->selectIndex('grav.index');
        $this->tnt->asYouType = $this->options['as_you_type'];

        if (isset($this->options['fuzzy']) && $this->options['fuzzy']) {
            $this->tnt->fuzziness = true;
        }

        $limit = intval($this->options['limit']);
        $type = isset($type) ? $type : $this->options['search_type'];

        switch ($type) {
            case 'basic':
                $results = $this->tnt->search($query, $limit);
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
        $indexer = $this->tnt->createIndex('grav.index');

        // Set the stemmer language if set
        if ($this->options['stemmer'] != 'default') {
            $indexer->setLanguage($this->options['stemmer']);
        }

        $indexer->run();
    }

    public function deleteIndex($page)
    {
        $this->tnt->setDatabaseHandle(new GravConnector);

        try {
            $this->tnt->selectIndex('grav.index');
        } catch (IndexNotFoundException $e) {
            return;
        }

        $indexer = $this->tnt->getIndex();

        // Delete existing if it exists
        $indexer->delete($page->route());
    }

    public function updateIndex($page)
    {
        $this->tnt->setDatabaseHandle(new GravConnector);

        try {
            $this->tnt->selectIndex('grav.index');
        } catch (IndexNotFoundException $e) {
            return;
        }

        $indexer = $this->tnt->getIndex();

        // Delete existing if it exists
        $indexer->delete($page->route());

        $fields = GravTNTSearch::indexPageData($page);
        $document = (array) $fields;

        // Insert document
        $indexer->insert($document);
    }

    public function indexPageData($page)
    {
        $fields = new \stdClass();
        $fields->id = $page->route();
        $fields->name = $page->title();
        $fields->content = $this->getCleanContent($page);

        Grav::instance()->fireEvent('onTNTSearchIndex', new Event(['page' => $page, 'fields' => $fields]));

        return $fields;
    }

}
