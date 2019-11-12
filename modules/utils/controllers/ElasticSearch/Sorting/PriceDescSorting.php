<?php

namespace app\modules\utils\controllers\ElasticSearch\Sorting;

class PriceDescSorting extends AbstractSorting
{
    protected $indexName = 'parties_general';
    protected $idPrefix = null;
    protected $itemId = 'party_id';

    protected function formSortTemplate()
    {
        return [
            [
                'price' => 'desc',
            ]
        ];
    }
}