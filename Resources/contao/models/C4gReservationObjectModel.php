<?php

/*
 * This file is part of con4gis, the gis-kit for Contao CMS.
 * @package con4gis
 * @version 8
 * @author con4gis contributors (see "authors.txt")
 * @license LGPL-3.0-or-later
 * @copyright (c) 2010-2021, by Küstenschmiede GmbH Software & Design
 * @link https://www.con4gis.org
 */
namespace con4gis\ReservationBundle\Resources\contao\models;

use con4gis\CoreBundle\Classes\Helper\ArrayHelper;
use con4gis\CoreBundle\Resources\contao\models\C4gLogModel;
use con4gis\ReservationBundle\Classes\C4gReservationCalculator;
use con4gis\ReservationBundle\Classes\C4gReservationCalculatorResult;
use con4gis\ReservationBundle\Classes\C4gReservationFrontendObject;
use con4gis\ReservationBundle\Classes\C4gReservationHelper;
use Contao\Database;
use Contao\Date;
use Contao\StringUtil;

/**
 * Class C4gReservationObjectModel
 * @package c4g\projects
 */
class C4gReservationObjectModel extends \Model
{

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_c4g_reservation_object';

    private static function check($date)
    {
        if ($date != '') {
            return $date . ',';
        } else {
            return $date;
        }
    }

    public static function getWeekdayExclusionString($list)
    {
        $result = '';
        if ($list) {
            $weekdays = array();
            foreach ($list as $object) {
                $we = $object->getWeekdayExclusion();
                foreach ($we as $key => $value) {
                    $weekdays[$key] = ($value || $weekdays[$key]);
                }
            }

            foreach ($weekdays as $key => $value) {
                if ($value == false) {
                    $result = self::check($result) . $key;
                }
            }
        }

        return $result;
    }

    /**
     * @param $list
     * @param $date
     * @param $type
     * @return int
     */
    public static function getMaxObjectCountPerDate($list, $date, $type) {
        $result = 0;
        $database = Database::getInstance();
        $periodType = $type['periodType'];
        $maxPerTime = $type['objectCount'];

        foreach ($list as $object) {
            $id= $object->getId();
            $objectData = $database->prepare("SELECT * FROM `tl_c4g_reservation_object` WHERE id=? AND published='1'")
                ->execute($id)->fetchAssoc();
            $weekday = date("w",$date);
            $quantity = $objectData['quantity'];

            if ($maxPerTime < $quantity) {
                $quantity = $maxPerTime; //ToDo check max count per interval for all objects
            }

            $interval = $object->getTimeInterval();

            if($weekday == 0){
                $array = unserialize($objectData['oh_sunday']);
            }
            if($weekday == 1){
                $array = unserialize($objectData['oh_monday']);
            }
            if($weekday == 2){
                $array = unserialize($objectData['oh_tuesday']);
            }
            if($weekday == 3){
                $array = unserialize($objectData['oh_wednesday']);
            }
            if($weekday == 4){
                $array = unserialize($objectData['oh_thursday']);
            }
            if($weekday == 5){
                $array = unserialize($objectData['oh_friday']);
            }
            if($weekday == 6){
                $array = unserialize($objectData['oh_saturday']);
            }

            $possibleBookings = 0;
            foreach ($array as $timeset) {

                if (intval($timeset['time_end']) < intval($timeset['time_begin'])) { //nxtday
                    $possibleSeconds = intval(86400+$timeset['time_end']) + intval($timeset['time_begin']);
                } else {
                    $possibleSeconds = intval($timeset['time_end']) - intval($timeset['time_begin']);
                }

                switch ($periodType) {
                    case 'minute':
                        $toSecond = 60;
                        break;
                    case 'hour':
                        $toSecond = 3600;
                        break;
                    default: '';
                }

                if ($possibleSeconds) {
                    $possibleBookings = $possibleBookings + (($possibleSeconds / $toSecond / $interval) * $quantity);
                }
            }

            if ($possibleBookings > 0) {
                $result++;
            }
        }

        return $result;
    }

    public static function getDateExclusionString($list, $type, $removeBookedDays=1)
    {
        $result = '';
        if ($list) {
            $alldates = array();
            $minDate = 0;
            $maxDate = 0;
            //remove configured date exclusion
            foreach ($list as $object) {
                if(!$object instanceof C4gReservationFrontendObject){
                    break;
                }
                $exclusionPeriods = $object->getDatesExclusion();

                foreach ($exclusionPeriods as $period) {
                    if ($period) {
                        $exclusionBegin = $period['date_exclusion'];
                        $exclusionEnd =  $period['date_exclusion_end'];

                        $current = $exclusionBegin;
                        while($current <= $exclusionEnd) {
                            $alldates[] = $current;
                            $current = intval($current) + 86400;
                        }
                    }
                }


                if ($minDate && ($minDate > 1) && ($minDate > $object->getMinReservationDay())) {
                    $minDate = $object->getMinReservationDay();
                }

                if ($maxDate && ($maxDate < 365) && ($maxDate < $object->getMaxReservationDay())) {
                    $maxDate = $object->getMaxReservationDay();
                }
            }

            //remove dates without possible times
            $begin = new \DateTime(date('d.m.Y', time()+($minDate*86400)));
            $end   = new \DateTime(date('d.m.Y',time()+($maxDate*86400)));

            if ($removeBookedDays) {
                for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
                    $beginDate = $i->format('d.m.Y');
                    if (!$beginDate) {
                        continue;
                    }
                    $weekday = date('w', strtotime($beginDate));
                    $timeArr = self::getReservationTimes($list, $type['id'], $weekday, $beginDate);
                    if (!$timeArr || (count($timeArr) == 0)) {
                        $alldates[] = strtotime($beginDate);
                    } else {
                        $excludeTime = true;
                        foreach ($timeArr as $timeElement) {
                            if ($timeElement && $timeElement['objects']) {
                                foreach ($timeElement['objects'] as $timeElementObj) {
                                    if (intval($timeElementObj['id']) && intval($timeElementObj['id']) !== -1) {
                                        $excludeTime = false;
                                        break 2;
                                    }
                                }
                            }
                        }
                        if ($excludeTime) {
                            $alldates[] = strtotime($beginDate);
                        }
                    }
                }
            }

            foreach ($alldates as $date) {
                if ($date) {
                    $result = self::check($result) . $date;
                }
            }
        }

        return $result;
    }

    public static function isEventObject($object) {
        return ($object && (intval($object) > 0)) ? true : false;
    }

    public static function isSunday($date)
    {

        $date = strtotime($date);
        if ($date && (date("w", $date) == 0)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isMonday($date)
    {   
        $date = strtotime($date);
        if ($date && (date("w", $date) == 1)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isTuesday($date)
    {
        $date = strtotime($date);;
        if ($date && (date("w", $date) == 2)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isWednesday($date)
    {
        $date = strtotime($date);
        if ($date && (date("w", $date) == 3)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isThursday($date)
    {
        $date = strtotime($date);
        if ($date && (date("w", $date) == 4)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isFriday($date)
    {
        $date = strtotime($date);
        if ($date && (date("w", $date) == 5)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isSaturday($date)
    {
        $date = strtotime($date);
        if ($date && (date("w", $date) == 6)) {
            return true;
        } else {
            return false;
        }
    }

    private static function addTime($list, $time, $obj, $interval, $endTime = 0)
    {
        $clock = '';
        if (!strpos($GLOBALS['TL_CONFIG']['timeFormat'],'A')) {
            $clock = ' '.$GLOBALS['TL_LANG']['fe_c4g_reservation']['clock'];
        }

        foreach ($list as $key => $item) {
            if ($key === $time || ($interval && ($key === ($time.'#'.$interval))) || ($endTime && ($key === ($time.'#'.($endTime-$time))))) {
                $list[$key]['objects'][] = $obj;
                return $list;
            }
        }

        if ($obj && ($obj['id'] == -1)) {
            $begin = date($GLOBALS['TL_CONFIG']['timeFormat'], $time).$clock;

            if ($interval) {
                $key = $time.'#'.$interval;
                $end = date($GLOBALS['TL_CONFIG']['timeFormat'], $time+$interval).$clock;
                $list[$key] = array('id' => $key, 'time' => $time, 'interval' => $interval, 'name' => $begin.'&nbsp;-&nbsp;'.$end, 'objects' => [$obj]);
            } else if ($endTime && ($endTime != $time)) {
                $key = $time.'#'.($endTime-$time);
                $end = date($GLOBALS['TL_CONFIG']['timeFormat'], $endTime).$clock;
                $list[$key] = array('id' => $key, 'time' => $time, 'interval' => ($endTime-$time), 'name' => $begin.'&nbsp;-&nbsp;'.$end, 'objects' => [$obj]);
            } else {
                $key = $time;
                $list[$key] = array('id' => $key, 'time' => $time, 'interval' => 0, 'name' => $begin, 'objects' => [$obj]);
            }
        } else {
            $begin = date($GLOBALS['TL_CONFIG']['timeFormat'], $time).$clock;

            if ($interval) {
                $key = $time.'#'.$interval;
                $end = date($GLOBALS['TL_CONFIG']['timeFormat'], $time+$interval).$clock;
                $list[$key] = array('id' => $key, 'time' => $time, 'interval' => $interval, 'name' => $begin.'&nbsp;-&nbsp;'.$end, 'objects' => [$obj]);
            } else if ($endTime && ($endTime != $time)) {
                $key = $time.'#'.($endTime-$time);
                $end = date($GLOBALS['TL_CONFIG']['timeFormat'], $endTime).$clock;
                $list[$key] = array('id' => $key, 'time' => $time, 'interval' => ($endTime-$time), 'name' => $begin.'&nbsp;-&nbsp;'.$end, 'objects' => [$obj]);
            } else {
                $key = $time;
                $list[$key] = array('id' => $key, 'time' => $time, 'interval' => 0, 'name' => $begin, 'objects' => [$obj]);
            }
        }

        return $list;
    }

    /**
     * @param $list
     */
    public static function getMinDate($objects)
    {
        $result = '';
        $firstmin = 0;
        $today = time();
        if ($objects) {

            foreach ($objects as $object) {
                $min = $object->getMinReservationDay();
                if ($min && ($min > $firstmin)) {
                    $firstmin = $min;
                }
            }

            return $today + ($firstmin * 3600 * 24);
        }
    }

    public static function getNextWeekday($objects, $weekday)
    {
        $result = '';
        $firstmin = 0;
        $today = time();
        $wd = date('N', $today);
        $diff = $weekday - $wd;
        if ($diff < 0) {
            $diff = 7 - $diff;
        }

        if ($objects) {
            foreach ($objects as $object) {
                $min = $object->getMinReservationDay();
                if ($min && ($min > $firstmin)) {
                    $firstmin = $min;
                }
            }
        }

        return $today + (($firstmin+$diff) * 3600 * 24);
    }

    /**
     * @param $list
     */
    public static function getMaxDate($objects)
    {
        $result = '';
        $lastmax = 365;
        $today = time();
        if ($objects) {
            foreach ($objects as $object) {
                $max = intval($object->getMaxReservationDay());

                if ($max === 0) {
                    $result = $today + (365 * 3600 * 24);
                } elseif ($max < $lastmax) {
                    $result = $today + ($max * 3600 * 24);
                } else{
                    $lastmax = $max;
                    $result = $today + ($lastmax * 3600 * 24);
                }
            }
        }

        return $result;
    }

    /**
     * @param $objects
     * @param $date
     * @return bool
     */
    public static function checkMinMaxDate($objects, $date)
    {
        $format = $GLOBALS['TL_CONFIG']['dateFormat'];
        $today = date_create('Today');
        $tsdate = \DateTime::createFromFormat($format, $date);

        if ($today && $tsdate) {
            $diff = date_diff($today, $tsdate);
            $days = $diff->format('%d');
            if ($days) {
                $d = intval($days);

                if ($d && $objects) {
                    foreach ($objects as $object) {
                        $min = $object->getMinReservatioDay();
                        $max = $object->getMaxReservationDay();

                        if (!$min) {
                            $min = 0;
                        }

                        if (!$max) {
                            $max = 999999;
                        }

                        if (($d >= $min) && ($d <= $max)) {
                            return true;
                        }
                    }
                }
            }

        }

        return false;
    }

    /**
     * @param $tstamp
     * @param $period
     */
    public static function checkValidPeriod($tstamp, $period) {
        $tstamp = intval($tstamp);
        $date_from = intval($period['date_from']);
        $date_to = intval($period['date_to']);

        if ($tstamp && ($date_from || $date_to)) {

            //hit the date
            if ($date_from && $date_to && ($tstamp >= $date_from) && ($tstamp <= $date_to)) {
                return true;
            } else if (!$date_to && $date_from && ($tstamp >= $date_from)) {
                return true;
            } else if (!$date_from && $date_to && ($tstamp <= $date_to)) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $list
     * @param $type
     * @param int $weekday
     * @param null $date
     * @param int $duration
     * @param false $withEndTimes
     * @param false $showFreeSeats
     * @return array|mixed
     */
    public static function getReservationTimes($list, $type, $weekday = -1, $date = null, $duration=0, $withEndTimes=false, $showFreeSeats=false, $checkToday=false)
    {
        $result = array();

        if ($list) {
            shuffle($list);

            //hotfix dates with slashes
            $date = str_replace("~", "/", $date);
            if ($date) {
                $format = $GLOBALS['TL_CONFIG']['dateFormat'];

                $tsdate = \DateTime::createFromFormat($format, $date);
                if ($tsdate) {
                    $tsdate->Format($format);
                    $tsdate->setTime(0,0,0);
                    $tsdate = $tsdate->getTimestamp();
                } else {
                    $format = "d/m/Y";
                    $tsdate = \DateTime::createFromFormat($format, $date);
                    if ($tsdate) {
                        $tsdate->Format($format);
                        $tsdate->setTime(0,0,0);
                        $tsdate = $tsdate->getTimestamp();
                    } else {
                        $tsdate = strtotime($date);
                    }
                }

                $nowDate = new \DateTime();
                if ($nowDate) {
                    $nowDate->Format($format);
                    $nowDate->setTime(0,0,0);
                    $nowDate = $nowDate->getTimestamp();
                }

                $objDate = new Date(date($GLOBALS['TL_CONFIG']['timeFormat'], time()), Date::getFormatFromRgxp('time'));
                $nowTime = $objDate->tstamp;
            }

            if (!$type) {
                return [];
            }

            $typeObject = C4gReservationTypeModel::findByPk($type);
            if (!$typeObject) {
                return [];
            }
            $periodType = $typeObject->periodType;

            $maxCount = intval($typeObject->objectCount);

            $objectType = $typeObject->reservationObjectType;
            $calculator = new C4gReservationCalculator($tsdate, $objectType);

            //$count = []; //count over all objects

            foreach ($list as $object) {
                $found = false;
                $timeArray = []; //count for one object
                $objectQuantity = $object->getQuantity() ?  $object->getQuantity() : 1;

                if ($object->getAllTypesQuantity()) {
                    $maxCount = $objectQuantity;
                }

                $reservationTypes = $object->getReservationTypes();
                
                if ($reservationTypes) {
                    foreach ($reservationTypes as $reservationType) {
                        if ($reservationType == $type) {
                            $found = true;
                            break;
                        }
                    }
                }

                if (!$found) {
                    continue;
                }

                $calculator->loadReservations($typeObject, $object);

                //im Formulat können zurzeit nur Minuten gesetzt werden
                if ($duration >= 1)
                {

                    //$oh = $object->getOpeningHours();
                    switch ($periodType) {
                        case 'minute':
                            $object->setDuration($duration);
                            break;
                        case 'hour':
                            $object->setDuration($duration/60);
                            break;
                        default: '';
                    }
                }

                //else {
                    $oh = $object->getOpeningHours();
                    switch ($periodType) {
                        case 'minute':
                            $interval = $object->getTimeinterval() * 60;
                            $durationInterval = $object->getDuration() > $object->getTimeinterval() ? $object->getDuration() * 60 : $interval;
                            break;
                        case 'hour':
                            $interval = $object->getTimeinterval() * 3600;
                            $durationInterval = $object->getDuration() > $object->getTimeinterval() ? $object->getDuration() * 3600 : $interval;
                            break;
                        default: '';
                    }
                //}

                //max persons
                $desiredCapacity = $object->getDesiredCapacity()[1] ? $object->getDesiredCapacity()[1] : 1;

//                if (!$objectType->severalBookings) {
//                    $maxObjects = $maxCount && ($maxCount < $objectQuantity) ? $maxCount : $objectQuantity;
//                } else {
                    $maxObjects = $maxCount ?: 0;
//                }

                //object count * max persons
                $capacity = $objectQuantity * intval($desiredCapacity);

                if (is_numeric($weekday)) {
                    switch (intval($weekday)) {
                        case 0:
                            $weekday = 'su';
                            break;
                        case 1:
                            $weekday = 'mo';
                            break;
                        case 2:
                            $weekday = 'tu';
                            break;
                        case 3:
                            $weekday = 'we';
                            break;
                        case 4:
                            $weekday = 'th';
                            break;
                        case 5:
                            $weekday = 'fr';
                            break;
                        case 6:
                            $weekday = 'sa';
                            break;
                    }
                }

                if ($durationInterval && ($durationInterval > 0)) {
                    foreach ($oh as $key => $day) {
                        if (($day != -1) && ($key == $weekday)) {
                            foreach ($day as $period) {
                                if (!C4gReservationObjectModel::checkValidPeriod($tsdate, $period)) {
                                    continue;
                                }

                                $time_begin = intval($period['time_begin']);
                                $time_end = intval($period['time_end']);

                                if ($time_end < $time_begin) { //nxt day
                                    if ($time_begin && $time_end) {
                                        $time = $time_begin;
                                        $endOfDate = 86400 + $time_end; //24h + nxt day time
                                        $periodEnd = $endOfDate - $durationInterval;

                                        while ($time <= $periodEnd) {
                                            $id = $object->getId();
                                            if ($date && $tsdate && $time && $typeObject && $capacity) {
                                                $endTime = $time + $interval;
                                                $calculator->calculateAll(
                                                    $tsdate, $time, $endTime, $object, $typeObject, $capacity, $timeArray
                                                );
                                                $calculatorResult = $calculator->getCalculatorResult();
                                                $timeArray = $calculatorResult->getTimeArray();

                                                $endTimeInterval = $interval;
                                                if (!$withEndTimes) {
                                                    $endTimeInterval = 0;
                                                }

                                                $max = $capacity;
                                                if ($calculatorResult->getDbPersons() && !$typeObject->severalBookings && ($objectQuantity == 1)) {
                                                    $time = $time + $interval;
                                                    continue;
                                                }

                                                $timeObj = [
                                                    'id'=>-1,
                                                    'act'=>$calculatorResult->getDbPersons(),
                                                    'percent'=>$calculatorResult->getDbPercent(),
                                                    'max'=>$max,
                                                    'showSeats'=>$showFreeSeats,
                                                    'priority'=>intval($object->getPriority())
                                                ];

                                                $checkTime = $time;
                                                if ($typeObject->bookRunning) {
                                                    $checkTime = $endTime;
                                                }

                                                if ($tsdate && $nowDate && (!$checkToday || ($nowDate < $tsdate) || (($nowDate == $tsdate) && ($nowTime < $checkTime))/* || ($typeObject->directBooking)*/)) {
                                                    if ($calculatorResult->getDbPersons() >= $capacity) {
                                                        //Each object can only be booked once
                                                        //C4gLogModel::addLogEntry('reservation', 'Persons ('.$calculatorResult->getDbPersons().') > capacity ('.$capacity.'): '.$date.' '. date($GLOBALS['TL_CONFIG']['timeFormat'], $time));
                                                        //C4gLogModel::addLogEntry('reservation', 'Interval '.$durationInterval);
                                                    } else if ($maxObjects && ($calculatorResult->getDbBookings() >= intval($maxObjects)) && (!$typeObject->severalBookings || $object->getAllTypesQuantity() || $object->getAllTypesValidity())) {
                                                        //n times for type
                                                        //C4gLogModel::addLogEntry('reservation', 'Buchungen ('.$calculatorResult->getDbBookings().') > capacity ('.intval($maxCount * $capacity).'): '.$date.' '. date($GLOBALS['TL_CONFIG']['timeFormat'], $time));
                                                    } /*else if ($maxObjects && ($calculatorResult->getDbBookedObjects() >= intval($maxObjects)) && $typeObject->severalBookings) {
                                                    //n times for type
                                                    //C4gLogModel::addLogEntry('reservation', 'Buchungen ('.$calculatorResult->getDbBookings().') > capacity ('.intval($maxCount * $capacity).'): '.$date.' '. date($GLOBALS['TL_CONFIG']['timeFormat'], $time));
                                                } */else if ($capacity && (!empty($timeArray)) && ($timeArray[$tsdate][$time] >= intval($capacity))) {
                                                        //n times for object
                                                        //C4gLogModel::addLogEntry('reservation', 'Array-Eintrag ('.$timeArray[$tsdate][$time].') > capacity ('.$capacity.'): '.$date.' '. date($GLOBALS['TL_CONFIG']['timeFormat'], $time));
                                                    } else {
                                                        $timeObj['id'] = $id;
                                                    }

                                                    $result = self::addTime($result, $time, $timeObj, $endTimeInterval);
                                                }
                                            }

                                            $time = $time + $interval;
                                        }
                                    }
                                } else {
                                    if ($time_begin && $time_end) {
                                        $time = $time_begin;
                                        $periodEnd = $time_end - $durationInterval;

                                        while ($time <= $periodEnd) {
                                            $id = $object->getId();
                                            if ($date && $tsdate && $time && $typeObject && $capacity) {
                                                $endTime = $time + $interval;
                                                $calculator->calculateAll(
                                                    $tsdate, $time, $endTime, $object, $typeObject, $capacity, $timeArray
                                                );
                                                $calculatorResult = $calculator->getCalculatorResult();
                                                $timeArray = $calculatorResult->getTimeArray();

                                                $endTimeInterval = $interval;
                                                if (!$withEndTimes) {
                                                    $endTimeInterval = 0;
                                                }

                                                $max = $capacity;

                                                //Todo check "hotfix with several bookings 2.4.14"
                                                /*if ($calculatorResult->getDbPersons() && !$typeObject->severalBookings && ($objectQuantity == 1)) {
                                                    $time = $time + $durationInterval;
                                                    continue;
                                                }*/

                                                $timeObj = [
                                                    'id'=>-1,
                                                    'act'=>$calculatorResult->getDbPersons(),
                                                    'percent'=>$calculatorResult->getDbPercent(),
                                                    'max'=>$max,
                                                    'showSeats'=>$showFreeSeats,
                                                    'priority'=>intval($object->getPriority())
                                                ];

                                                $checkTime = $time;
                                                if ($typeObject->bookRunning) {
                                                    $checkTime = $endTime;
                                                }

                                                if ($tsdate && $nowDate && (!$checkToday || ($nowDate < $tsdate) || (($nowDate == $tsdate) && ($nowTime < $checkTime)))) {
                                                    if ($calculatorResult->getDbPersons() >= $max) {
                                                        //Each object can only be booked once
                                                        //C4gLogModel::addLogEntry('reservation', 'Persons ('.$calculatorResult->getDbPersons().') > capacity ('.$capacity.'): '.$date.' '. date($GLOBALS['TL_CONFIG']['timeFormat'], $time));
                                                        //C4gLogModel::addLogEntry('reservation', 'Interval '.$durationInterval);
                                                    } else if ($maxObjects && ($calculatorResult->getDbBookings() >= intval($maxObjects)) && (!$typeObject->severalBookings || $object->getAllTypesQuantity() || $object->getAllTypesValidity())) {
                                                        //n times for type
                                                        //C4gLogModel::addLogEntry('reservation', 'Buchungen ('.$calculatorResult->getDbBookings().') > capacity ('.intval($maxCount * $capacity).'): '.$date.' '. date($GLOBALS['TL_CONFIG']['timeFormat'], $time));
                                                    } /*else if ($maxObjects && ($calculatorResult->getDbBookedObjects() >= intval($maxObjects)) && $typeObject->severalBookings) {
                                                    //n times for type
                                                    //C4gLogModel::addLogEntry('reservation', 'Buchungen ('.$calculatorResult->getDbBookings().') > capacity ('.intval($maxCount * $capacity).'): '.$date.' '. date($GLOBALS['TL_CONFIG']['timeFormat'], $time));
                                                } */else if ($max && (!empty($timeArray)) && ($timeArray[$tsdate][$time] >= intval($max))) {
                                                        //n times for object
                                                        //C4gLogModel::addLogEntry('reservation', 'Array-Eintrag ('.$timeArray[$tsdate][$time].') > capacity ('.$capacity.'): '.$date.' '. date($GLOBALS['TL_CONFIG']['timeFormat'], $time));
                                                    } else {
                                                        $timeObj['id'] = $id;
                                                    }

                                                    $result = self::addTime($result, $time, $timeObj, $endTimeInterval);
                                                }
                                            }

                                            $time = $time + $interval;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($result && is_array($result) && (count($result) > 0)) {
                return ArrayHelper::sortArrayByFields($result,['id' => SORT_ASC]);
            } else {
                return [];
            }

        }
    }

    /**
     * @param $reservationObject
     * @param $putVars
     */
    public static function preventDublicateBookings($reservationType,$reservationObject,$putVars)
    {
        $result = false;
        if ($reservationObject and $putVars) {
            $objectId = $reservationObject->id;
            $typeId   = $reservationType->id;
            $beginDate = strtotime($putVars['beginDate_'.$typeId]);
            $beginTime = 0;
            foreach ($putVars as $key => $value) {
                if (strpos($key, "beginTime_".$typeId) !== false) {
                    if ($value) {
                        if (strpos($value, '#') !== false) {
                            $value = substr($value,0, strpos($value, '#')); //remove frontend duration
                        }

                        $beginTime = $value;
                        break;
                    }
                }
            }

            $reservationId = $putVars['reservation_id'];
            $t = 'tl_c4g_reservation';
            $arrColumns = array("$t.reservation_type=$typeId AND $t.reservation_object=$objectId AND $t.reservationObjectType='1' AND $t.beginDate=$beginDate AND $t.beginTime=$beginTime AND NOT $t.cancellation='1'");
            $arrValues = array();
            $arrOptions = array();
            $reservations = C4gReservationModel::findBy($arrColumns, $arrValues, $arrOptions);
            $reservationCount = count($reservations);

            if ($reservationType->severalBookings) {
                $factor = $reservationType->objectCount && ($reservationType->objectCount < $reservationObject->quantity) ? $reservationType->objectCount : $reservationObject->quantity;
                if ($reservations && $factor) {
                    $counter = 0;
                    foreach ($reservations as $reservation) {
                        $counter = $counter + $reservation->desiredCapacity;
                    }

                    if ($reservationObject>desiredCapacityMax && (($factor * $reservationObject->desiredCapacityMax) <= $counter)) {
                        return true;
                    }
                }
            } else {
                $maxCount = $reservationType->objectCount && ($reservationType->objectCount < $reservationObject->quantity) ? $reservationType->objectCount : $reservationObject->quantity;
                if ($maxCount && ($reservationCount >= $maxCount)) {
                    return true;
                }
            }
        }
        return $result;
    }

    public static function getButtonStateClass($object) {
        $result = '';

        if ($object && $object->getAlmostFullyBookedAt() &&  $object->getDesiredCapacity() &&  $object->getDesiredCapacity()[1]) {  //orange state
            $t = 'tl_c4g_reservation';
            $id = $object->getId();
            $arrColumns = array("$t.reservation_object=$id AND $t.reservationObjectType='2' AND NOT $t.cancellation='1'");
            $arrValues = array();
            $arrOptions = array();
            $reservations = C4gReservationModel::findBy($arrColumns, $arrValues, $arrOptions);

            $percent = $object->getAlmostFullyBookedAt();
            $reservationCount = count($reservations);

            $desiredCapacity = $object->getDesiredCapacity()[1];

            if ((($reservationCount / $desiredCapacity) * 100) >= 100) {
                $result = ' c4g_radio_object_fully_booked';
            } else if ((($reservationCount / $desiredCapacity) * 100) >= $percent) {
                $result = ' c4g_radio_object_hurry_up';
            }
        }
        
        return $result;
    }

    public static function getReservationEventTime($object, $withEndTimes=false, $showFreeSeats=false) {
        $t = 'tl_c4g_reservation';
        $id = $object->getId();
        $arrColumns = array("$t.reservation_object=$id AND $t.reservationObjectType='2' AND NOT $t.cancellation='1'");
        $arrValues = array();
        $arrOptions = array();
        $reservations = C4gReservationModel::findBy($arrColumns, $arrValues, $arrOptions);
        $actPersons = 0;
        $desiredCapacity = $object->getDesiredCapacity()[1] ? $object->getDesiredCapacity()[1] : 1;
        $capacity = $desiredCapacity;
        if ($reservations) {
            foreach ($reservations as $reservation) {
//                $count[$tsdate][$time] = $count[$tsdate][$time] ? $count[$tsdate][$time] + 1 : 1;
//                $objectCount[$tsdate][$time] = $objectCount[$tsdate][$time] ? $objectCount[$tsdate][$time] + 1 : 1;
                $actPersons = $actPersons + intval($reservation->desiredCapacity);
            }

            if ($object->getAlmostFullyBookedAt()) {
                $percent = ($actPersons / intval($capacity)) * 100;
                if ($percent >= $object->getAlmostFullyBookedAt()) {
                    $actPercent = $percent;
                }
            }
        }

        $timeObj = ['id'=>$id,'act'=>$actPersons,'percent'=>$actPercent,'max'=>$capacity,'showSeats'=>$showFreeSeats];

        $endTime = 0;
        if ($withEndTimes) {
            $endTime = $object->getEndTime();
        }
        return self::addTime([], $object->getBeginTime(), $timeObj, false, $endTime);
    }

    public static function getReservationNowTime($object, $withEndTimes=false, $showFreeSeats=false) {
        $t = 'tl_c4g_reservation';
        $id = intval($object['id']);

        $time = time();

        $oh = $object['openingHours'];
        $weekday = date("w", $time);
        if (is_numeric($weekday)) {
            switch (intval($weekday)) {
                case 0:
                    $weekday = 'su';
                    break;
                case 1:
                    $weekday = 'mo';
                    break;
                case 2:
                    $weekday = 'tu';
                    break;
                case 3:
                    $weekday = 'we';
                    break;
                case 4:
                    $weekday = 'th';
                    break;
                case 5:
                    $weekday = 'fr';
                    break;
                case 6:
                    $weekday = 'sa';
                    break;
            }
        }

        $validDate = false;
        foreach ($oh as $key => $day) {
            if (($day != -1) && ($key == $weekday)) {
                foreach ($day as $period) {
                    if (!C4gReservationObjectModel::checkValidPeriod($time, $period)) {
                        continue;
                    } else {
                        $time_begin = $period['time_begin'];
                        $time_end = $period['time_end'];

                        $objDate = new Date(date($GLOBALS['TL_CONFIG']['timeFormat'],$time), Date::getFormatFromRgxp('time'));
                        $nowTime = $objDate->tstamp;

                        if (($time_begin <= $nowTime) && ($time_end >= $nowTime)) {
                            $validDate = true;
                            break;
                        }
                    }
                }
            }
        }

        if ($validDate) {
            $arrColumns = array("$t.reservation_object=$id AND $t.reservationObjectType='1' AND $t.beginDate<=$time AND $t.endTime>=$time AND NOT $t.cancellation='1'");
            $arrValues = array();
            $arrOptions = array();
            $reservations = C4gReservationModel::findBy($arrColumns, $arrValues, $arrOptions);
            $actPersons = 0;
            $min = $object['min'] ?: 1;
            $max = $object['max'] ?: 1;

            if ($reservations) {
                foreach ($reservations as $reservation) {
                    $actPersons = $actPersons + intval($reservation->desiredCapacity);
                }

                if ($object['almostFullyBookedAt']) {
                    $percent = ($actPersons / intval($max)) * 100;
                    if ($percent >= $object['almostFullyBookedAt']) {
                        $actPercent = $percent;
                    }
                }
            }

            if ($actPersons >= $max) {
                $id = -1;
            }

            $timeObj = ['id'=>$id,'act'=>$actPersons,'percent'=>$actPercent,'min'=>$min,'max'=>$max,'showSeats'=>$showFreeSeats];

            $endTime = 0;
            return self::addTime([], time(), $timeObj, false, $endTime);
        } else {
            return [];
        }
    }


    /**
     * @param null $moduleTypes
     * @param int $objectId
     * @return array
     */
    public static function getReservationObjectList($moduleTypes = null, $objectId = 0, $showPrices = false, $getAllTypes = false)
    {
        $objectlist = array();
        $allTypesList = array();
        foreach ($moduleTypes as $moduleType) {
            if ($moduleType) {
                $type = C4gReservationTypeModel::findByPk($moduleType);
                if ($type && $type->reservationObjectType === '2') {
                    $objectlist = C4gReservationObjectModel::getReservationObjectEventList($moduleTypes, $objectId, $type, $showPrices);

                    if ($getAllTypes) {
                        foreach($objectlist as $key=>$object) {
                            $allTypesList['events'][$key] = $object;
                        }
                    } else {
                        break;
                    }

                } else {
                    $objectlist = C4gReservationObjectModel::getReservationObjectDefaultList($moduleTypes, $type, $showPrices);

                    if ($getAllTypes) {
                        foreach($objectlist as $key=>$object) {
                            $allTypesList['default'][$key] = $object;
                        }
                    } else {
                        break;
                    }
                }
            }
        }

        if ($getAllTypes) {
            return $allTypesList;
        } else {
            return $objectlist;
        }
    }

    /**
     * @param $object
     */
    private static function calcPrices($object, $type, $isEvent = false, $countPersons = 1) {
        $price = 0;
        if ($object) {
            $priceObjs = C4gReservationObjectPricesModel::findBy('published', '1');
            if ($priceObjs) {
                foreach ($priceObjs as $priceObj) {
                    $days = 1;
                    $minutes = 0;
                    $hours = 0;
                    if ($isEvent) {
                        $objects = $priceObj->reservation_event;
                    } else {
                        $objects = $priceObj->reservation_object;
                    }

                    if ($objects) {
                        $objects = unserialize($objects);
                        foreach ($objects as $objectId) {
                            if ($objectId == $object->id) {
                                switch($priceObj->priceoption) {
                                    case 'pMin':
                                        if ($isEvent && $object->startTime && $object->endTime) {
                                            $diff = $object->endTime - $object->startTime;
                                            if ($diff > 0 ) {
                                                $minutes = $diff / 60;
                                            }
                                        } else if (!$isEvent && $type->periodType && $object->time_interval) {
                                            switch ($type->periodType) {
                                                case 'minute':
                                                    $minutes = $object->time_interval;
                                                    break;
                                                case 'hour':
                                                    $minutes = $object->time_interval * 60;
                                                    break;
                                                default: '';
                                            }
                                        }
                                        $price = $price + (intval($priceObj->price)*$minutes);
                                        break;
                                    case 'pHour':
                                        if ($isEvent && $object->startTime && $object->endTime) {
                                            $diff = $object->endTime - $object->startTime;
                                            if ($diff > 0 ) {
                                                $hours = $diff / 3600;
                                            }
                                        } else if (!$isEvent && $type->periodType && $object->time_interval) {
                                            switch ($type->periodType) {
                                                case 'minute':
                                                    $hours = $object->time_interval / 60;
                                                    break;
                                                case 'hour':
                                                    $hours = $object->time_interval;
                                                    break;
                                                default: '';
                                            }
                                        }
                                        $price = $price + (intval($priceObj->price)*$hours);
                                        break;
                                    case 'pDay':
                                        if ($isEvent && $object->startDate && $object->endDate) {
                                            $days = round(abs($object->endDate - $object->startDate) / (60*60*24));
                                        } else if (!$isEvent && $object->beginDate && $object->endDate) {
                                            $days = round(abs($object->endDate - $object->beginDate) / (60*60*24));
                                        }
                                        $price = $price + (intval($priceObj->price)*$days);
                                        break;
                                    case 'pEvent':
                                        $price = $price + intval($priceObj->price);
                                        break;
                                    case 'pPerson':
                                        $price = ($price + intval($priceObj->price)).$GLOBALS['TL_LANG']['fe_c4g_reservation']['pPerson'];
                                        break;
                                }

                                break;
                            }
                        }
                    }
                }
            }
        }

        return $price;
    }

    /**
     * @param null $moduleTypes
     * @param int $objectId
     * @return array
     */
    public static function getReservationObjectEventList($moduleTypes = null, $objectId = 0, $type, $showPrices = false)
    {
        $objectList = array();
        $db = Database::getInstance();
        $almostFullyBookedAt = $type->almostFullyBookedAt;
        if ($objectId) {
            $events = C4gReservationEventModel::findBy('pid',$objectId);
            if ($events) {
                if (count($events) > 1) {
                    C4gLogModel::addLogEntry('reservation', 'There are more than one event connections. Check Event: '.$objectId);
                } else if (count($events) > 0) {
                    $event = $events;
                }
            }

            $eventObject = \CalendarEventsModel::findByPk($objectId);
            if ($event && $eventObject && $eventObject->published && (($eventObject->startTime && ($eventObject->startTime > time())) || (!$eventObject->startTime && $eventObject->startDate && $eventObject->startDate >= time()))) {
                $frontendObject = new C4gReservationFrontendObject();
                $frontendObject->setType(2);
                $frontendObject->setId($eventObject->id);
                $price = $showPrices ? static::calcPrices($eventObject, $type, true, 1) : 0;
                $frontendObject->setCaption($showPrices && $price ? $eventObject->title."<span class='price'>&nbsp;(+".number_format($price,2,',','.')." €)</span>" : $eventObject->title);
                $frontendObject->setDesiredCapacity([$event->minParticipants,$event->maxParticipants]);
                $frontendObject->setBeginDate($eventObject->startDate ?: 0);
                $frontendObject->setBeginTime($eventObject->startTime ?: 0);
                $frontendObject->setEndDate($eventObject->endDate ?: 0);
                $frontendObject->setEndTime($eventObject->endTime ?: 0);
                $frontendObject->setAlmostFullyBookedAt($almostFullyBookedAt);
                $frontendObject->setNumber($event->number ?: '');
                $frontendObject->setAudience($event->targetAudience ? unserialize($event->targetAudience) : []);
                $frontendObject->setEventDuration('');
                $frontendObject->setSpeaker($event->speaker ? unserialize($event->speaker) : []);
                $frontendObject->setTopic($event->topic ? unserialize($event->topic) : []);
                $frontendObject->setLocation($event->location ?: 0);
                $objectList[] = $frontendObject;
            }
        } else {
            $idString = "(" ;
            foreach ($moduleTypes as $key => $typeId) {
                $idString .= "\"$typeId\"";
                if (!(array_key_last($moduleTypes) === $key)) {
                    $idString .= ",";
                }
            }
            $idString .= ")";

            $allEvents = $db->prepare("SELECT * FROM tl_c4g_reservation_event WHERE reservationType IN $idString")->execute()->fetchAllAssoc();
            if ($allEvents) {
                foreach ($allEvents as $event) {
                    $eventObject = \CalendarEventsModel::findByPk($event['pid']);
                    if ($eventObject && $eventObject->published && (($eventObject->startTime && ($eventObject->startTime > time())) || (!$eventObject->startTime && $eventObject->startDate && $eventObject->startDate >= time()))) {
                        $frontendObject = new C4gReservationFrontendObject();
                        $frontendObject->setType(2);
                        $frontendObject->setId($eventObject->id);
                        $price = $showPrices ? static::calcPrices($eventObject, $type, true, 1) : 0;
                        $frontendObject->setCaption($showPrices && $price ? $eventObject->title."<span class='price'>&nbsp;(".number_format($price,2,',','.')." €)</span>" : $eventObject->title);
                        $frontendObject->setDesiredCapacity([$event['minParticipants'],$event['maxParticipants']]);
                        $frontendObject->setBeginDate($eventObject->startDate ?: 0);
                        $frontendObject->setBeginTime($eventObject->startTime ?: 0);
                        $frontendObject->setEndDate($eventObject->endDate ?: 0);
                        $frontendObject->setEndTime($eventObject->endTime ?: 0);
                        $frontendObject->setAlmostFullyBookedAt($almostFullyBookedAt);
                        $frontendObject->setNumber($event['number']);
                        $frontendObject->setAudience($event['targetAudience'] ? unserialize($event['targetAudience']) : []);
                        $frontendObject->setEventDuration('');
                        $frontendObject->setSpeaker($event['speaker'] ? unserialize($event['speaker']) : []);
                        $frontendObject->setTopic($event['topic'] ? unserialize($event['topic']) : []);
                        $frontendObject->setLocation($event['location']);
                        $objectList[] = $frontendObject;
                    }
                }
            }
        }

        return $objectList;
    }

    /**
     * @param null $moduleTypes
     * @param $type
     * @param $calculator
     * @param $date
     * @param false $showPrices
     * @return array
     */
    public static function getReservationObjectDefaultList($moduleTypes = null, $type, $showPrices = false)
    {
        $objectList = array();
        $t = static::$strTable;
        $arrOptions = array();
        $allObjects = self::findBy('published','1');
        $almostFullyBookedAt = $type->almostFullyBookedAt ?: 0;
        if ($moduleTypes) {
            $types = $moduleTypes;
            $objects = [];
            foreach ($allObjects as $object) {
                $objectTypes = unserialize($object->viewableTypes);
                foreach($objectTypes as $objectType) {
                    if (in_array($objectType, $types)) {
                        $objects[] = $object;
                        break;
                    }
                }
            }
        } else {
            $objects = $allObjects;
        }

        if ($objects) {
            foreach ($objects as $object) {
                $frontendObject = new C4gReservationFrontendObject();
                $frontendObject->setType(1);
                $frontendObject->setId($object->id);

                $frontendObject->setCaption($object->caption);

                $captions = $object->options;
                if ($captions) {
                    foreach ($captions as $caption) {
                        if (strpos($GLOBALS['TL_LANGUAGE'],$caption['language']) >= 0) {
                            $frontendObject->setCaption($caption['caption']);
                            break;
                        }
                    }
                }

                $price = $showPrices ? static::calcPrices($object, $type, false, 1) : 0;
                $frontendObject->setCaption($showPrices && $price ? $frontendObject->getCaption()."<span class='price'>&nbsp;(".number_format($price,2,',','.')." €)</span>" : $frontendObject->getCaption());

                $frontendObject->setTimeinterval($object->time_interval);
                $frontendObject->setDuration($object->duration);
                $frontendObject->setMinReservationDay($object->min_reservation_day);
                $frontendObject->setMaxReservationDay($object->max_reservation_day);
                $frontendObject->setReservationTypes(unserialize($object->viewableTypes));
                $frontendObject->setDesiredCapacity([$object->desiredCapacityMin, $object->desiredCapacityMax]);
                $frontendObject->setQuantity($object->quantity);
                $frontendObject->setAllTypesQuantity($object->allTypesQuantity ?: 0);
                $frontendObject->setAllTypesValidity($object->allTypesValidity ?: 0);
                $frontendObject->setAlmostFullyBookedAt($almostFullyBookedAt);
                $frontendObject->setPriority($object->priority ?: 0);
                $frontendObject->setSwitchAllTypes($object->switchAllTypes);


                $opening_hours = array();
                $weekdays = array('0'=>false,'1'=>false,'2'=>false,'3'=>false,'4'=>false,'5'=>false,'6'=>false);

                $opening_hours['su'] = unserialize($object->oh_sunday);
                $opening_hours['mo'] = unserialize($object->oh_monday);
                $opening_hours['tu'] = unserialize($object->oh_tuesday);
                $opening_hours['we'] = unserialize($object->oh_wednesday);
                $opening_hours['th'] = unserialize($object->oh_thursday);
                $opening_hours['fr'] = unserialize($object->oh_friday);
                $opening_hours['sa'] = unserialize($object->oh_saturday);

                //ToDo check if only the first record is empty.
                if ($opening_hours['su'] != false) {
                    if ($opening_hours['su'][0]['time_begin'] && $opening_hours['su'][0]['time_end']) {
                        $weekdays['0'] = true;
                    }
                }
                if ($opening_hours['mo'] != false) {
                    if ($opening_hours['mo'][0]['time_begin'] && $opening_hours['mo'][0]['time_end']) {
                        $weekdays['1'] = true;
                    }
                }
                if ($opening_hours['tu'] != false) {
                    if ($opening_hours['tu'][0]['time_begin'] && $opening_hours['tu'][0]['time_end']) {
                        $weekdays['2'] = true;
                    }
                }
                if ($opening_hours['we'] != false) {
                    if ($opening_hours['we'][0]['time_begin'] && $opening_hours['we'][0]['time_end']) {
                        $weekdays['3'] = true;
                    }
                }
                if ($opening_hours['th'] != false) {
                    if ($opening_hours['th'][0]['time_begin'] && $opening_hours['th'][0]['time_end']) {
                        $weekdays['4'] = true;
                    }
                }
                if ($opening_hours['fr'] != false) {
                    if ($opening_hours['fr'][0]['time_begin'] && $opening_hours['fr'][0]['time_end']) {
                        $weekdays['5'] = true;
                    }
                }
                if ($opening_hours['sa'] != false) {
                    if ($opening_hours['sa'][0]['time_begin'] && $opening_hours['sa'][0]['time_end']) {
                        $weekdays['6'] = true;
                    }
                }

                $frontendObject->setWeekdayExclusion($weekdays);
                $frontendObject->setOpeningHours($opening_hours);
                $frontendObject->setDatesExclusion(unserialize($object->days_exclusion));

                $objectList[] = $frontendObject;
            }
        }

        return $objectList;
    }
}
