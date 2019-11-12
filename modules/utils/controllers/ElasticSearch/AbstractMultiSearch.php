<?php

namespace app\modules\utils\controllers\ElasticSearch;

use app\modules\utils\controllers\ElasticSearch\Sorting\AbstractSorting;
use Elasticsearch\ClientBuilder;
use yii\helpers\StringHelper;

abstract class AbstractMultiSearch
{
    use QueryMethodsTrait;

    protected $sortMethod = null;
    protected $sortParams = null;

    protected $allowedSorting = [];

    protected $sortingClassNamespace = 'app\modules\utils\controllers\ElasticSearch\Sorting\\';

    protected $queryStandardStep = 300;

    protected $limit = 1000;
    protected $offset = 0;

    protected $result;

    protected $excludedFromSearch = [];
    protected $searchClass = SearchResult::class;

    protected $client;
    protected $queriesArrays;

    protected $sourceResult = null;

    protected $indexMap = [];
    protected $realIndexes = [];

    protected $searchField = null;

    public function search()
    {
        $results = [];
        $queryOffset = 0;

        while (true)
        {
            $params = $this->formParams($this->queryStandardStep, $queryOffset);
            $sourceResult = $this->msearch($params);
            try {
                $newResults = $this->formNewResults($sourceResult);
            } catch (\LengthException $e) {
                break;
            }
            $results = $this->mergeOldAndNewResults($results, $newResults);
            $queryOffset += $this->queryStandardStep;
        }

        $searched = $this->searchClass::create($results, $this->searchField)
            ->exclude($this->excludedFromSearch)
            ->getIds();

        if ($this->sortMethod) {
            $searched = $this->applySort($searched);
        }

        $searched = $this->applyLimit($searched, $this->offset, $this->limit);
        $this->result = $searched;

        return $this;
    }

    public function sort(string $method, ?array $params = null)
    {
        if (!in_array($method, $this->allowedSorting)) {
            throw new \Exception('No such sort method');
        }
        $this->sortMethod = $method;
        $this->sortParams = $params;
        return $this;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function exclude(array $excludedFromSearch)
    {
        $this->excludedFromSearch = $excludedFromSearch;
        return $this;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public static function create()
    {
        return new static();
    }

    protected function __construct()
    {
        $this->client = ClientBuilder::create()->setHosts([$_ENV['ELASTICSEARCH_HOST'] . ':'. $_ENV['ELASTICSEARCH_PORT']])->build();
    }

    protected function applySort(array $searched)
    {
        /**
         * @var $className AbstractSorting
         */
        $className = $this->formSortClassFullName();
        $sortObject = $className::create($this->client, $this->realIndexes);
        $searched = $sortObject->sort($searched, $this->sortParams);
        return $searched;
    }

    protected function formSortClassFullName()
    {
        return $this->sortingClassNamespace .
            str_replace(' ', '', ucwords(str_replace('_', ' ', $this->sortMethod))) .
            'Sorting';
    }

    protected function formSingleTemplate()
    {
        return [
            ['index' => null, ],
            [
                'query' => null,
                "from" => 0,
                "size" => $this->queryStandardStep,
            ],
        ];
    }

    protected function formTemplate(?int $limit = null, ?int $offset = null)
    {
        $template = [];
        foreach ($this->queriesArrays as $queriesArray) {
            $tempTemplate = $this->formSingleTemplate();
            $tempTemplate[0]['index'] = $queriesArray['index'];
            $tempTemplate[1]['query'] = $queriesArray['formed_query'];

            if (!is_null($limit)) {
                $tempTemplate[1]['size'] = $limit;
            }
            if (!is_null($offset)) {
                $tempTemplate[1]['from'] = $offset;
            }

            $template = array_merge($template, $tempTemplate);
        }

        return $template;
    }

    protected function applyLimit(array $searched, int $offset, int $limit)
    {
        return array_slice($searched, $offset, $limit);
    }

    protected function formNewResults(array $sourceResult)
    {
        $responsesResults = [];
        $allEmpty = true;
        foreach ($sourceResult['responses'] as $response) {
            $responseResults = [];
            foreach ($response['hits']['hits'] as $entity) {
                $entityid = $entity['_source'][$this->searchField];
                $score = $entity['_score'];
                $responseResults []= [
                    $this->searchField => $entityid,
                    'score' => $score,
                ];
            }
            if (!empty($responseResults)) {
                $allEmpty = false;
            }
            $responsesResults []= $responseResults;
        }

        if ($allEmpty) {
            throw new \LengthException('End of elasticsearch indexes');
        }

        return $responsesResults;
    }

    protected function mergeOldAndNewResults(array $results, array $newResults)
    {
        $merged = [];
        foreach ($newResults as $index => $newResult) {
            $merged []= array_merge(array_key_exists($index, $results) ? $results[$index] : [], $newResult);
        }
        return $merged;
    }

    protected function formParams(?int $limit = null, ?int $offset = null)
    {
        $template = $this->formTemplate($limit, $offset);
        return [
            'body' => $template
        ];
    }

    protected function findIndexByFields(array $fields)
    {
        if (empty($this->realIndexes)) {
            $this->formRealIndices();
        }
        foreach ($this->indexMap as $index => $indexFields) {
            $isTarget = true;
            foreach ($fields as $field) {
                if (!in_array($field, $indexFields)) {
                    $isTarget = false;
                    break;
                }
            }
            if ($isTarget) {
                return $this->realIndexes[$index];
            }
        }
    }

    protected function formRealIndices()
    {
        $indexPairs = [];
        $indices = $this->getAliases();
        $indices = array_keys($indices);
        rsort($indices);
        $latestIndices = [];
        // creating the array of base_index => real_index pairs
        // real_index must be a second real index by date to prevent usage of index under construction
        foreach ($indices as $index) {
            foreach (array_keys($this->indexMap) as $mapItem) {
                if (StringHelper::startsWith($index, $mapItem) && !array_key_exists($mapItem, $indexPairs)) {
                    if (in_array($mapItem, $latestIndices)) {
                        $indexPairs[$mapItem] = $index;
                    } else {
                        $latestIndices []= $mapItem;
                    }
                }
            }
        }
        $this->realIndexes = $indexPairs;
    }

    protected function getAliases()
    {
        return $this->client->indices()->getAliases();
    }

    protected function msearch($params)
    {
        return $this->client->msearch($params);
    }
}