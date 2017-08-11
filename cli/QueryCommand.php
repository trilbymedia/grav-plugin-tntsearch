<?php
namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Console\ConsoleCommand;
use Grav\Plugin\TNTSearch\GravIndexer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use TeamTNT\TNTSearch\TNTSearch;

/**
 * Class IndexerCommand
 *
 * @package Grav\Plugin\Console
 */
class QueryCommand extends ConsoleCommand
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $colors = [
        'DEBUG'     => 'green',
        'INFO'      => 'cyan',
        'NOTICE'    => 'yellow',
        'WARNING'   => 'yellow',
        'ERROR'     => 'red',
        'CRITICAL'  => 'red',
        'ALERT'     => 'red',
        'EMERGENCY' => 'magenta'
    ];

    protected $tnt;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName("query")
            ->setDescription("TNTSearch Query")
            ->addArgument(
                'query',
                InputArgument::REQUIRED,
                'The search query you wish to use to test the database'
            )
            ->setHelp('The <info>query command</info> allows you to test the search engine')
        ;
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        $this->doQuery();
        $this->output->writeln('Done.');
    }

    private function doQuery()
    {
        include __DIR__ . '/../vendor/autoload.php';

        $data_path = Grav::instance()['locator']->findResource('user://data', true) . '/tnt-search';

        if (!file_exists($data_path)) {
            echo "Must index first...";
        }

        $this->tnt = new TNTSearch();

        $this->tnt->loadConfig([
            "storage"   => $data_path,
            "driver"    => 'grav',
        ]);

        $this->tnt->selectIndex('grav');
        $this->tnt->asYouType = true;

        $query = $this->input->getArgument('query');

        $results = $this->tnt->search($query, 10);

        print_r($this->processResults($results, $query));

    }

    protected function processResults($res, $query)
    {
        $data = ['hits' => [], 'nbHits' => count($res)];

        $grav = Grav::instance();
        $grav['debugger']->enabled(false);
        $grav['twig']->init();

        /** @var Pages $pages */
        $pages = Grav::instance()['pages'];
        $pages->init();

        foreach ($res as $result) {
            $page = $pages->dispatch($result['path']);

            if ($page) {
                $file = strip_tags($page->content());
                $title = $page->title();

                $relevant = $this->tnt->snippet($query, strip_tags($file));

                $data['hits'][] = [
                    'link' => $result['path'],
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

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

