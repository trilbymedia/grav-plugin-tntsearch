<?php
namespace Grav\Plugin\TNTSearch;

use Grav\Common\Grav;
use Grav\Common\Page\Pages;
use TeamTNT\TNTSearch\Indexer\TNTIndexer;
use TeamTNT\TNTSearch\Support\Collection;

class GravIndexer extends TNTIndexer
{

    public function createConnector(array $config)
    {
        if (!isset($config['driver'])) {
            throw new Exception('A driver must be specified.');
        }
        return new GravConnector();
    }

    public function run()
    {
        return $this->readDocumentsFromGrav();
    }

    protected function readDocumentsFromGrav()
    {
        $this->index->exec("CREATE TABLE IF NOT EXISTS filemap (
					id INTEGER PRIMARY KEY,
					path TEXT)");

        $this->index->beginTransaction();
        $counter = 0;

        $grav = Grav::instance();
        $grav['debugger']->enabled(false);
        $grav['twig']->init();

        /** @var Pages $pages */
        $pages = Grav::instance()['pages'];
        $pages->init();

        $collection = $pages->all();

        //Drop unpublished and unroutable pages
        $collection->published()->routable();

        foreach ($collection as $page) {
            $counter++;

            $route = $page->route();

            try {
                $file = [
                    'id' => $counter,
                    'name' => $page->title(),
                    'content' => strip_tags($page->content())
                ];
            } catch (\Exception $e) {
                $this->info("Skipped $counter $route");
                continue;
            }

            $this->processDocument(new Collection($file));
            $this->index->exec("INSERT INTO filemap ( 'id', 'path') values ( $counter, '$route')");
            $this->info("Processed $counter $route");
        }


        $this->index->commit();
//        $this->index->exec("INSERT INTO info ( 'key', 'value') values ( 'total_documents', $counter)");
        $this->index->exec("UPDATE info SET value = $counter WHERE key = 'total_documents'");
        $this->index->exec("INSERT INTO info ( 'key', 'value') values ( 'driver', 'filesystem')");
        $this->info("Total rows $counter");
        $this->info("Index created: {$this->config['storage']}");
    }

}
