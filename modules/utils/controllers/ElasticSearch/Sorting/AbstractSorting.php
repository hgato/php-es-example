<?php

namespace app\modules\utils\controllers\ElasticSearch\Sorting;

abstract class AbstractSorting
{
    protected $client;
    protected $actualIndices;

    protected $indexName = '';
    protected $idPrefix;
    protected $itemId;

    protected $sortParams = [];

    abstract protected function formSortTemplate();

    public function sort($toSort, $sortParams = null)
    {
        $this->sortParams = $sortParams;
        $ids = $this->getIds($toSort);
        $params = $this->getTemplate($ids);
        $result = $this->search($params);
        $hits = $result['hits']['hits'];

        $resultIds = [];
        foreach ($hits as $hit) {
            $resultIds []= $hit['_source'][$this->itemId];
        }
        return $resultIds;
    }

    public static function create($client, $actualIndices)
    {
        return new static($client, $actualIndices);
    }

    protected function __construct($client, $actualIndices)
    {
        $this->client = $client;
        $this->actualIndices = $actualIndices;
    }

    protected function getTemplate($ids)
    {
        return [
            'index' => $this->actualIndices[$this->indexName],
            'body' => [
                "query" => [
                    'ids' => [
                        'values' => $ids
                    ]
                ],
                "sort" => $this->formSortTemplate(),
                'size' => 10000,
            ]
        ];
    }

    protected function search($params)
    {
        return $this->client->search($params);
    }

    protected function getIds($toSort)
    {
        $idPrefix = $this->idPrefix ?: $this->indexName . '_';
        $ids = [];
        foreach ($toSort as $item) {
            $ids []= $idPrefix . $item;
        }
        return $ids;
    }
}


