<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Predicate\Expression;

class UserPoints extends AbstractModel {

    public $id;
    public $user_id;
    public $status = 1;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function UserPointsDetails($user_id = 0) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'activity_title' => 'points_descriptions',
            'created_at',
            'activity_id' => 'id',
            'activity_points' => 'points',
            'redeemPoint'
        ));
        $select->join(array(
            'pd' => 'point_source_detail'
                ), 'pd.id = user_points.point_source', array(
            'activity_type' => 'name',
            'activity_identifier' => 'identifier'
                ), $select::JOIN_INNER
        );
        $where = new Where ();
        $where->equalTo('user_id', $user_id);
        $where->equalTo('status', 1);
        $select->order(array('user_points.id' => 'DESC'));
        $select->where($where);
        $userPointsDetails = $this->_tableGateway->selectWith($select)->toArray();
        return $userPointsDetails;
    }

    public function UserLastActivity($user_id) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'activity_title' => 'points_descriptions',
            'created_at',
            'activity_id' => 'id',
            'activity_points' => 'points'
        ));
        $select->join(array('ps' => 'point_source_detail'), 'ps.id = user_points.point_source', array('acitvity_identifier' => 'identifier'), $select::JOIN_INNER);

        $where = new Where ();
        $where->equalTo('user_id', $user_id);
        $where->equalTo('status', 1);
        $select->where($where);
        $select->order('created_at desc');
        $select->limit('1');
        $userPointsDetails = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);
        return $userPointsDetails->toArray();
    }

    public function gettingCustomersWhoEarnMostPoints($startDate, $endDate) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'user_id',
            'total_points' => new Expression('SUM(user_points.points)')
        ));
        $select->join(array(
            'rs' => 'restaurant_servers'
                ), 'user_points.user_id = rs.user_id and user_points.restaurant_id = rs.restaurant_id', array(
            'restaurant_id',
            'user_id',
            'code'
                ), $select::JOIN_INNER);
        $where = new Where ();
        $where->between('rs.date', $startDate, $endDate);
        $select->where($where);
        $select->group('user_points.user_id');
        $select->order('total_points desc');
        $select->limit(3)->offset(0);
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $serversList = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $serversList;
    }

    public function gettingServerPoints($code, $startDate, $endDate) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'user_id',
            'total_points' => new Expression('SUM(user_points.points)')
        ));
        $select->join(array(
            'rs' => 'restaurant_servers'
                ), 'user_points.user_id = rs.user_id', array(
            'restaurant_id'
                ), $select::JOIN_INNER);
        $select->join(array(
            's' => 'servers'
                ), 'rs.code = s.code', array(
            'server_name' => new Expression("CONCAT(s.first_name,' ',s.last_name)")
                ), $select::JOIN_INNER);
        $where = new Where ();
        $where->equalTo('s.code', $code);
        $where->between('rs.date', $startDate, $endDate);
        $select->where($where);
        $select->group('user_id');
        $select->order('total_points desc');
        $select->limit(1);
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $serversList = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $serversList;
    }

    public function getUserTotalPoints($userId, $restId, $restStartDate, $restEndDate) {
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->columns(array(
            'total_points' => new Expression('SUM(points)'),
        ));
        $where = new Where ();
        $where->equalTo('user_id', $userId);
        $where->equalTo('restaurant_id', $restId);
        $where->between('created_at', $restStartDate, $restEndDate);
        $select->where($where);
        $select->group('user_id');
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $data = $this->getDbTable()
                        ->setArrayObjectPrototype('ArrayObject')
                        ->getReadGateway()
                        ->selectWith($select)->toArray();
        if (!empty($data)) {
            return $data[0]['total_points'];
        } else {
            return 0;
        }
    }

    public function getRestaurantsTotalPoints($restId, $restStartDate, $restEndDate) {
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->columns(array(
            'total_points_accured' => new Expression('SUM(points)'),
            'total_points_redeemed' => new Expression('SUM(redeemPoint)'),
        ));
        $select->join(array(
            'rs' => 'restaurant_servers'
                ), 'rs.user_id = user_points.user_id and rs.restaurant_id = user_points.restaurant_id', array(
                ), $select::JOIN_INNER);
        $where = new Where ();
        $where->equalTo('user_points.restaurant_id', $restId);
        $where->between('user_points.created_at', $restStartDate, $restEndDate);
        $select->where($where);
        $select->group('user_points.restaurant_id');
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $data = $this->getDbTable()
                        ->setArrayObjectPrototype('ArrayObject')
                        ->getReadGateway()
                        ->selectWith($select)->toArray();
        if (!empty($data)) {
            return $data[0];
        } else {
            return 0;
        }
    }

    public function getRestaurantsTotalPointsDaily($restId, $startDate, $endDate) {
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->columns(array(
            'total_points_accured' => new Expression('SUM(points)'),
            'total_points_redeemed' => new Expression('SUM(redeemPoint)'),
        ));
        $select->join(array(
            'rs' => 'restaurant_servers'
                ), 'rs.user_id = user_points.user_id', array(
                ), $select::JOIN_INNER);
        $where = new Where ();
        $where->equalTo('user_points.restaurant_id', $restId);
        $where->between('user_points.created_at', $startDate, $endDate);
        $select->where($where);
        $select->group('user_points.restaurant_id');
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $data = $this->getDbTable()
                        ->setArrayObjectPrototype('ArrayObject')
                        ->getReadGateway()
                        ->selectWith($select)->toArray();
        if (!empty($data)) {
            return $data[0];
        } else {
            return 0;
        }
    }

}
