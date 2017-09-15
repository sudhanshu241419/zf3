<?php

namespace User\Controller;

use User\Model\UserOrderTable;
use User\Model\UserOrder;
//use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
//use Zend\EventManager\EventManagerInterface;
use MCommons\Controller\AbstractRestfulController;
//use MCommons\StaticFunctions;

class UserOrderController extends AbstractRestfulController {

    private $table;

    public function __construct(UserOrderTable $table) {
        $this->table = $table;
    }

    public function getList() { 
        $orders = $this->table->fetchAll();
        $data = $userArr = [];
        $i = 0;
        foreach ($orders as $order) {
            $data[] = [
                //'codigo_torcedor' => $user->codigo_torcedor,
                'fname' => $order->fname,
                'lname' => $order->lname,
                'email' => $order->email
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
}
