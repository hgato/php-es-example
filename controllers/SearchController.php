<?php

namespace app\controllers;

use app\modules\utils\controllers\ElasticSearch\HostSearch\HostSearch;
use app\modules\utils\controllers\ElasticSearch\PartySearch\PartySearch;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

class SearchController extends Controller
{

    function actionSearchGeneric()
    {
        [$exclude, $limit, $sort, $params, ] = $this->getBasicSearchPost();

        $partiesExclude = ArrayHelper::getValue($exclude, 'parties', []);
        $hostsExclude = ArrayHelper::getValue($exclude, 'hosts', []);
        $partiesLimit = ArrayHelper::getValue($limit, 'parties', null);
        $hostsLimit = ArrayHelper::getValue($limit, 'hosts', null);

        $data = [
            'results' => [
                'parties' => static::searchParties($params, $partiesExclude, $partiesLimit),
                'hosts' => static::searchHosts($params, $hostsExclude, $hostsLimit),
            ]
        ];
        return $this->asJson($data);
    }

    function actionSearchParties()
    {
        [$exclude, $limit, $sort, $params, ] = $this->getBasicSearchPost();
        $data = [
            'results' => [
                'parties' => static::searchParties($params, $exclude, $limit, $sort),
            ]
        ];
        return $this->asJson($data);
    }

    function actionSearchHosts ()
    {
        [$exclude, $limit, $sort, $params, ] = $this->getBasicSearchPost();

        $data = [
            'results' => [
                'hosts' => static::searchHosts($params, $exclude, $limit),
            ]
        ];
        return $this->asJson($data);
    }

    function getBasicSearchPost()
    {
        $request = YII::$app->request;
        $exclude = $request->post('exclude', null);
        $limit = $request->post('limit', null);
        $sort = $request->post('sort', null);
        $params = $request->post('params', null);

        return [$exclude, $limit, $sort, $params, ];
    }

    public static function searchParties(array $params, array $exclude, ?int $limit, ?array $sort = null)
    {
        $search =  PartySearch::find()
            ->setParams($params);
        if ($exclude) {
            $search->exclude($exclude);
        }
        if ($limit) {
            $search->limit($limit);
        }
        if ($sort) {
            $search->sort($sort);
        }
        $parties = $search->search();
        return $parties;
    }

    public static function searchHosts(array $params, array $exclude, ?int $limit)
    {
        $search = HostSearch::find()
            ->setParams($params);
        if ($exclude) {
            $search->exclude($exclude);
        }
        if ($limit) {
            $search->limit($limit);
        }
        $hosts = $search->search();
        return $hosts;
    }
}
