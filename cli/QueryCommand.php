<?php
namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Console\ConsoleCommand;
//use Grav\Plugin\TNTSearch\GravIndexer;
use Grav\Plugin\TNTSearchPlugin;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
        $this->doQuery();
        $this->output->writeln('');
    }

    private function doQuery()
    {
        $grav = Grav::instance();


        // Initialize Plugins
        $grav->fireEvent('onPluginsInitialized');

        // Set Language if one passed in
        $language = $grav['language'];
        if ($language->enabled()) {
            $lang = $this->input->getOption('language');
            if ($lang && $language->validate($lang)) {
                $language->setActive($lang);
            } else {
                $language->setActive($language->getDefault());
            }
        }

        $grav['debugger']->enabled(false);
        $grav['twig']->init();
        $grav['pages']->init();

        $gtnt = TNTSearchPlugin::getSearchObjectType(['json' => true]);
        print_r($gtnt->search($this->input->getArgument('query')));
    }

}

