<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\TableGateway\TableGatewayInterface;

class UserTip extends AbstractModel {

    public $id;
    public $user_id;
    public $restaurant_id;
    public $status;
    public $created_at;
    public $tip;
    protected $_primary_key = 'id';
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function insert($data) {
        $rowsAffected = $this->_tableGateway->insert($data);
        $lastInsertId = $this->_tableGateway->lastInsertValue;
        return $lastInsertId;
    }

    public function getTipActivity($restaurantId, $userId, $bookmarkType) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'user_id'
        ));
        $select->where(array(
            'restaurant_id' => $restaurantId,
            'user_id' => $userId,
        ));

        $userTip = $this->_tableGateway->selectWith($select);
        //echo $select->getSqlString($this->getPlatform());
        return $userTip->toArray();
    }

    public function getUserTotalTip($userId) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'total_tip' => new \Zend\Db\Sql\Expression('COUNT(user_tips.id)')
        ));
        $select->where(
                array('user_tips.user_id' => $userId,
                    'user_tips.status' => array(0, 1)
                )
        );

        $totalTip = $this->_tableGateway->selectWith($select)->current();
        //pr($select->getSqlString($this->getPlatform('READ')),true);
        return $totalTip;
    }

    public function getUserTotalTipForDetails($userId) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTableName());
        $select->columns(array(
            'total_tip' => new \Zend\Db\Sql\Expression('COUNT(user_tips.id)')
        ));
        $select->where(array(
            'user_tips.user_id' => $userId
        ));

        $totalTip = $this->_tableGateway->selectWith($select)->current();
        //pr($select->getSqlString($this->getPlatform('READ')),true);
        return $totalTip;
    }

    public function getUserAllTip($userId = false) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array('id', 'restaurant_id'));
        $select->where(array('user_id' => $userId, 'assignMuncher' => '0', 'status' => '1'));
        $select->where->notEqualTo('tip', '');
        $totalCheckin = $this->_tableGateway->selectWith($select);
        return $totalCheckin->toArray();
    }

    public function updateMuncher($data) {
        $this->_tableGateway->update($data, array('id' => $this->id));
        return true;
    }

    public function restaurantTotalTips($restaurantId) {
        $res = $this->find(array(
            'columns' => array(
                'total_count' => new Expression('COUNT(id)'),
            ),
            'where' => array(
                'restaurant_id' => $restaurantId,
                'status' => 1
            )
                ));
        return $res->toArray()[0];
    }

    public function updateCronOrder($id = false) {
        $this->_tableGateway->update(array('cronUpdate' => 1), array('id' => $id));
        return true;
    }

}
