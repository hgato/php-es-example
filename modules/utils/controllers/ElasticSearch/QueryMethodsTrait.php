<?php

namespace app\modules\utils\controllers\ElasticSearch;

trait QueryMethodsTrait
{
    public function query($fields, $queryString, $analyzer = 'english', $defaultOperator = 'AND')
    {
        if (!$queryString) {
            return $this->matchAll();
        }

        $index = $this->findIndexByFields($fields);
        $this->queriesArrays []= [
            'type' => 'query',
            'index' => $index,
            'fields' => $fields,
            'query_string' => $queryString,
            'formed_query' => [
                "bool" => [
                    "should" => [
                        [
                            "query_string" => [
                                'fields' => $fields,
                                "query" => $queryString,
                                'analyzer' => $analyzer,
                                'default_operator' => $defaultOperator
                            ]
                        ],
                        [
                            "query_string" => [
                                'fields' => $fields,
                                "query" => $queryString,
                                'analyzer' => 'standard',
                                'default_operator' => $defaultOperator
                            ]
                        ],
                    ]
                ]
            ]
        ];
        return $this;
    }

    public function exactMatchAnyQuery(array $fields, array $queryStrings)
    {
        $index = $this->findIndexByFields($fields);

        if ((count($queryStrings) == 1) && (count($fields) == 1)) {
            $formedQuery = [
                "term" => [
                    $fields[0] => [
                        "value" => $queryStrings[0],
                        "boost" => 1.0
                    ]
                ]
            ];
        } else {
            $terms = [];
            foreach ($queryStrings as $queryString) {
                foreach ($fields as $field) {
                    $terms [] = [
                        "term" => [
                            $field => [
                                "value" => $queryString,
                                "boost" => 1.0
                            ]
                        ]
                    ];
                }
            }
            $formedQuery = [
                "bool" => [
                    "should" => $terms
                ]
            ];
        }

        $this->queriesArrays []= [
            'type' => 'exactMatch',
            'index' => $index,
            'fields' => $fields,
            'formed_query' => $formedQuery,
        ];
        return $this;
    }

    public function matchAll()
    {
        if (empty($this->realIndexes)) {
            $this->formRealIndices();
        }
        $this->queriesArrays []= [
            'type' => 'query',
            'index' => array_values($this->realIndexes)[1],
            'fields' => null,
            'formed_query' => ["match_all" => (object)[]]
        ];
        return $this;
    }

    public function range($field, $bottomLimit = null, $topLimit = null)
    {
        $index = $this->findIndexByFields([$field]);
        $formedQuery = $this->formSingleRangeItem($field, $bottomLimit, $topLimit);

        $this->queriesArrays []= [
            'type' => 'range',
            'index' => $index,
            'fields' => [$field],
            'top_limit' => $topLimit,
            'bottomLimit' => $bottomLimit,
            'formed_query' => $formedQuery,
        ];
        return $this;
    }

    public function optionalMultiRange($field, $intervals)
    {
        $index = $this->findIndexByFields([$field]);

        $rangeQueries = [];
        foreach ($intervals as $interval) {
            $rangeQueries []= $this->formSingleRangeItem($field, $interval['start'], $interval['end']);
        }
        $formedQuery = [
            "bool" => [
                "should" => $rangeQueries
            ]
        ];

        $this->queriesArrays []= [
            'type' => 'optionalMultiRange',
            'index' => $index,
            'fields' => [$field],
            'intervals' => $intervals,
            'formed_query' => $formedQuery,
        ];
    }

    protected function formSingleRangeItem($field, $start, $end)
    {
        $item = [
            "range" => [
                $field => []
            ]
        ];

        if ($start) {
            $item['range'][$field]['gte'] = $start;
        }
        if ($end) {
            $item['range'][$field]['lte'] = $end;
        }
        return $item;
    }

    public function requestableOrHaveSessions()
    {
        $index = 'parties_accessibility';
        $this->queriesArrays []= [
            'type' => 'requestableOrHaveSessions',
            'index' => $index,
            'fields' => ['open_request', 'open_sessions_with_free_spots'],
            'formed_query' => [
                'bool' => [
                    'should' => [
                        ['match' => ['open_request' => true]],
                        ["range" => [
                            'open_sessions_with_free_spots' => [
                                'gte' => 1,
                            ]
                        ]]
                    ]
                ]
            ]
        ];
        return $this;
    }
}