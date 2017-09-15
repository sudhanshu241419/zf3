<?php

namespace Search\Common;

/**
 * Description of DateTimeUtility
 *
 * @author bravvura
 */
class DateTimeUtility {
    
    public static $nextDay2Char = array(
        "su" => "mo",
        "mo" => "tu",
        "tu" => "we",
        "we" => "th",
        "th" => "fr",
        "fr" => "sa",
        "sa" => "su"
    );
    
    public static $prevDay2Char = array(
        "su" => "sa",
        "mo" => "su",
        "tu" => "mo",
        "we" => "tu",
        "th" => "we",
        "fr" => "th",
        "sa" => "fr"
    );
    
    /**
     * Get city's current date 'Y-m-d' format, day (2 char lower case), 
     * and time (int value in 0-2359 range)
     * @param int $city_id
     * @return array with keys 'date', 'day', 'time', 'timezone', 'city_id'
     * @throws \Exception
     */
    public static function getCityDayDateAndTime24F($city_id)
    {
        /*
         * Above Object can not be created right now as there is no City module. As soon as the
         * Module gets created we would make it dynamic.
         * Right now we are applying hack to proceed. [ Athar ]
         */
        /*
        $city   =   new City();
        $detail =   $city->cityDetails($city_id);
        */
        $detail = array();
        $detail[0]['id']           =   18848;    
        $detail[0]['city_name']    =   "New York";    
        $detail[0]['state_code']   =   "NY";    
        $detail[0]['latitude']     =   "40.7127";    
        $detail[0]['longitude']    =   "-74.005898";    
        $detail[0]['time_zone']    =   "America/New_York";    
        $detail[0]['neighbouring'] =   "";
        if(empty($detail))
        {
            throw new \Exception('invalid city id');
        }
        $datetime   =   new \DateTime; // current time = server time
        $otherTZ    =   new \DateTimeZone($detail[0]['time_zone']);
        $datetime->setTimezone($otherTZ); // calculates with new TZ now
        
        return array(
            'city_id'   =>  $city_id,
            'date'      =>  $datetime->format('Y-m-d'),
            'day'       =>  substr(strtolower($datetime->format('D')), 0, 2),
            'time'      =>  intval($datetime->format('Hi')),
            'timezone'  =>  $detail[0]['time_zone'],
        );
    }

    /**
     * 
     * @param int $time_24_format in range 0-2359 
     * @return int number of seconds since midnight
     */
    public static function getSecondsFrom24HTime($time_24_format) 
    {
        $hours      =   intval($time_24_format / 100);
        $minutes    =   $time_24_format % 100;
        return $hours * 3600 + $minutes * 60;
    }

    /**
     * 
     * @param int $seconds in range 0-2359 
     * @return int number of seconds since midnight
     */
    public static function get24HTimeFromSeconds($seconds) 
    {
        $hours          =   intval($seconds / 3600);
        $extraSeconds   =   $seconds % 3600;
        $rawMinutes     =   intval($extraSeconds / 60);
        $minutesString  =   ($rawMinutes < 9) ? '0' . $rawMinutes : $rawMinutes;
        return intval($hours . $minutesString);
    }

    /**
     * 
     * @param int $time_24Hours in range 0-2359 
     * @return string 12:00 AM to 12:00 PM
     */
    public static function get24HourHiTo12HAmPmTime($time_24Hours) 
    {
        $hours          =   intval($time_24Hours / 100);
        $rawHours       =   ($hours <= 12) ? $hours : ($hours - 12);
        $hoursString    =   ($rawHours < 10) ? '0' . $rawHours : $rawHours;
        $rawMinutes     =   intval($time_24Hours % 100);
        $minutesString  =   ($rawMinutes < 9) ? '0' . $rawMinutes : $rawMinutes;
        $amPm           =   ($hours < 12) ? ' AM' : ' PM';
        return $hoursString . ':' . $minutesString . $amPm;
    }

    /**
     * Get time difference in milliseconds between 2 24H format times.
     * @param int $start_time_24Hours in range 0-2359
     * @param int $end_time_24Hours in range 0-2359
     * @return int
     */
    public static function getTimeDiffInMilli24H($start_time_24Hours, $end_time_24Hours) 
    {
        if ($start_time_24Hours >= $end_time_24Hours) 
        {
            return 0;
        }
        return (self::getSecondsFrom24HTime($end_time_24Hours) - self::getSecondsFrom24HTime($start_time_24Hours)) * 1000;
    }

}
