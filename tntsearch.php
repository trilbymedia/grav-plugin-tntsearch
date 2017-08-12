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
            'onPagesInitialized' => ['onPagesInitialized', 0],
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
            'onTwigLoader' => ['onTwigLoader', 0],
        ]);
    }


    public function onPagesInitialized()
    {
        $page = $this->grav['page'];

        if (!$page || $page->name() == 'notfound.md') {
            $page = new Page;
            $page->init(new \SplFileInfo(__DIR__ . '/pages/tntsearch.md'));
            unset($this->grav['page']);
            $this->grav['page'] = $page;
        }

        $this->config->set('plugins.tntsearch', $this->mergeConfig($page));

        /** @var Uri $uri */
        $uri = $this->grav['uri'];
        $this->query = $uri->param('query') ?: $uri->query('query');
        $route = $this->config->get('plugins.tntsearch.route');

        // performance check for route
        if (!($route && $route == $uri->path())) {
            return;
        }

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
