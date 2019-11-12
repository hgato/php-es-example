<?php

namespace app\modules\utils\controllers\ElasticSearch;

interface SearchResultInterface
{
    public static function create($resultsArray, $searchField);

    public function exclude(array $exclude);

    public function getRawResults();

    public function getIds();
}