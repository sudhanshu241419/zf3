<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;

class UserActionSettings extends AbstractModel {

    public $id;
    public $user_id;
    public $order;
    public $reservation;
    public $bookmarks;
    public $checkin;
    public $muncher_unlocked;
    public $upload_photo;
    public $reviews;
    public $tips;
    public $email_sent;
    public $notification_sent;
    public $sms_sent;
    public $created_at;
    public $updated_at;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function insert() {
        $data = $this->toArray();
        $rowsAffected = $this->_tableGateway->insert($data);
        if ($rowsAffected) {
            $this->id = $this->_tableGateway->lastInsertValue;
            $data = $this->toArray();
            unset($data['updated_at'], $data['created_at']);
            return $data;
        } else {
            return false;
        }
    }

    public function update() {
        $data = $this->toArray();
        unset($data['created_at']);
        $rowAffected = $this->_tableGateway->update($data, [
            'id' => $this->id
                ]);

        if ($rowAffected) {
            unset($data['updated_at']);
            return $data;
        } else {
            return false;
        }
    }

    public function userActionSettings(array $options = []) { //select
        return $this->find($options)->toArray();
    }

}
