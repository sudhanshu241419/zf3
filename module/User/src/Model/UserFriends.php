<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class UserFriends extends AbstractModel {

    public $id;
    public $user_id;
    public $to_id;
    public $restaurant_id;
    public $message;
    public $msg_status;
    public $reservation_id;
    public $friend_email;
    public $user_type;
    public $created_on;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    /**
     * Check if $user is friend of $other_user
     * @param int $user_id
     * @param int $other_user_id
     * @return int 0=not friend, 1=freind, 2=invitation pending
     */
    public function isFriend($user_id, $other_user_id) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $where = new Where ();
        $where->equalTo('user_id', $user_id);
        $where->equalTo('friend_id', $other_user_id);
        $where->equalTo('status', 1);
        $select->where($where);
        //pr($this->_tableGateway->getSql()->getSqlStringForSqlObject($select));
        $friends = $this->_tableGateway->selectWith($select)->toArray();
        return count($friends) > 0 ? 1 : 0;
    }

    public function insertFriends($data) {
        $this->_tableGateway->insert($data);
        $insertedId = $this->_tableGateway->lastInsertValue;
        if ($insertedId) {
            return $insertedId;
        } else {
            return false;
        }
    }

    /**
     * Get User Friends List
     *
     * @param unknown $userId        	
     * @param unknown $orderby        	
     * @return Array
     */
    public function getUserFriendList($userId, $orderby) {
        $order = 'first_name ASC';
        if ($orderby == 'name') {
            $order = 'first_name ASC';
        } elseif ($orderby == 'email') {
            $order = 'email ASC';
        } else {
            $order = 'user.created_at DESC';
        }
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns([
            'friends_on' => 'created_on',
            'friend_id', 'user_id'
        ]);
        $select->join([
            'user' => 'users'
                ], 'user.id =  user_friends.friend_id', [
            'first_name',
            'last_name',
            'email',
            'display_pic_url',
            'created_at',
            'city_id'
                ], $select::JOIN_INNER);
        $where = new Where ();
        $where->equalTo('user_friends.user_id', $userId);
        $where->equalTo('user_friends.status', 1);
        $select->where($where);
        $select->order($order);
        $select->group('friend_id');
        $friends = $this->_tableGateway->selectWith($select)->toArray();
        return $friends;
    }

    public function getTotalUserFriends($userId) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns([
            'total_friend' => new Expression('COUNT(id)')
        ]);
        $select->where([
            'user_id' => $userId,
            'status' => 1
        ]);
        $totalFriend = $this->_tableGateway->selectWith($select)->current();
        return $totalFriend;
    }

}
