<?php
namespace Grav\Plugin\TNTSearch;

use Grav\Common\Grav;
use Grav\Common\Twig\Twig;

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

        $default_process = true;

        $results = [];

        foreach ($collection as $page) {
            $counter++;

            $process = $default_process;
            $header = $page->header();
            if (isset($header->tntsearch['process'])) {
                $process = $header->tntsearch['process'];
            }

            // Only process what's configured
            if (!$process) {
                continue;
            }

            $route = $page->route();

            try {
                $results[] = [
                    'id'      => $route,
                    'name'    => $page->title(),
                    'content' => GravTNTSearch::getCleanContent($page)
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

