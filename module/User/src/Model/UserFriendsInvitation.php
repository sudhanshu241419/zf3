<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class UserFriendsInvitation extends AbstractModel {

    public $id;
    public $user_id;
    public $name;
    public $email;
    public $source;
    public $token;
    public $created_on;
    public $expired_on;
    public $status;
    public $invitation_status;

    const ACCEPTED = 1;
    const INVITE = 0;

    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        $this->_tableGateway = $tableGateway;
        parent::__construct($tableGateway);
    }

    public function createUserInvitation($data) {

        $dataUpdated = $this->_tableGateway->insert($data);
        $insertedId = $this->_tableGateway->lastInsertValue;
        if ($dataUpdated == 0) {
            throw new \Exception("Invalid Data provided", 500);
        } else {
            return $insertedId;
        }
    }

    public function getUserInvitationList($userEmail, $orderby) {
        $order = 'first_name ASC';
        if ($orderby == 'name') {
            $order = 'first_name ASC';
        } elseif ($orderby == 'email') {
            $order = 'email ASC';
        } else {
            $order = 'created_on DESC';
        }
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(['id' => new \Zend\Db\Sql\Expression('user_invitations.id')]);
        $select->join([
            'user' => 'users'
                ], 'user.id =  user_invitations.user_id', [
            'user_id' => 'id',
            'first_name',
            'last_name',
            'email',
            'display_pic_url',
            'city_id'
                ], $select::JOIN_INNER);
        $where = new Where ();
        $where->equalTo('user_invitations.email', $userEmail);
        $where->equalTo('user_invitations.invitation_status', 0);
        $select->where($where);
        $select->order($order);
        $select->group('user_invitations.user_id');
        $invitation = $this->_tableGateway->selectWith($select)->toArray();
        return $invitation;
    }

    public function getComingInvitationList($userId, $orderby) {
        $order = 'first_name ASC';
        if ($orderby == 'name') {
            $order = 'first_name ASC';
        } elseif ($orderby == 'email') {
            $order = 'email ASC';
        } else {
            $order = 'created_on DESC';
        }
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array('id' => new \Zend\Db\Sql\Expression('user_invitations.id'), 'email'));
        $select->join(array(
            'user' => 'users'
                ), 'user.email =  user_invitations.email', array(
            'user_id' => 'id',
            'first_name',
            'last_name',
            'display_pic_url',
            'city_id'
                ), $select::JOIN_INNER);
        $where = new Where ();
        $where->equalTo('user_invitations.user_id', $userId);
        $where->equalTo('user_invitations.invitation_status', 0);
        $select->where($where);
        $select->order($order);
        $select->group('user_invitations.user_id');
        $select->group('id');
        $comingInvitation = $this->_tableGateway->selectWith($select)->toArray();

        return $comingInvitation;
    }

    /*
     * This function searches to check if provided email id is invited by given
     * user id for connection
     * @args:
     * $userId: user id who invited the user
     * $email: invited email id
     * */

    public function isUserInvited($userId, $email) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array('id', 'created_on', 'status'));
        $where = new Where ();
        $where->equalTo('user_invitations.user_id', $userId);
        $where->equalTo('user_invitations.invitation_status', 0);
        $where->equalTo('user_invitations.email', $email);
        $select->where($where);
        $pendingInvitation = $this->_tableGateway->selectWith($select)->toArray();
        return $pendingInvitation;
    }

    public function getUserInvitationListForSugetion($userEmail, $userId) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array('email'));
        $where = new Where ();
        $where->equalTo('email', $userEmail);
        $where->equalTo('user_id', $userId);
        $select->where($where);
//        var_dump($select->getSqlString($this->getPlatform('READ')));
//        die;
        $invitation = $this->_tableGateway->selectWith($select)->toArray();
        return $invitation;
    }

    public function getSugetionListByPhone($userEmail, $userId) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(['email']);
        $where = new Where ();
        $where->equalTo('email', $userEmail);
        $where->equalTo('user_id', $userId);
        $where->in('invitation_status', [0, 1]);
        $select->where($where);
//        var_dump($select->getSqlString($this->getPlatform('READ')));
//        die;
        $invitation = $this->_tableGateway->selectWith($select)->toArray();
        return $invitation;
    }

    public function getUserAllInvitation($userId = false) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array('id', 'email'));
        $select->where(array('user_id' => $userId, 'assignMuncher' => '0', 'invitation_status' => '0'));
        $totalCheckin = $this->_tableGateway->selectWith($select);
        return $totalCheckin->toArray();
    }

    public function updateMuncher($data) {
        $this->_tableGateway->update($data, array(
            'id' => $this->id
        ));
        return true;
    }

    public function unfriendInvitation($id = false, $email = false) {
        if ($id != '' && $id != NULL) {
            $data = array('invitation_status' => 3);
            $this->_tableGateway->update($data, array(
                'id' => $id
            ));
        }
        return true;
    }

    public function unfriendUserInvitation($id = false, $email = false) {
        if ($id != '' && $id != NULL) {
            $data = array('invitation_status' => 3);
            $this->_tableGateway->update($data, array(
                'user_id' => $id, 'email' => $email
            ));
        }
        return true;
    }

    public function updateUserInvitation($where = false) {
        if ($where != '') {
            $data = array('invitation_status' => 0);
            $this->_tableGateway->update($data, $where);
        }
        return true;
    }

    public function getInvitatioExist($userId, $email) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array('invitation_status'));
        $where = new Where ();
        $where->equalTo('user_invitations.user_id', $userId);
        $where->equalTo('user_invitations.email', $email);
        $select->where($where);
        $existInvitation = $this->_tableGateway->selectWith($select)->toArray();
        if ($existInvitation) {
            return $existInvitation;
        } else {
            return false;
        }
    }

    public function updateCron($id = false) {
        $this->_tableGateway->update(array('cronUpdate' => 1), array(
            'id' => $id
        ));
        return true;
    }

    public function getReffInvitatioExist($userId, $email) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array('invitation_status', 'id', 'user_id', 'email'));
        $where = new Where ();
        $where->equalTo('user_invitations.user_id', $userId);
        $where->equalTo('user_invitations.email', $email);
        $select->where($where);
        $existInvitation = $this->_tableGateway->selectWith($select)->toArray();
        if ($existInvitation) {
            return $existInvitation;
        } else {
            return false;
        }
    }

    public function createReffUserInvitation($data) {
        $dataInserted = $this->_tableGateway->insert($data);
        $insertedId = $this->_tableGateway->lastInsertValue;
        if ($insertedId) {
            return $insertedId;
        } else {
            return false;
        }
    }

}
