<?php

namespace Restaurant\Functions;

class RestaurantDetailsFunctions 
{

    public static $_bookmark_types;
    public static $_isMobile;
    public $deliveryFinalTimings = array();
    public $reserveFinalTimings = array();
    public $restaurantName = '';
    
    public function extract_day_from_date($date) 
    {
        $restaurant_day = array(
            'su',
            'mo',
            'tu',
            'we',
            'th',
            'fr',
            'sa'
        );
        if (empty($date))
            return '';
        $date   =   strtotime($date);
        $day    =   date('w', $date);
        return $restaurant_day [$day];
    }

    public function adjustReserveTimings($timings) 
    {
        $reserveFinalTimings = array();
        if (empty($timings)) 
        {
            return array();
        }
        
        $timArray = explode(",", $timings);
        
        foreach ($timArray as $key => $tim) 
        {
            $tim = trim($tim);
            $tim = explode('-', $tim);
            if (count($tim) != 2) 
            {
                continue;
            }
            $dateTime   =   new \DateTime(trim($tim[0]));
            $open       =   $dateTime->format("H:i");

            $dateTime   =   new \DateTime(trim($tim[1]));
            $close      =   $dateTime->format("H:i");
            $tim        =   array( 'open' => $open, 'close' => $close );
            $timArray[$key] = $tim;
        }
        return $timArray;
    }
}
