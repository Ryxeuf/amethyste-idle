<?php

namespace App\SearchEngine;

use Typesense\Collection;

interface SearchEngineInterface
{
    public function getObject(string $id): array;
    public function find(array $query): array;
    public function getCollection(): Collection;
}