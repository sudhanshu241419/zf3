<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

class ActivityFeed extends AbstractModel {

    public $id;
    public $feed_type_id;
    public $user_id;
    public $feed;
    public $feed_for_others;
    public $event_date_time;
    public $added_date_time;
    public $status;
    public $privacy_status = 0;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        $this->_tableGateway = $tableGateway;
        parent::__construct($tableGateway);
    }

    public function insert($data) {
        $rowsAffected = $this->_tableGateway->insert($data);
        if ($rowsAffected) {
            \MCommons\StaticFunctions::resquePush($data, "activityLog");
            return true;
        } else {
            return false;
        }
    }

    public function updatePrivacyStatus($data, $where) {
        $rowsAffected = $this->_tableGateway->update($data, $where);
        if ($rowsAffected) {
            return true;
        } else {
            return false;
        }
    }

    public function getRestaurantUserActivity($userId, $restId) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'event_date_time',
        ));
        $where = new Where ();
        $where->equalTo('user_id', $userId);
        $where->equalTo('status', '1');
        $select->where($where);
        $select->order('event_date_time DESC');
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $data = $this->_tableGateway->selectWith($select)->current();
        if (empty($data)) {
            return '';
        } else {
            return $data['event_date_time'];
        }
    }

    public function getRestaurantActiveMembers($restId, $ids, $restStartDate, $restEndDate) {
        $feedTypes = array('1', '4', '9');
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'id',
            'feed',
        ));
        $where = new Where ();
        $where->equalTo('activity_feed.status', '1');
        $where->in('activity_feed.feed_type_id', $feedTypes);
        $where->in('activity_feed.user_id', $ids);
        $where->between('event_date_time', $restStartDate, $restEndDate);
        $select->where($where);
        $select->order('event_date_time DESC');
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $data = $this->_tableGateway->selectWith($select)->toArray();
        $count = 0;
        if (!empty($data)) {
            foreach ($data as $value) {
                if (json_decode($value['feed'])->restaurant_id == $restId) {
                    $count++;
                }
            }
        }
        return $count;
    }

    public function getRestaurantInactiveMembers($restId) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array());
        $select->join(array(
            'rs' => 'restaurant_servers'
                ), new Expression('rs.user_id != activity_feed.user_id'), array(
            'user_id',
                ), $select::JOIN_INNER);
        $where = new Where ();
        $where->equalTo('rs.restaurant_id', $restId);
        $select->where($where);
        //$select->order('event_date_time DESC');
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $data = $this->_tableGateway->selectWith($select)->toArray();
        $count = 0;
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $count++;
            }
        }
        return $count;
    }

}
