<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;

class UserAvatar extends AbstractModel {

    public $id;
    public $user_id;
    public $avatar_id;
    public $action_count;
    public $date_earned;
    public $total_earned;
    public $status;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function insert($data) {
        $writeGateway = $this->_tableGateway;
        if ($this->id) {
            $rowsAffected = $writeGateway->update($data, array('id' => $this->id));
        } else {
            $rowsAffected = $writeGateway->insert($data);
        }

        if ($rowsAffected) {
            return true;
        } else {
            return false;
        }
    }

}
