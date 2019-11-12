<?php

namespace app\modules\utils\controllers\ElasticSearch\HostSearch;

use app\modules\utils\controllers\ElasticSearch\AbstractMultiSearch;

class HostMultiSearch extends AbstractMultiSearch
{
    protected $searchField = 'user_id';
    protected $indexMap = [
        'users' =>  ['user_id', 'about', 'firstname', 'lastname', 'fullname', ],
    ];
}