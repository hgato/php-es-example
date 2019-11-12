<?php

namespace app\modules\utils\controllers\ElasticSearch\Sorting;

class NearestSorting extends AbstractSorting
{
    protected $indexName = 'parties_location';
    protected $idPrefix = null;
    protected $itemId = 'party_id';

    protected function formSortTemplate()
    {
        return [
            [
                "_geo_distance" => [
                    "location" => [
                        "lat" => $this->sortParams['lat'],
                        "lon" => $this->sortParams['lon']
                    ],
                    "order" => "asc",
                    "unit" => "km",
                    "distance_type" => "plane"
                ]
            ]
        ];
    }
}