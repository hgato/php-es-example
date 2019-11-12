<?php

namespace app\modules\utils\controllers\ElasticSearch\SearchConstructor;

use yii\helpers\ArrayHelper;

abstract class AbstractSearchConstructor
{
    protected $params;
    protected $parameterTypes = [];
    protected $multiSearchClass;
    protected $excludeArray = [];
    protected $_limit = null;
    protected $_sort;

    protected abstract function gatherData($searchResult);

    public function __construct()
    {

    }

    public static function find()
    {
        return new static();
    }

    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    public function search()
    {
        $searchObject = $this->multiSearchClass::create();
        $searchObject->exclude($this->excludeArray);

        if (!is_null($this->_limit)) {
            $searchObject->limit($this->_limit);
        }
        if (!is_null($this->_sort)) {
            $searchObject->sort($this->_sort['method'], ArrayHelper::getValue($this->_sort, 'params', []));
        }

        foreach ($this->parameterTypes as $typeName => $methodName) {
            if (array_key_exists($typeName, $this->params)) {
                $searchObject = $this->$methodName($searchObject, $this->params[$typeName]);
            }
        }

        $searchResult = $searchObject->search()->getResult();
        return [
            'results' => $this->gatherData($searchResult),
            'exclude' => array_merge($this->excludeArray, $searchResult)];
    }

    public function exclude(array $toExclude)
    {
        $this->excludeArray = $toExclude;
        return $this;
    }

    public function limit(int $limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    public function sort(array $sort)
    {
        $this->validateSortParams(ArrayHelper::getValue($sort, 'params', []));
        $this->_sort = $sort;
        return $this;
    }

    protected function validateSortParams($params)
    {
        $lat = ArrayHelper::getValue($params, 'lat', null);
        if ($lat !== null) {
            if ($lat > 90 || $lat < -90) {
                throw new \Exception('Wrong lat in sort');
            }
        }
        $lon = ArrayHelper::getValue($params, 'lon', null);
        if ($lon !== null) {
            if ($lon > 180 || $lon < -180) {
                throw new \Exception('Wrong lon in sort');
            }
        }
    }
}