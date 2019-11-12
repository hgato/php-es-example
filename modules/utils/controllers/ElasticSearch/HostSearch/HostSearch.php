<?php

namespace app\modules\utils\controllers\ElasticSearch\HostSearch;

use app\modules\utils\controllers\ElasticSearch\SearchConstructor\AbstractSearchConstructor;

class HostSearch extends AbstractSearchConstructor
{
    protected $parameterTypes = [
        'query' => 'applyQuery',
    ];
    protected $multiSearchClass = HostMultiSearch::class;

    public function applyQuery(HostMultiSearch $searchObject, $queryString)
    {
        $searchObject->query(['user_id', 'about', 'firstname', 'lastname', 'fullname', ], $queryString);
        return $searchObject;
    }

    protected function gatherData($searchResult)
    {
        $results = [];
        foreach ($searchResult as $userid) {
            $query = "
        SELECT u.firstname, u.lastname,
        (SELECT about FROM host_profiles WHERE host_profiles.userid = u.userid LIMIT  1) as about,
        (SELECT experience FROM host_profiles WHERE host_profiles.userid = u.userid LIMIT  1) as experience
        FROM users u
        WHERE u.userid = %i_userid";
            $temp = \DB::queryOneRow($query, [
                'userid' => $userid
            ]);
            $temp['host_name'] = $temp['firstname'] . ' ' . $temp['lastname'];
            $temp['host_id'] = $userid;
            unset($temp['firstname']);
            unset($temp['lastname']);

            $results []= $temp;
        }
        return $results;
    }
}