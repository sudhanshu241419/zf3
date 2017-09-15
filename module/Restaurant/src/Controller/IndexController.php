<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Restaurant\Controller;

use Restaurant\Model\Calendar;
use MCommons\Controller\AbstractRestfulController;
use MCommons\StaticFunctions;

class IndexController extends AbstractRestfulController
{
    public function getList() 
    { 
        pr("hello in restaurant",1);
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
