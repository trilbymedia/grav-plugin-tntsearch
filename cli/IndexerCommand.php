<?php
namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Console\ConsoleCommand;
use Grav\Plugin\TNTSearch\GravConnector;
use TeamTNT\TNTSearch\TNTSearch;

/**
 * Class IndexerCommand
 *
 * @package Grav\Plugin\Console
 */
class IndexerCommand extends ConsoleCommand
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

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName("index")
            ->setDescription("TNTSearch Indexer")
            ->setHelp('The <info>index command</info> re-indexes the search engine');
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {

        $this->output->writeln('');
        $this->output->writeln('<magenta>Re-indexing Search</magenta>');
        $this->output->writeln('');

        $this->doIndex();
        $this->output->writeln('Done.');
    }

    private function doIndex()
    {
        include __DIR__.'/../vendor/autoload.php';
        error_reporting(1);

        $data_path = Grav::instance()['locator']->findResource('user://data', true).'/tnt-search';

        if (!file_exists($data_path)) {
            mkdir($data_path);
        }

        $tnt = new TNTSearch;

        $tnt->loadConfig([
            'driver'  => 'sqlite',
            'storage' => $data_path
        ]);
        $tnt->setDatabaseHandle(new GravConnector);
        $indexer = $tnt->createIndex('grav.index');
        $indexer->run();

    }
}

