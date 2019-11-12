<?php

namespace app\modules\utils\controllers\ElasticSearch\PartySearch;

use app\modules\utils\controllers\ElasticSearch\AbstractMultiSearch;

class PartyMultiSearch extends AbstractMultiSearch
{
    protected $allowedSorting = [
        'most_popular',
        'newest',
        'nearest',
        'price_asc',
        'price_desc',
    ];
    protected $searchField = 'party_id';
    protected $indexMap = [
        'parties_accessibility' => ['party_id', 'max_people', 'open', ],
        'parties_duration' => ['party_id', 'start_time', 'end_time', ],
        'parties_general' => ['party_id', 'featured', 'price', ],
        'parties_location' => ['party_id', 'latitude', 'longitude', ],
        'parties_text' => ['party_id', 'name', 'description'],
        'parties_ratings' => ['party_id', 'ratings_average', 'ratings_count'],
        'party_sessions' => ['session_time', 'free_spots'],
    ];
}