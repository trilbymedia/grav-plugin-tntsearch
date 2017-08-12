<?php
namespace Grav\Plugin\TNTSearch;

use Grav\Common\Grav;
use Grav\Plugin\TNTSearch\GravConnector;
use TeamTNT\TNTSearch\TNTSearch;

class GravTNTSearch
{
    protected $tnt;
    protected $options;

    public function __construct($options = null)
    {
        $defaults = ['json' => true, 'fuzzy' => false];
        
        $this->tnt = new TNTSearch();

        $data_path = Grav::instance()['locator']->findResource('user://data', true) . '/tntsearch';

        // merge any passed-in options
        if ($options) {
            $this->options = array_merge($defaults, $options);
        } else {
            $this->options = $defaults;
        }

        if (!file_exists($data_path)) {
            mkdir($data_path);
        }

        $this->tnt->loadConfig([
            "storage"   => $data_path,
            "driver"    => 'sqlite',
        ]);

    }

    public function search($query, $limit = 10) {

        $this->tnt->selectIndex('grav.index');
        $this->tnt->asYouType = true;

        if ($this->options['fuzzy']) {
            $results = $this->tnt->fuzzySearch($query, $limit);
        } else {
            $results = $this->tnt->search($query, $limit);
        }

        return $this->processResults($results, $query);
    }

    protected function processResults($res, $query)
    {
        $data = ['hits' => [], 'nbHits' => $res['hits'], 'executionTime' => $res['execution_time']];

        $grav = Grav::instance();
        $grav['twig']->init();

        /** @var Pages $pages */
        $pages = Grav::instance()['pages'];
        $pages->init();

        foreach ($res['ids'] as $path) {
            $page = $pages->dispatch($path);

            if ($page) {
                $file = strip_tags($page->content());
                $title = $page->title();

                $relevant = $this->tnt->snippet($query, strip_tags($file));

                $data['hits'][] = [
                    'link' => $path,
                    '_highlightResult' => [
                        'h1' => [
                            'value' => $this->tnt->highlight($title, $query),
                        ],
                        'content' => [
                            'value' => $this->tnt->highlight($relevant, $query),
                        ]
                    ]
                ];
            }


        }
        if ($this->options['json']) {
            return json_encode($data, JSON_PRETTY_PRINT);
        } else {
            return $data;
        }
    }

    public function indexGravPages()
    {
        $this->tnt->setDatabaseHandle(new GravConnector);
        $indexer = $this->tnt->createIndex('grav.index');
        $indexer->run();
    }

}