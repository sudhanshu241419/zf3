<?php

namespace City\Controller;

use City\Model\City;
use MCommons\Controller\AbstractRestfulController;
use MCommons\StaticFunctions;

class CityController extends AbstractRestfulController 
{
    public function getList()
    {
        pr("hello in city",1);
        return "";
    }
    
    public function get($id) {
        
    }

    public function create($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function delete($id) {
        
    }

    public function getConfig() {
        $event = $this->getEvent();
        $config = $event->getApplication()->getServiceManager()->get('config');
        return $config;
    }
}
