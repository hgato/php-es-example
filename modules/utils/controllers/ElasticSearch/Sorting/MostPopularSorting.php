<?php

namespace app\modules\utils\controllers\ElasticSearch\Sorting;

class MostPopularSorting extends AbstractSorting
{
    protected $indexName = 'parties_ratings';
    protected $idPrefix = null;
    protected $itemId = 'party_id';

    protected function formSortTemplate()
    {
        return [
            [
                'ratings_average' => 'desc',
                'ratings_count' => 'desc'
            ]
        ];
    }
}