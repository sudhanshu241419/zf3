<?php

namespace Restaurant\Model;

use MCommons\Model\AbstractModel;
use MCommons\StaticFunctions;
use Zend\Db\Sql\Select;
use Restaurant\Functions\RestaurantDetailsFunctions;
use Zend\Db\TableGateway\TableGatewayInterface;

class RestaurantCalendar extends AbstractModel {

    public $id;
    public $restaurant_id;
    public $calendar_day;
    public $open_time;
    public $close_time;
    public $breakfast_start_time;
    public $breakfast_end_time;
    public $lunch_start_time;
    public $lunch_end_time;
    public $dinner_start_time;
    public $dinner_end_time;
    public $open_close_status;
    public $status;
    public $operation_hours;
    public $operation_hrs_ft;
    private $__tableName = "restaurant_calendars";
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    protected function getMappedDay($abbr) {
        return isset(StaticFunctions::$dayMapping [$abbr]) ? StaticFunctions::$dayMapping [$abbr] : '';
    }

    public function getOrderOpenCloseSlots($restaurant_id, $date) {
        if ($date == "") {
            $date = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                    ))->format('Y-m-d H:i');
        }

        $flippedMapping = array_flip(StaticFunctions::$dayMapping);

        // Current Date
        $currDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, 'Y-m-d H:i');
        $currDateString = $currDateTime->format('Y-m-d');
        $currDay = $currDateTime->format('D');
        $currDayAbbr = $flippedMapping[$currDay];

        // Previous Date
        $prevDateTime = clone($currDateTime);
        $prevDateTime->sub(new \DateInterval('P1D'));
        $prevDateString = $prevDateTime->format('Y-m-d');
        $prevDay = $prevDateTime->format('D');
        $prevDayAbbr = $flippedMapping[$prevDay];
        $options = array(
            'columns' => array(
                'breakfast_start_time',
                'breakfast_end_time',
                'lunch_start_time',
                'lunch_end_time',
                'dinner_start_time',
                'dinner_end_time',
                'calendar_day'
            ),
            'where' => array(
                'restaurant_id' => $restaurant_id,
                'status' => 1,
                'open_close_status > ?' => 1,
                'calendar_day' => array($prevDayAbbr, $currDayAbbr)
            )
        );

        //$this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $response = $this->find($options)->toArray();
        $yesterdayOperatingHours = array_filter($response, function($operatingHours) use ($prevDayAbbr) {
            return $operatingHours['calendar_day'] == $prevDayAbbr;
        });

        array_walk($yesterdayOperatingHours, array($this, 'adjustDeliveryTimings'));
        if (count($yesterdayOperatingHours)) {
            $yesterdayOperatingHours = array_pop($yesterdayOperatingHours);
        }
        $slotFromYesterday = array();
        foreach ($yesterdayOperatingHours as $openClose) {
            $openDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $prevDateString . " " . $openClose['open'], 'Y-m-d H:i:s');

            $closeDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $prevDateString . " " . $openClose['close'], 'Y-m-d H:i:s');

            if ($closeDateTime <= $openDateTime) {
                $slotFromYesterday = array(
                    'open' => StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $currDateString . ' 00:00', 'Y-m-d H:i'),
                    'close' => $closeDateTime->add(new \DateInterval('P1D')),
                );
            }
        }

        $todayOpenClose = array_filter($response, function($operatingHours) use ($currDayAbbr) {
            return $operatingHours['calendar_day'] == $currDayAbbr;
        });
        array_walk($todayOpenClose, array($this, 'adjustDeliveryTimings'));
        if (count($todayOpenClose)) {
            $todayOpenClose = array_pop($todayOpenClose);
        }

        $slotsFromToday = array();
        $midNightCloseDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $currDateString . ' 23:59', 'Y-m-d H:i');
        foreach ($todayOpenClose as $openClose) {
            $openDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $currDateString . " " . $openClose['open'], 'Y-m-d H:i:s');
            $closeDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $currDateString . " " . $openClose['close'], 'Y-m-d H:i:s');
            if ($closeDateTime <= $openDateTime) {
                $slotForToday = array(
                    'open' => $openDateTime,
                    'close' => $midNightCloseDateTime
                );
            } else {
                $slotForToday = array(
                    'open' => $openDateTime,
                    'close' => $closeDateTime
                );
            }
            $slotsFromToday[] = $slotForToday;
        }
        return array(
            'slotFromYesterday' => $slotFromYesterday,
            'slotsFromToday' => $slotsFromToday
        );
    }

    public function adjustDeliveryTimings(&$element) {
        $openClose = array();
        if (!empty($element ['breakfast_start_time']) && !empty($element ['breakfast_end_time'])) {
            array_push($openClose, array(
                'open' => $element ['breakfast_start_time'],
                'close' => $element ['breakfast_end_time']
            ));
        }
        if (!empty($element ['lunch_start_time']) && !empty($element ['lunch_end_time'])) {
            array_push($openClose, array(
                'open' => $element ['lunch_start_time'],
                'close' => $element ['lunch_end_time']
            ));
        }
        if (!empty($element ['dinner_start_time']) && !empty($element ['dinner_end_time'])) {
            array_push($openClose, array(
                'open' => $element ['dinner_start_time'],
                'close' => $element ['dinner_end_time']
            ));
        }
        $element = $openClose;
    }

    public function getOpenCloseSlots($restaurant_id, $date) {
        if ($date == "") {
            $date = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                    ))->format('Y-m-d H:i');
        } else {
            $tmpDate = new \DateTime($date);
            $date = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $tmpDate->format('Y-m-d'), 'Y-m-d')->format('Y-m-d H:i');
        }

        $flippedMapping = array_flip(StaticFunctions::$dayMapping);

        // Current Date
        $currDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $date, 'Y-m-d H:i');

        $currDateString = $currDateTime->format('Y-m-d');
        $currDay = $currDateTime->format('D');
        $currDayAbbr = $flippedMapping[$currDay];

        // Previous Date
        $prevDateTime = clone($currDateTime);
        $prevDateTime->sub(new \DateInterval('P1D'));

        $prevDateString = $prevDateTime->format('Y-m-d');
        $prevDay = $prevDateTime->format('D');
        $prevDayAbbr = $flippedMapping[$prevDay];
        $restaurantDetails = new RestaurantDetailsFunctions();

        $options = array(
            'columns' => array(
                'operation_hours' => 'operation_hrs_ft',
                'calendar_day'
            ),
            'where' => array(
                'restaurant_id' => $restaurant_id,
                'status' => 1,
                'open_close_status > ?' => 1,
                'calendar_day' => array($prevDayAbbr, $currDayAbbr)
            )
        );
        //$this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $response = $this->find($options)->toArray();

        $yesterdayOperatingHours = array_filter($response, function($operatingHours) use ($prevDayAbbr) {
            return $operatingHours['calendar_day'] == $prevDayAbbr;
        });
        if (count($yesterdayOperatingHours)) {
            $yesterdayOperatingHours = array_pop($yesterdayOperatingHours);
        }
        if (isset($yesterdayOperatingHours['operation_hours']) && !empty($yesterdayOperatingHours['operation_hours']) && $yesterdayOperatingHours['operation_hours'] != 'CLOSED') {
            $yesterdayOperatingHours = $yesterdayOperatingHours['operation_hours'];
        } else {
            $yesterdayOperatingHours = '';
        }

        $todayOperatingHours = array_filter($response, function($operatingHours) use ($currDayAbbr) {
            return $operatingHours['calendar_day'] == $currDayAbbr;
        });
        if (count($todayOperatingHours)) {
            $todayOperatingHours = array_pop($todayOperatingHours);
        }
        if (isset($todayOperatingHours['operation_hours']) && !empty($todayOperatingHours['operation_hours']) && $todayOperatingHours['operation_hours'] != 'CLOSED') {
            $todayOperatingHours = $todayOperatingHours['operation_hours'];
        } else {
            $todayOperatingHours = '';
        }
        $yesterdayOpenClose = $restaurantDetails->adjustReserveTimings($yesterdayOperatingHours);
        $todayOpenClose = $restaurantDetails->adjustReserveTimings($todayOperatingHours);

        $slotFromYesterday = array();
        foreach ($yesterdayOpenClose as $openClose) {

            $openDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $prevDateString . " " . $openClose['open'], 'Y-m-d H:i');

            $closeDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $prevDateString . " " . $openClose['close'], 'Y-m-d H:i');

            if ($closeDateTime <= $openDateTime) {
                $slotFromYesterday = array(
                    'open' => StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $currDateString . ' 00:00', 'Y-m-d H:i'),
                    'close' => $closeDateTime->add(new \DateInterval('P1D')),
                    'day' => 'yesterday'
                );
            }
        }

        $slotsFromToday = array();
        $midNightCloseDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $currDateString . ' 23:59', 'Y-m-d H:i');
        foreach ($todayOpenClose as $openClose) {
            $openDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $currDateString . " " . $openClose['open'], 'Y-m-d H:i');
            $closeDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $currDateString . " " . $openClose['close'], 'Y-m-d H:i');
            if ($closeDateTime <= $openDateTime) {
                $slotForToday = array(
                    'open' => $openDateTime,
                    'close' => $midNightCloseDateTime,
                    'day' => 'today'
                );
            } else {
                $slotForToday = array(
                    'open' => $openDateTime,
                    'close' => $closeDateTime,
                    'day' => 'today'
                );
            }
            $slotsFromToday[] = $slotForToday;
        }

        return array(
            'slotFromYesterday' => $slotFromYesterday,
            'slotsFromToday' => $slotsFromToday
        );
    }

    public function isRestaurantOpen($restaurant_id, $date = "") {
        try {
            if ($date == "") {
                $date = StaticFunctions::getRelativeCityDateTime(array(
                            'restaurant_id' => $restaurant_id
                        ))->format('Y-m-d H:i');
            }

            $slots = $this->getOpenCloseSlots($restaurant_id, $date);
            $slotFromYesterday = $slots['slotFromYesterday'];
            $slotsFromToday = $slots['slotsFromToday'];

            $currDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $date, 'Y-m-d H:i');

            if (!empty($slotFromYesterday) && $currDateTime >= $slotFromYesterday['open'] && $currDateTime <= $slotFromYesterday['close']) {
                return true;
            }

            foreach ($slotsFromToday as $slot) {
                if ($currDateTime >= $slot['open'] && $currDateTime <= $slot['close']) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    public function getRestaurantCalender($restaurant_id) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'restaurant_id',
            'calendar_day'
        ));
        $select->where(array(
            'restaurant_id' => $restaurant_id
        ));
        $restaurantData = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $restaurantData;
    }

    public function getRestaurantReservationHours($restaurant_id, $date) {
        return $this->getOpenCloseSlots($restaurant_id, $date);
    }

    public function getOpeningHours($options = array()) {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $restaurantData = $this->find($options)->toArray();
        return $restaurantData;
    }

    public function calendarDayOperation($restaurantId, $day) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'breakfast_start_time',
            'breakfast_end_time',
            'lunch_start_time',
            'lunch_end_time',
            'dinner_start_time',
            'dinner_end_time',
        ));
        $select->where(array(
            'restaurant_id' => $restaurantId,
            'calendar_day' => $day
        ));
        $calendarDayData = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $calendarDayData;
    }

    public function isRestaurantDeliver($restaurant_id, $is_rest_open = false, $has_delivery) {
        if ($has_delivery == 1) {
            try {
                $date = StaticFunctions::getRelativeCityDateTime(array(
                            'restaurant_id' => $restaurant_id
                        ))->format('Y-m-d H:i');
                $slots = $this->getOrderOpenCloseSlots($restaurant_id, $date);
                $slotFromYesterday = $slots['slotFromYesterday'];
                $slotsFromToday = $slots['slotsFromToday'];
                $currDateTime = StaticFunctions::getAbsoluteCityDateTime(array(
                            'restaurant_id' => $restaurant_id
                                ), $date, 'Y-m-d H:i');
                $resLastOrderTime = $currDateTime->add(new \DateInterval('PT30M'));
                if (!empty($slotFromYesterday) && $currDateTime >= $slotFromYesterday['open'] && $resLastOrderTime <= $slotFromYesterday['close']) {
                    if ($is_rest_open) {
                        return true;
                    }
                }
                foreach ($slotsFromToday as $slot) {
                    if ($currDateTime >= $slot['open'] && $resLastOrderTime <= $slot['close']) {
                        if ($is_rest_open) {
                            return true;
                        }
                    }
                }
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    public function isRestaurantTakeout($restaurant_id, $is_rest_open = false, $has_takeout) {
        $date = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->format('Y-m-d');
        $currDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->format('Y-m-d H:i');

        $orderFinal ['timeslots'] = array();
        foreach (StaticFunctions::getRestaurantTakeoutTimeSlots($restaurant_id, $date) as $t) {
            if ($t ['status'] == 1) {
                $orderFinal ['timeslots'] [] = $t ['slot'];
            }
        }
        $total_slot = count($orderFinal ['timeslots']);
        if ($has_takeout == 1 && $total_slot > 1) {
            $resCloseSlot = $orderFinal ['timeslots'][$total_slot - 2];
            $takeoutTakenLastslot = strtotime($date . ' ' . $resCloseSlot) - 30 * 60;
            if (strtotime($currDateTime) <= $takeoutTakenLastslot) {
                if ($is_rest_open) {
                    return true;
                }
            }
        } else if ($has_takeout == 1 && $total_slot == 1) {
            $resCloseSlot = $orderFinal ['timeslots'][$total_slot - 1];
            $takeoutTakenLastslot = strtotime($date . ' ' . $resCloseSlot) - 30 * 60;
            if (strtotime($currDateTime) <= $takeoutTakenLastslot) {
                if ($is_rest_open) {
                    return true;
                }
            }
        }

        return false;
    }

    public function restaurantDeliveryTimeDiff($restaurant_id, $has_delivery) {

        $ms = 0;
        if ($has_delivery == 1) {
            $date = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                    ))->format('Y-m-d');

            $inputDate = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $date, 'Y-m-d');

            //pr($inputDate->format('H:i'));
            $slots = $this->getOrderOpenCloseSlots($restaurant_id, $inputDate->format('Y-m-d H:i'));
            $finalSlots = array();

            if (!empty($slots['slotsFromToday'])) {
                foreach ($slots['slotsFromToday'] as $slot) {
                    $finalSlots[] = $slot['open']->format('g:i A') . ' - ' . $slot['close']->format('g:i A');
                }
            } else {
                return $ms;
            }

            $currentDayDelivery = StaticFunctions::getPerDayDeliveryStatus($restaurant_id, $date);
            if ($currentDayDelivery) {
                $operatioHours['delivery'][] = implode(",", array_unique($finalSlots));
            } else {
                return $ms;
            }

            $deliveryHours = explode(",", $operatioHours['delivery'][0]);
            //pr($deliveryHours);
            //$deliveryHours = array("10:30 - 11:00");
            //pr($deliveryHours,1);
            foreach ($deliveryHours as $key => $s) {
                $ds = explode(" - ", $s);
                $ds1 = strtotime($ds[0]);
                $ds2 = strtotime($ds[1]);
                //echo $inputDate->format('H:i');
                $ct = strtotime($inputDate->format('H:i'));
                if ($ct >= $ds1 && $ct <= $ds2) {
                    $minusFromLastTimeSlot = strtotime($date . ' ' . $ds[1]) - (45 * 60);
                    //echo "=========";
                    //echo date("Y-m-d H:i", $minusFromLastTimeSlot);
                    //$date2 = $date." ".$inputDate->format('H:i');
                    $date1 = new \DateTime(date("Y-m-d H:i:s", $minusFromLastTimeSlot));
                    $date2 = new \DateTime($date . " " . $inputDate->format('H:i:s'));
                    $interval = $date1->diff($date2);
                    // pr($interval);
                    //echo $int = $interval->invert;
                    //die;
                    if ($interval->invert == 1) {
                        $h = $interval->format('%h') * 60 * 60;
                        $i = $interval->format('%i') * 60;
                        //echo $ms = ($interval->format('%s')+$h+$i)*1000;
                        return $ms = ($interval->format('%s') + $h + $i) * 1000;
                    } else {
                        return $ms = 0;
                    }
                }
                // die;
            }
        }
        return $ms;
    }

    public function restaurantTakeoutTimeDiff($restaurant_id, $has_takeout) {
        $s = 0;
        $date = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->format('Y-m-d');

        $currDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->format('Y-m-d H:i:s');


        $slots = $this->getOpenCloseSlots($restaurant_id, $currDateTime);

        $slotFromYesterday = $slots['slotFromYesterday'];
        $slotsFromToday = $slots['slotsFromToday'];

        $mergedSlots = array_merge_recursive(array($slotFromYesterday), $slotsFromToday);
        $timezoneformat = StaticFunctions::getTimeZoneMapped(array(
                    'restaurant_id' => $restaurant_id
        ));

        //pr($mergedSlots);        
        foreach ($mergedSlots as $key => $dateTimeSlot) {

            if (!empty($dateTimeSlot)) {
                $openDateTimeObj = $dateTimeSlot['open'];
                $closeDateTimeObj = $dateTimeSlot['close'];

                if (strtotime($currDateTime) >= strtotime($openDateTimeObj->format('Y-m-d H:i:s')) && strtotime($currDateTime) <= strtotime($closeDateTimeObj->format('Y-m-d H:i:s'))) {

                    $takeoutTakenLastslot = strtotime($date . " " . $closeDateTimeObj->format('H:i')) - (45 * 60);
                    $currentTimestamp = strtotime($currDateTime);

                    $date1 = new \DateTime(date('Y-m-d H:i:s', $takeoutTakenLastslot));
                    $date2 = new \DateTime($date . " " . date('H:i:s', strtotime($currDateTime)));

                    $interval = $date1->diff($date2);
                    //pr($interval);
                    $h = $interval->format('%h') * 60 * 60;
                    $i = $interval->format('%i') * 60;
                    $s = ($interval->format('%s') + $h + $i) * 1000;
                    break;
                }
            }
        }
        return $s;
    }

    function takeoutPreviousSlotOfRestauratCloseSlot($restaurant_id, $is_rest_open = false, $has_takeout) {
        $pSlot = false;
        $date = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->format('Y-m-d');
        $currDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->format('Y-m-d H:i');

        $orderFinal ['timeslots'] = array();
        foreach (StaticFunctions::getRestaurantTakeoutTimeSlots($restaurant_id, $date) as $t) {
            if ($t ['status'] == 1) {
                $orderFinal ['timeslots'] [] = $t ['slot'];
            }
        }
        //pr($orderFinal ['timeslots']);
        $total_slot = count($orderFinal ['timeslots']);

        if ($has_takeout == 1) {

            if ($total_slot > 1) {
                $pSlot = $orderFinal ['timeslots'][$total_slot - 2];
            } elseif ($total_slot == 1) {
                $pSlot = date("H:i", strtotime($date . ' ' . $orderFinal ['timeslots'][$total_slot - 1]) - 30 * 60);
            } else {
                $pSlot = false;
            }
        } else {
            $pSlot = false;
        }
        return $pSlot;
    }

    function deliveryPreviousSlotOfRestauratCloseSlot($restaurant_id, $is_rest_open = false, $has_delivery) {

        $ms = '';
        if ($has_delivery == 1) {
            $date = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                    ))->format('Y-m-d');

            $inputDate = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $date, 'Y-m-d');

            //pr($inputDate->format('H:i'));
            $slots = $this->getOrderOpenCloseSlots($restaurant_id, $inputDate->format('Y-m-d H:i'));
            $finalSlots = array();

            if (!empty($slots['slotsFromToday'])) {
                foreach ($slots['slotsFromToday'] as $slot) {
                    $finalSlots[] = $slot['open']->format('g:i A') . ' - ' . $slot['close']->format('g:i A');
                }
            } else {
                return $ms;
            }

            $currentDayDelivery = StaticFunctions::getPerDayDeliveryStatus($restaurant_id, $date);
            if ($currentDayDelivery) {
                $operatioHours['delivery'][] = implode(",", array_unique($finalSlots));
            } else {
                return $ms;
            }

            $deliveryHours = explode(",", $operatioHours['delivery'][0]);
            //pr($deliveryHours,1);

            foreach ($deliveryHours as $key => $s) {
                $ds = explode(" - ", $s);
                $ds1 = strtotime($ds[0]);
                $ds2 = strtotime($ds[1]);
                $ct = strtotime($inputDate->format('H:i'));
                if ($ct >= $ds1 && $ct <= $ds2) {
                    $secLastTimeSlot = strtotime($date . ' ' . $ds[1]) - (30 * 60);
                    $ms = date("H:i", $secLastTimeSlot);
                }
            }
        }
        return $ms;
    }

    public function isOpenDeliverForOneHourBefore($restaurant_id, $has_delivery) {
        $ms = false;
        if ($has_delivery == 1) {
            $isRestaurantDeliverCurrently = $this->checkRestaurantOpenOnUserCommingTime($restaurant_id);
            if ($isRestaurantDeliverCurrently) {
                return true;
            }

            $date = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                    ))->format('Y-m-d');

            $inputDate = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $date, 'Y-m-d');

            $slots = $this->getOrderOpenCloseSlots($restaurant_id, $inputDate->format('Y-m-d H:i'));
            $finalSlots = array();
            $slotFromYesterday = $slots['slotFromYesterday'];

            if (!empty($slotFromYesterday)) {
                $finalSlots[] = $slotFromYesterday['open']->format('g:i A') . ' - ' . $slotFromYesterday['close']->format('g:i A');
            }

            if (!empty($slots['slotsFromToday'])) {
                foreach ($slots['slotsFromToday'] as $slot) {
                    $finalSlots[] = $slot['open']->format('g:i A') . ' - ' . $slot['close']->format('g:i A');
                }
            } else {
                return $ms;
            }

            $currentDayDelivery = StaticFunctions::getPerDayDeliveryStatus($restaurant_id, $date);
            if ($currentDayDelivery) {
                $operatioHours['delivery'][] = implode(",", array_unique($finalSlots));
            } else {
                return $ms;
            }

            $deliveryHours = explode(",", $operatioHours['delivery'][0]);
            $timezoneformat = StaticFunctions::getTimeZoneMapped(array(
                        'restaurant_id' => $restaurant_id
            ));

            foreach ($deliveryHours as $key => $s) {
                $ds = explode(" - ", $s);
                $ds1 = strtotime($ds[0]);
                $ds2 = strtotime($ds[1]);
                $ct = strtotime($inputDate->format('H:i A'));
                $currentDateSlot = new \DateTime($date . " " . $inputDate->format('H:i'), new \DateTimeZone($timezoneformat));
                $firstTimeSlot = strtotime($date . ' ' . $ds[0]) - (60 * 60);
                $firstTimeSlotDate = new \DateTime(date("Y-m-d H:i", $firstTimeSlot), new \DateTimeZone($timezoneformat));
                $lastTimeSlot = strtotime($date . ' ' . $ds[1]) - (30 * 60);
                $lastTimeSlotDate = new \DateTime(date("Y-m-d H:i", $lastTimeSlot), new \DateTimeZone($timezoneformat));
                if ($currentDateSlot >= $firstTimeSlotDate && $currentDateSlot < $lastTimeSlotDate) {
                    return true;
                }
            }
        }
        return $ms;
    }

    public function isOpenTakeoutForhalfHourBefore($restaurant_id, $is_currently_open, $has_takeout) {
        $pSlot = false;
        $date = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->format('Y-m-d');
        $currDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->format('Y-m-d H:i');

        $orderFinal ['timeslots'] = array();
        foreach (StaticFunctions::getRestaurantTakeoutTimeSlots($restaurant_id, $date) as $t) {
            if ($t ['status'] == 1) {
                $orderFinal ['timeslots'] [] = $t ['slot'];
            }
        }
        //pr($orderFinal ['timeslots']);

        if (isset($orderFinal ['timeslots'][0]) && !empty($orderFinal ['timeslots'][0])) {
            $timezoneformat = StaticFunctions::getTimeZoneMapped(array('restaurant_id' => $restaurant_id));
            $currentTimeTs = date("Y-m-d H:i", strtotime($currDateTime) + (30 * 60));
            //pr($currentTimeTs);
            $firstSlot = $date . " " . $orderFinal ['timeslots'][0];
            $currentDateSlot = new \DateTime($currentTimeTs, new \DateTimeZone($timezoneformat));
            $firstTimeSlotDate = new \DateTime($firstSlot, new \DateTimeZone($timezoneformat));
            $diffrence = $firstTimeSlotDate->diff($currentDateSlot);
            if ($diffrence->invert == 1) {
                //pr($diffrence);
                $min = $diffrence->h * 60 + $diffrence->i;
                if ($min <= 30) {
                    return true;
                }
            }
        }
        return $pSlot;
    }

    #########################
    /* Delivery
      current time and restaurant first delivery slot difference < 2h
      then
      Delivery_open_timmer = restaurant first delivery slot – 1 h () - current time (Milli sec)
      else
      Delivery_open_timmer = 0


      Takeout
      current time and restaurant Open time difference < 2h
      then
      Takeout_open_timmer = restaurant open time – 1 h – current time (milli sec)
      else
      Takeout_open_timmer = 0 */
    #########################

    public function getDeliveryTakeoutTimmer($restaurantId = false, $hasDeliveryTakeout = false, $orderType = false) {
        $timmer = 0;

        if ($restaurantId) {
            $timezoneformat = StaticFunctions::getTimeZoneMapped(array('restaurant_id' => $restaurantId));
            $currentDateTime = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restaurantId
                    ))->format('Y-m-d H:i:s');
            $currentDate = date('Y-m-d', strtotime($currentDateTime));
            $firstDeliverySlot = $this->getFirstSlot($orderType, $hasDeliveryTakeout, $restaurantId, $currentDate);

            if ($firstDeliverySlot) {
                $deliveryFirstTimeSlot = $currentDate . " " . $firstDeliverySlot;
                $date1 = new \DateTime($currentDateTime, new \DateTimeZone($timezoneformat));
                $deliveryFirstTimeSlot = new \DateTime($deliveryFirstTimeSlot, new \DateTimeZone($timezoneformat));

                $difference = $date1->diff($deliveryFirstTimeSlot);

                if ($difference->invert == 0) {
                    $h = $difference->format('%h') * 60 * 60;
                    $i = $difference->format('%i') * 60;
                    $s = $difference->format('%s') + $h + $i;
                    if ($s < 7200) {
                        $substract1h = $deliveryFirstTimeSlot->sub(new \DateInterval('PT1H')); //substract 1 H
                        $interval = $substract1h->diff($date1);
                        $h = $interval->format('%h') * 60 * 60;
                        $i = $interval->format('%i') * 60;
                        $timmer = ($interval->format('%s') + $h + $i) * 1000;
                    }
                }
            }
        }
        return $timmer;
    }

    public function getFirstSlot($orderType, $hasDeliveryTakeout, $restaurantId, $currentDate) {
        $firstDeliverySlot = false;
        $orderTimeSlot = explode("-", ORDER_TIME_SLOT);
        $startOrderTime = strtotime($orderTimeSlot[0] . ":00");
        $endOrderTime = strtotime($orderTimeSlot[1] . ":00");
        if ($orderType == 'order') {
            $slots = StaticFunctions::getRestaurantOrderTimeSlots($restaurantId, $currentDate);
        } elseif ($orderType == 'takeout') {
            $slots = StaticFunctions::getRestaurantTakeoutTimeSlots($restaurantId, $currentDate);
        }
        $firstDeliverySlot = false;
        foreach ($slots as $key => $slot) {
            if ($slot['status'] == 1) {
                $firstDeliverySlot = $slot['slot'];
                $slotHour = strtotime($firstDeliverySlot);
                if ($slotHour >= $startOrderTime && $slotHour < $endOrderTime) {
                    $firstDeliverySlot = $slot['slot'];
                    break;
                } else {
                    $firstDeliverySlot = false;
                }
            } else {
                $firstDeliverySlot = false;
            }
        }
        return $firstDeliverySlot;
    }

    /**
     * Get restaurants delivery slots
     * @param int $restaurant_id
     * @param string $day 2 char day
     * @return array
     */
    public function getResDeliverySlotsForDay($restaurant_id, $day) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'breakfast_start_time', 'breakfast_end_time',
            'lunch_start_time', 'lunch_end_time',
            'dinner_start_time', 'dinner_end_time',
        ));
        $select->where(array(
            'restaurant_id' => $restaurant_id,
            'calendar_day' => $day,
        ));
        $restaurantData = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $restaurantData;
    }

    /**
     * Get restaurants delivery slots
     * @param int $restaurant_id
     * @param string $day 2 char day
     * @return array with keys 'operation_hours','calendar_day','operation_hrs_ft'
     */
    public function getResOperationHoursForDay($restaurant_id, $day) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array('operation_hours', 'calendar_day', 'operation_hrs_ft'));
        $select->where(array(
            'restaurant_id' => $restaurant_id,
            'calendar_day' => $day,
        ));
        $restaurantData = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $restaurantData;
    }

    public function checkRestaurantOpenOnUserCommingTime($restaurant_id) {
        $ms = false;
        $currentDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->format('Y-m-d H:i A');
        $dateArray = explode(' ', $currentDateTime);
        $staticDateTime1 = $dateArray[0] . " 11:00 PM";
        $staticDateTime2 = $dateArray[0] . " 11:59 PM";

        if (strtotime($currentDateTime) >= strtotime($staticDateTime1) && strtotime($currentDateTime) <= strtotime($staticDateTime2)) {
            $timezoneformat = StaticFunctions::getTimeZoneMapped(array(
                        'restaurant_id' => $restaurant_id
            ));

            $currentDateTimeObj = new \DateTime(date("Y-m-d", strtotime($currentDateTime)), new \DateTimeZone($timezoneformat));

            $nextDateTime = $currentDateTimeObj->add(new \DateInterval('P1D'));

            $deliveryHours = $this->getNextDaySlot($nextDateTime, $restaurant_id);

            if ($deliveryHours && !empty($deliveryHours)) {
                $deliveryHoursArray = explode(" - ", $deliveryHours[0]);

                if (in_array("12:00 AM", $deliveryHoursArray)) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        return $ms;
    }

    public function getNextDaySlot($nextDateTime, $restaurant_id) {
        $ms = false;
        $slots = $this->getOrderOpenCloseSlots($restaurant_id, $nextDateTime->format('Y-m-d H:i'));
        $finalSlots = array();
        $slotFromYesterday = $slots['slotFromYesterday'];
        if (!empty($slotFromYesterday)) {
            $finalSlots[] = $slotFromYesterday['open']->format('g:i A') . ' - ' . $slotFromYesterday['close']->format('g:i A');
        }

        if (!empty($slots['slotsFromToday'])) {
            foreach ($slots['slotsFromToday'] as $slot) {
                $finalSlots[] = $slot['open']->format('g:i A') . ' - ' . $slot['close']->format('g:i A');
            }
        } else {
            return $ms;
        }

        $currentDayDelivery = StaticFunctions::getPerDayDeliveryStatus($restaurant_id, $nextDateTime->format("Y-m-d"));

        if ($currentDayDelivery) {
            $operatioHours['delivery'][] = implode(",", array_unique($finalSlots));
        } else {
            return $ms;
        }
        return $deliveryHours = explode(",", $operatioHours['delivery'][0]);
    }

    /**
     * Get restaurant weekly calendar
     * @param int $restaurant_id
     * @return array with keys 'calendar_day',
      'breakfast_start_time', 'breakfast_end_time',
      'lunch_start_time', 'lunch_end_time',
      'dinner_start_time', 'dinner_end_time',
      'operation_hours','operation_hrs_ft'
     */
    public function getResWeekCalData($restaurant_id) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'calendar_day',
            'breakfast_start_time', 'breakfast_end_time',
            'lunch_start_time', 'lunch_end_time',
            'dinner_start_time', 'dinner_end_time',
            'operation_hours'
        ));
        $select->where(array(
            'restaurant_id' => $restaurant_id,
        ));
        $restaurantData = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $restaurantData;
    }

    public function restaurantOpenCloseTime($restaurantid) {
        $currentDateTime = \MCommons\StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurantid
                ))->format(StaticFunctions::MYSQL_DATE_FORMAT);
        $day = date("D", strtotime($currentDateTime));
        $keys = array_keys(StaticFunctions::$dayMapping, $day);
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'open_time',
            'close_time'
        ));
        $select->where(array(
            'restaurant_id' => $restaurantid,
            'calendar_day' => $keys[0]
        ));
        $restaurantData = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $restaurantData;
    }

}
