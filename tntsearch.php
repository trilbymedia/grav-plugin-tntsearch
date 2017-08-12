<?php
namespace Grav\Plugin;

use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use Grav\Plugin\TNTSearch\GravTNTSearch;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class TNTSearchPlugin
 * @package Grav\Plugin
 */
class TNTSearchPlugin extends Plugin
{
    protected $results = [];
    protected $query;

    protected $query_route;
    protected $search_route;
    protected $current_route;

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    public function onPluginsInitialized()
    {
        include __DIR__.'/vendor/autoload.php';

        if ($this->isAdmin()) {
            return;
        }

        $this->enable([
            'onPagesInitialized' => ['onPagesInitialized', 1000],
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
            'onTwigLoader' => ['onTwigLoader', 0],
        ]);
    }


    public function onPagesInitialized()
    {
        /** @var Uri $uri */
        $uri = $this->grav['uri'];

        $this->current_route = $uri->path();
        $this->query_route = $this->config->get('plugins.tntsearch.query_route');
        $this->search_route = $this->config->get('plugins.tntsearch.search_route');
        $this->query = $uri->param('query') ?: $uri->query('query');

        $pages = $this->grav['pages'];
        $page = $pages->dispatch($this->current_route);

        if (!$page) {
            $page = new Page;

            if ($this->query_route == $this->current_route) {
                $page->init(new \SplFileInfo(__DIR__ . "/pages/tntquery.md"));
            } elseif ($this->search_route == $this->current_route) {
                $page->init(new \SplFileInfo(__DIR__ . "/pages/search.md"));
            }

            $page->slug(basename($this->current_route));
            $pages->addPage($page, $this->current_route);
        }

        $this->config->set('plugins.tntsearch', $this->mergeConfig($page));

        $tnt = new GravTNTSearch(['json' => false]);
        $this->results = $tnt->search($this->query);
    }

    public function onTwigLoader()
    {
        $this->grav['twig']->addPath(__DIR__ . '/templates');
    }
    
    public function onTwigSiteVariables()
    {
        $twig = $this->grav['twig'];

        if ($this->query) {
            $twig->twig_vars['query'] = $this->query;
            $twig->twig_vars['tntsearch_results'] = $this->results;
        }
    }


}
