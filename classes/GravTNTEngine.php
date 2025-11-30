<?php

namespace Grav\Plugin\TNTSearch;

use Grav\Plugin\TNTSearch\GravConnector;
use TeamTNT\TNTSearch\Engines\SqliteEngine;
use TeamTNT\TNTSearch\Support\Collection;
use PDO;

class GravTNTEngine extends SqliteEngine
{
    /**
     * @param string $indexName
     * @return SqliteEngine
     */
    #[\Override]
    public function createIndex(string $indexName): SqliteEngine
    {
        $this->setDatabaseHandle(new GravConnector());
        $engine = parent::createIndex($indexName);

        // Lookup table for grav data
        $this->index->exec("CREATE TABLE IF NOT EXISTS grav (
            id INTEGER PRIMARY KEY,
            route TEXT)");

        return $engine;
    }

    /**
     * @param Collection $row
     * @return void
     */
    #[\Override]
    public function processDocument(Collection $row): void
    {
        $this->saveGravRoute($row->get('route'));

        $rowArr = $row->toArray();
        $gravId = $this->getLastGravId();
        $rowArr[$this->getPrimaryKey()] = $gravId;

        parent::processDocument(new Collection($rowArr));
    }

    /**
     * @param int $documentId
     * @return void
     */
    #[\Override]
    public function delete(int $documentId): void
    {
        parent::delete($documentId);
        $this->prepareAndExecuteStatement('DELETE FROM grav WHERE id = :documentId;', [
            ['key' => ':documentId', 'value' => $documentId],
        ]);
    }

    /**
     * @param string $route
     * @return void
     */
    public function saveGravRoute(string $route): void
    {
        $insert = 'INSERT INTO grav (route) VALUES (:route)';
        $stmt = $this->index->prepare($insert);
        $stmt->bindValue(':route', $route);
        $stmt->execute();
    }

    /**
     * @return int|null
     */
    public function getLastGravId(): ?int
    {
        $query = 'SELECT MAX(id) FROM grav';
        $stmt = $this->index->prepare($query);
        $stmt->execute();

        if ($maxId = $stmt->fetchColumn()) {
            return $maxId ;
        }

        return null;
    }

    /**
     * @param array<int> $ids
     * @return array<string>|array{}
     */
    public function getGravRoutesByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = rtrim(str_repeat('?,', count($ids)), ',');
        $query = "SELECT route FROM grav WHERE id IN ($placeholders)";
        $stmt = $this->index->prepare($query);
        $stmt->execute($ids);

        if ($routes = $stmt->fetchAll(PDO::FETCH_COLUMN)) {
            return $routes;
        }

        return [];
    }

    /**
     * @param string $route
     * @return int|null
     */
    public function getGravRouteId(string $route): ?int
    {
        $query = "SELECT id FROM grav WHERE route = :route";
        $stmt = $this->index->prepare($query);
        $stmt->bindValue(':route', $route);
        $stmt->execute();

        if ($id = $stmt->fetchColumn()) {
            return $id;
        }

        return null;
    }
}
