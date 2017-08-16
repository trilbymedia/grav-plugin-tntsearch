<?php
namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Console\ConsoleCommand;
use Grav\Plugin\TNTSearch\GravTNTSearch;

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

        $grav = Grav::instance();
        $grav['debugger']->enabled(false);
        $grav['twig']->init();
        $grav['pages']->init();

        $gtnt = new GravTNTSearch();
        $gtnt->createIndex();

    }
}

