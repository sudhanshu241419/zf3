<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\TableGateway\TableGatewayInterface;

class UserEatingHabits extends AbstractModel {

    public $id;
    public $user_id;
    public $favorite_beverage;
    public $where_do_you_go;
    public $comfort_food;
    public $favorite_food;
    public $dinner_with;
    public $created_on;
    public $updated_on;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function findUserEatingHabits($userId = 0) {
        $result = $this->find([
                    'where' => [
                        'user_id' => $userId
                    ]
                ])->current();
        return $result;
    }

    public function update($data) {
        $rowsAffected = $this->_tableGateway->update($data, [
            'id' => $this->id
        ]);
        if ($rowsAffected) {
            return true;
        } else {
            return false;
        }
    }

    public function insert($data) {
        $rowsAffected = $this->_tableGateway->insert($data);
        if ($rowsAffected) {
            $lastInsertId = $this->_tableGateway->lastInsertValue;
            return $lastInsertId;
        } else {
            return false;
        }
    }

}
