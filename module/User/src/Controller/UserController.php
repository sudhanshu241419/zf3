<?php

namespace User\Controller;

use User\Model\UserTable;
//use User\Model\User;
use Zend\View\Model\JsonModel;
use Zend\EventManager\EventManagerInterface;
use MCommons\Controller\AbstractRestfulController;
use MCommons\StaticFunctions;

class UserController extends AbstractRestfulController {

    private $table;

    public function __construct(UserTable $table) {
        $this->table = $table;
    }

    public function getList() {
        $users = $this->table->fetchAll();
        $data = $userArr = [];
        $i = 0;
        foreach ($users as $user) {
            $data[] = [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email
            ];
            $i++;
        }
        if (!empty($data)) {
            return new JsonModel($data);
        }
    }

    public function get($id) {
        
    }

    public function create($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function delete($id) {
        
    }

    public function setEventManager(EventManagerInterface $events) {
        parent::setEventManager($events);
        $events->attach('dispatch', array($this, 'getConfig'), 10);
    }

    public function getConfig() {
        $event = $this->getEvent();
        $config = $event->getApplication()->getServiceManager()->get('config');
        return $config;
    }

}
