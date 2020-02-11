<?php
namespace Grav\Plugin\TNTSearch;

use Grav\Common\Config\Config;
use Grav\Common\Grav;
use Grav\Common\Yaml;
use Grav\Common\Page\Page;
use Grav\Plugin\TNTSearchPlugin;

class GravConnector extends \PDO
{
    public function __construct()
    {

    }

    public function getAttribute($attribute): bool
    {
        return false;
    }

    public function query($query)
    {
        $counter = 0;
        $results = [];

        /** @var Config $config */
        $config = Grav::instance()['config'];
        $filter = $config->get('plugins.tntsearch.filter');
        $default_process = $config->get('plugins.tntsearch.index_page_by_default');
        $gtnt = TNTSearchPlugin::getSearchObjectType();


        if ($filter && array_key_exists('items', $filter)) {

            if (is_string($filter['items'])) {
                $filter['items'] = Yaml::parse($filter['items']);
            }

            $page = new Page;
            $collection = $page->collection($filter, false);
        } else {
            $collection = Grav::instance()['pages']->all();
            $collection->published()->routable();
        }

        foreach ($collection as $page) {
            $counter++;
            $process = $default_process;
            $header = $page->header();
            $route = $page->route();

            if (isset($header->tntsearch['process'])) {
                $process = $header->tntsearch['process'];
            }

            // Only process what's configured
            if (!$process) {
                echo("Skipped {$counter} {$route}\n");
                continue;
            }

            try {
                $fields = $gtnt->indexPageData($page);
                $results[] = (array) $fields;
                $display_route = $fields->display_route ?? $route;
                echo("Added   {$counter} {$display_route}\n");
            } catch (\Exception $e) {
                echo("Skipped {$counter} {$route} - {$e->getMessage()}\n");
                continue;
            }
        }

        return new GravResultObject($results);
    }

}

