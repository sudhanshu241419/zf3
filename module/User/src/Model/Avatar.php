<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;

class Avatar extends AbstractModel {

    public $id;
    public $avatar;
    public $name;
    public $type;
    public $avtar_image;
    public $message;
    public $temp_message;
    public $action;
    public $action_number;
    public $status;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function insert($data) {
        $writeGateway = $this->_tableGateway;
        $rowsAffected = $writeGateway->insert($data);
        if ($rowsAffected) {
            return true;
        } else {
            return false;
        }
    }

}
