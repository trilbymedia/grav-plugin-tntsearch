<?php
namespace Grav\Plugin\TNTSearch;

use Grav\Common\Grav;

class GravConnector extends \PDO
{
    public function __construct()
    {

    }

    public function query($query)
    {
        $counter = 0;
        $collection = Grav::instance()['pages']->all();
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

