<?php

namespace app\modules\utils\controllers\ElasticSearch;

class SearchResult implements SearchResultInterface
{
    protected $resultsArray;
    protected $searchField;
    protected $scored;
    protected $numberOfScores;
    protected $_exclude = [];

    protected function __construct($resultsArray, $searchField)
    {
        $this->resultsArray = $resultsArray;
        $this->searchField = $searchField;
        $this->numberOfScores = count($resultsArray);
        $this->scored = $this->formSearchedWithScore($resultsArray, $this->searchField, $this->numberOfScores);
    }

    public static function create($resultsArray, $searchField)
    {
        return new static($resultsArray, $searchField);
    }

    public function exclude(array $exclude)
    {
        $this->_exclude = $exclude;
        return $this;
    }

    public function getRawResults()
    {
        return $this->resultsArray;
    }

    protected function formSearchedWithScore($resultsArray, $searchField, $numberOfScores)
    {
        // Form array of 'id' => [<scores>]
        $condensed = $this->condenseScores($resultsArray, $searchField);

        // Take only ids that occur for each search parameter
        $finalResult = $this->checkFitsAllParameters($condensed, $numberOfScores);
        return $finalResult;
    }

    protected function condenseScores($resultsArray, $searchField)
    {
        $condensed = [];
        foreach ($resultsArray as $result) {
            $doubles = [];
            foreach ($result as $line) {
                if (!array_key_exists($line[$searchField], $condensed)) {
                    $condensed[$line[$searchField]] = [];
                }
                // prevent adding double scores for the same query
                // that prevents spamming number of occurrences in single query
                if (in_array($line[$searchField], $doubles)) {
                    continue;
                }
                $condensed[$line[$searchField]] []= $line['score'];
                $doubles []= $line[$searchField];
            }
        }
        return $condensed;
    }

    protected function checkFitsAllParameters($condensed, $numberOfScores)
    {
        $finalResult = [];
        foreach ($condensed as $key => $scores) {
            if (count($scores) == $numberOfScores) {
                $finalResult [$key]= $scores;
            }
        }
        return $finalResult;
    }

    protected function countAverageScore($scored)
    {
        $withAverage = [];
        foreach ($scored as $key => $scores) {
            $withAverage [$key]= array_sum($scores)/count($scores);
        }
        return $withAverage;
    }

    public function getIds()
    {
        $withAverage = $this->countAverageScore($this->scored);
        arsort($withAverage);
        return array_values(array_diff(array_keys($withAverage), $this->_exclude));
    }
}