<?php

namespace App\SearchEngine;

use Typesense\Collection;

abstract class SearchEngine implements SearchEngineInterface
{
    protected const CELL_INDEX_NAME = 'cells';
    protected const CELL_INDEX_CONFIGURATION = [
        'name' => self::CELL_INDEX_NAME,
        "enable_nested_fields" => true,
        'fields' => [
            ['name' => '.*', 'type' => 'auto'],
        ],
    ];

    public function __construct(
        protected readonly TypeSenseClient $client,
    ) {
    }

    public function upsert(array $data): void
    {
        $this->getCollection()->documents->import($data, [
            'action' => 'upsert',
        ]);
    }
}
