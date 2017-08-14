<?php
namespace Grav\Plugin;

use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use Grav\Plugin\TNTSearch\GravTNTSearch;
use RocketTheme\Toolbox\Event\Event;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;

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
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onTwigLoader' => ['onTwigLoader', 0],
        ];
    }

    public function onPluginsInitialized()
    {
        include __DIR__.'/vendor/autoload.php';

        if ($this->isAdmin()) {
            $this->enable([
                'onTwigSiteVariables' => ['onTwigAdminVariables', 0],
            ]);
            return;
        }

        $this->enable([
            'onPagesInitialized' => ['onPagesInitialized', 1000],
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
        ]);
    }


    public function onPagesInitialized()
    {
        /** @var Uri $uri */
        $uri = $this->grav['uri'];

        $options = [];

        $this->current_route = $uri->path();
        $this->query_route = $this->config->get('plugins.tntsearch.query_route');
        $this->search_route = $this->config->get('plugins.tntsearch.search_route');
        $this->query = $uri->param('q') ?: $uri->query('q');

        $snippet = $this->getFormValue('sl');
        $limit = $this->getFormValue('l');

        if ($snippet) {
            $options['snippet'] = $snippet;
        }
        if ($limit) {
            $options['limit'] = $limit;
        }

        $pages = $this->grav['pages'];
        $page = $pages->dispatch($this->current_route);

        if (!$page) {
            if ($this->query_route && $this->query_route == $this->current_route) {
                $page = new Page;
                $page->init(new \SplFileInfo(__DIR__ . "/pages/tntquery.md"));
            } elseif ($this->search_route && $this->search_route == $this->current_route) {
                $page = new Page;
                $page->init(new \SplFileInfo(__DIR__ . "/pages/search.md"));
            }
            if ($page) {
                $page->slug(basename($this->current_route));
                $pages->addPage($page, $this->current_route);
            }

        }

        $this->config->set('plugins.tntsearch', $this->mergeConfig($page));

        $gtnt = new GravTNTSearch($options);
        $this->results = $gtnt->search($this->query);
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
        $this->grav['assets']->addCss('plugin://tntsearch/assets/tntsearch.css');
        $this->grav['assets']->addJs('plugin://tntsearch/assets/tntsearch.js');
    }

    public function onTwigAdminVariables()
    {
        $twig = $this->grav['twig'];


        $status = true;

        $gtnt= new GravTNTSearch();
        try {
            $gtnt->tnt->selectIndex('grav.index');
        } catch (IndexNotFoundException $e) {
            $status = false;
            $msg = "Index not created";
        }

        if ($status) {
            $msg = $gtnt->tnt->totalDocumentsInCollection() . ' documents indexed';
        }


        $twig->twig_vars['tntsearch_index_status'] = ['status' => $status, 'msg' => $msg];

        $this->grav['assets']->addCss('plugin://tntsearch/assets/admin/tntsearch.css');
    }

    protected function getFormValue($val)
    {
        $uri = $this->grav['uri'];
        return $uri->param($val) ?: $uri->query($val) ?: filter_input(INPUT_POST, $val, FILTER_SANITIZE_ENCODED);;
    }


}
