<?php
namespace Grav\Plugin\TNTSearch;

use Grav\Common\Grav;

class GravConnector extends \PDO
{
    public function __construct()
    {}

    public function query($query)
    {
        $grav = Grav::instance();
        $grav['debugger']->enabled(false);
        $grav['twig']->init();
        /** @var Pages $pages */
        $pages = Grav::instance()['pages'];
        $pages->init();
        $collection = $pages->all();
        //Drop unpublished and unroutable pages
        $collection->published()->routable();

        $results = [];
        foreach ($collection as $page) {
            $counter++;
            $route = $page->route();
            try {
                $results[] = [
                    'id'      => $route,
                    'name'    => $page->title(),
                    'content' => strip_tags($page->content())
                ];
                echo("Added $counter $route\n");
            } catch (\Exception $e) {
                echo("Skipped $counter $route\n");
                continue;
            }
        }

        return new GravResultObject($results);
    }
}

