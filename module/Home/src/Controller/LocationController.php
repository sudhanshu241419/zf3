<?php

namespace Home\Controller;

use MCommons\Controller\AbstractRestfulController;
use Home\Model\State;

class LocationController extends AbstractRestfulController {

    private static $cityTableFields = [
        'cty_name' => 'city_name',
        'cty_latitude' => 'latitude',
        'cty_longitude' => 'longitude',
        'cty_id' => 'city_id',
        'cty_status' => 'status',
        'cty_browseonly' => 'is_browse_only'
    ];

    public function getList() {
        try {
            $allStates = [];
            $stateModel = $this->getServiceLocator(State::class);
            $memCached =  $this->getServiceLocator("memCachedObject");      
            $config = $this->getServiceLocator('Config');
            if ($config ['constants'] ['memcache'] && $memCached->getItem('location')) {
                return $memCached->getItem('location');
            } else {
                $states = $stateModel->getStates();
                $allStates = ($states) ? $this->refineLocationData($states) : $states;
                $memCached->setItem('location', $allStates, 0);
                return $allStates;
            }
        } catch (\Exception $e) {
            \MUtility\MunchLogger::writeLog($e, 1, 'Something Went Wrong On Location Api');
            throw new \Exception($e->getMessage(), 400);
        }
    }

    public function refineLocationData($states = []) {
        $locationData = [];
        $index = 0;
        foreach ($states as $state) {
            foreach ($state as $key => $val) {
                if (strpos($key, 'cty_') !== false) {
                    $locationData [$index] ['cities'] [self::$cityTableFields [$key]] = $val;
                } else {
                    $locationData [$index] [$key] = $val;
                }
            }
            $index ++;
        }
        return $this->reArrangeStateCity($locationData);
    }

    private function reArrangeStateCity($locationData = []) {
        $output = [];
        foreach ($locationData as $data) {
            if (!empty($output [$data ['id']]) && count($output [$data ['id']]) != 0) {
                $output [$data ['id']] ['cities'] [] = $data ['cities'];
            } else {
                $index = $data ['id'];
                $output [$index] ['state'] = $data ['state'];
                $output [$index] ['state_code'] = $data ['state_code'];
                $output [$index] ['cities'] [] = $data ['cities'];
            }
        }
        $output = array_values($output);
        return $output;
    }
}
