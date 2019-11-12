<?php

namespace app\modules\utils\controllers\ElasticSearch\PartySearch;

use yii\helpers\ArrayHelper;

trait PartyTimeTrait
{
    static $morningStart = 0;
    static $afternoonStart = 12 * 3600;
    static $eveningStart = 17 * 3600;

    static $morningEnd = 12 * 3600;
    static $afternoonEnd = 17 * 3600;
    static $eveningEnd = 24 * 3600;


    protected function handlePartyTimeWithSingleInterval(PartyMultiSearch $searchObject, $interval)
    {
        $searchObject->range('party_start_time', $interval['start'], $interval['end']);
        return $searchObject;
    }

    protected function handlePartyTimeWithSeveralIntervals(PartyMultiSearch $searchObject, $intervals)
    {
        $searchObject->optionalMultiRange('party_start_time', $intervals);
        return $searchObject;
    }

    protected function chooseStartEndTime($timeOfDay)
    {
        if (is_string($timeOfDay)) {
            $timeOfDay = [$timeOfDay];
        }

        $timeBreaks = $this->chooseTimeBreaks($timeOfDay);
        $timeBreaks = $this->removeDuplicates($timeBreaks);

        return $this->formStartEndTimeResult($timeBreaks);
    }

    protected function chooseTimeBreaks($timeOfDay)
    {
        $timeBreaks = [];
        if (in_array(static::TIME_OF_DAY_MORNING, $timeOfDay)) {
            $timeBreaks = array_merge($timeBreaks, [static::$morningStart, static::$morningEnd]);
        }
        if (in_array(static::TIME_OF_DAY_AFTERNOON, $timeOfDay)) {
            $timeBreaks = array_merge($timeBreaks, [static::$afternoonStart, static::$afternoonEnd]);
        }
        if (in_array(static::TIME_OF_DAY_EVENING, $timeOfDay)) {
            $timeBreaks = array_merge($timeBreaks, [static::$eveningStart, static::$eveningEnd]);
        }
        return $timeBreaks;
    }

    protected function removeDuplicates($timeBreaks)
    {
        $uniqueTimeBreaks = array_unique($timeBreaks);
        $duplicates = array_diff_assoc($timeBreaks, $uniqueTimeBreaks);
        foreach ($duplicates as $duplicate) {
            ArrayHelper::removeValue($timeBreaks, $duplicate);
        }
        return array_values($timeBreaks);
    }

    protected function formStartEndTimeResult($timeBreaks)
    {
        sort($timeBreaks);
        $result = [];
        while ($timeBreaks) {
            $start = array_shift($timeBreaks);
            $end = array_shift($timeBreaks);
            $result []= ['start' => $start, 'end' => $end];
        }
        return $result;
    }

    protected function checkTimeOfDay($timeOfDay)
    {
        $accepted = [
            PartySearch::TIME_OF_DAY_MORNING,
            PartySearch::TIME_OF_DAY_AFTERNOON,
            PartySearch::TIME_OF_DAY_EVENING
        ];
        if (
            (is_string($timeOfDay) && !in_array($timeOfDay, $accepted)) ||
            (is_array($timeOfDay) && !empty(array_diff($timeOfDay, $accepted)))
        ) {
            throw new \Exception('Wrong party time argument');
        }

    }
}