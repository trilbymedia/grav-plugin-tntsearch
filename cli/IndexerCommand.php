<?php
namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Console\ConsoleCommand;
use Grav\Plugin\TNTSearchPlugin;
use Symfony\Component\Console\Input\InputOption;

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
            ->addOption("alt", null, InputOption::VALUE_NONE, 'alternative output')
            ->setDescription("TNTSearch Indexer")
            ->setHelp('The <info>index command</info> re-indexes the search engine');
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        $alt_output = $this->input->getOption('alt') ? true : false;

        if ($alt_output) {
            $this->doIndex($alt_output);
        } else {
            $this->output->writeln('');
            $this->output->writeln('<magenta>Re-indexing</magenta>');
            $this->output->writeln('');
            $start = microtime(true);
            $this->doIndex($alt_output);
            $end =  number_format(microtime(true) - $start,1);
            $this->output->writeln('');
            $this->output->writeln('Indexed in ' . $end . 's');
        }
    }

    private function doIndex($alt_output = false)
    {
        error_reporting(1);

        $grav = Grav::instance();
        $grav->fireEvent('onPluginsInitialized');
        $grav->fireEvent('onThemeInitialized');

        list($status, $msg, $output) = TNTSearchPlugin::indexJob();

        $this->output->write($output);
        $this->output->writeln('');
    }
}

