<?php

namespace app\modules\utils\controllers\ElasticSearch\PartySearch;

use app\modules\utils\controllers\ElasticSearch\SearchConstructor\AbstractSearchConstructor;
use Carbon\Carbon;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;

class PartySearch extends AbstractSearchConstructor
{
    use PartyTimeTrait;

    const TIME_OF_DAY_MORNING = 'morning';
    const TIME_OF_DAY_AFTERNOON = 'afternoon';
    const TIME_OF_DAY_EVENING = 'evening';

    protected $parameterTypes = [
        'query' => 'applyQuery',
        'date' => 'applySessionDate',
        'time' => 'applyPartyTime',
        'guests' => 'applyGuests',
        'spots' => 'applyFreeSpots',
        'themes' => 'applyThemes',
        'types' => 'applyTypes',
        'price' => 'applyPrice',
        'location' => 'applyLocation',
        'difficulty' => 'applyDifficulty',
        'duration' => 'applyMultiDay',
        'multi_day' => 'applyMultiDay',
    ];
    protected $multiSearchClass = PartyMultiSearch::class;

    protected function applyQuery(PartyMultiSearch $searchObject, $queryString)
    {
        $searchObject->query(['name', 'description'], $queryString);
        return $searchObject;
    }

    protected function applySessionDate(PartyMultiSearch $searchObject, $params)
    {
        $startDate = $params['start'];
        $endDate = ArrayHelper::getValue($params, 'end', null);
        $endDate = $this->processUnlimitedEnd($endDate);
        $startTime = Carbon::parse($startDate)->startOfDay()->getTimestamp();
        $endTime = $endDate ? Carbon::parse($endDate ?: $startTime)->startOfDay()->getTimestamp() : null;
        $searchObject->range('session_time', $startTime, $endTime);
        return $searchObject;
    }

    protected function applyPartyTime(PartyMultiSearch $searchObject, $timeOfDay)
    {
        $this->checkTimeOfDay($timeOfDay);
        $intervals = $this->chooseStartEndTime($timeOfDay);
        if (count($intervals) == 1) {
            return $this->handlePartyTimeWithSingleInterval($searchObject, $intervals[0]);
        }

        return $this->handlePartyTimeWithSeveralIntervals($searchObject, $intervals);
    }

    protected function applyGuests(PartyMultiSearch $searchObject, $guests)
    {
        return $this->applyFreeSpots($searchObject, $guests + 1);
    }

    protected function applyFreeSpots(PartyMultiSearch $searchObject, $freeSpots)
    {
        $searchObject->range('free_spots', $freeSpots);
        return $searchObject;
    }

    protected static function applyThemes(PartyMultiSearch $searchObject, $themes)
    {
        $searchObject->exactMatchAnyQuery(['theme1', 'theme2', 'theme3'], $themes);
        return $searchObject;
    }

    protected static function applyTypes(PartyMultiSearch $searchObject, $type)
    {
        $searchObject->exactMatchAnyQuery(['type'], $type);
        return $searchObject;
    }

    protected function applyPrice(PartyMultiSearch $searchObject, $params)
    {
        $start = ArrayHelper::getValue($params, 'start', null);
        $end = ArrayHelper::getValue($params, 'end', null);
        $end = $this->processUnlimitedEnd($end);
        $searchObject->range('price', $start, $end);
        return $searchObject;
    }

    protected function applyLocation(PartyMultiSearch $searchObject, $queryString)
    {
        $searchObject->query(['city_name'], $queryString);
        return $searchObject;
    }

    protected function applyDifficulty(PartyMultiSearch $searchObject, $difficulty)
    {
        $searchObject->exactMatchAnyQuery(['difficulty'], [$difficulty]);
        return $searchObject;
    }

    protected static function applyMultiDay(PartyMultiSearch $searchObject, $multiDayFlag)
    {
        $searchObject->exactMatchAnyQuery(['multi_day'], [$multiDayFlag]);
        return $searchObject;
    }

    protected function gatherData($searchResult)
    {
        $results = [];
        foreach ($searchResult as $party_id) {
            $query = "
        SELECT a.`party_id`, a.`mediaid`, a.`name`, a.`city_name`, a.`description`, 
           (SELECT AVG(ratings.value) 
            FROM `attendances`
            LEFT JOIN ratings USING (`attendanceid`)
            WHERE attendances.party_id = a.party_id) as rating_average,
           (SELECT COUNT(*) 
            FROM `attendances`
            INNER JOIN ratings USING (`attendanceid`)
            WHERE attendances.party_id = a.party_id) as rating_count,
           m.`guid`, l.latitude, l.longitude,
           a.`open_request`, a.price, a.multi_day
        FROM `parties` a
            LEFT JOIN `locations` l USING (`locationid`)
            LEFT JOIN `media` m USING (`mediaid`)
        WHERE a.party_id = %i_party_id";
            $results []= \DB::queryOneRow($query, [
                'party_id' => $party_id
            ]);
        }

        $parties = [];
        foreach ($results as $result) {
            $clean = [
                'party_id' => (int) $result['party_id'],
                'name' => $result['name'],
                'city_name' => $result['city_name'],
                'rating' => [
                    'average' => $result['rating_average'] ?: 0,
                    'count' => (float) $result['rating_count'],
                ],
                'main_image' => [
                    'url' => Media::urlFromGuid($result['guid']),
                    'mediaid' => (int) $result['mediaid'],
                ],
                'price' => $result['price'],
                'multi_day' => $result['multi_day'],
                'description' => $result['description'],
            ];
            $clean['latitude'] = $result['latitude'];
            $clean['longitude'] = $result['longitude'];
            $parties []= $clean;
        }

        return $parties;
    }

    protected function processUnlimitedEnd($end)
    {
        if (StringHelper::endsWith($end, '+')) {
            return null;
        }
        return $end;
    }
}