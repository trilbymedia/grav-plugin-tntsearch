<?php
namespace Grav\Plugin\TNTSearch;

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
        $counter = 0;

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
            } catch (\Exception $e) {
                $this->info("Skipped $counter $route");
                continue;
            }
        }

        return new GravResultObject($results);
    }
}