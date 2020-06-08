<?php
namespace Grav\Plugin\Console;

use Grav\Console\ConsoleCommand;
use Grav\Plugin\TNTSearchPlugin;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class IndexerCommand
 *
 * @package Grav\Plugin\Console
 */
class TNTSearchQueryCommand extends ConsoleCommand
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
            ->setName('query')
            ->setDescription('TNTSearch Query')
            ->addArgument(
                'query',
                InputArgument::REQUIRED,
                'The search query you wish to use to test the database'
            )
            ->addOption(
                'language',
                'l',
                InputOption::VALUE_OPTIONAL,
                'optional language to search against (multi-language sites only)'
            )
            ->setHelp('The <info>query command</info> allows you to test the search engine')
        ;
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        $this->setLanguage($this->input->getOption('language'));
        $this->initializePages();

        $this->doQuery();
        $this->output->writeln('');
    }

    private function doQuery()
    {
        $gtnt = TNTSearchPlugin::getSearchObjectType(['json' => true]);
        print_r($gtnt->search($this->input->getArgument('query')));
    }
}

