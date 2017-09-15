<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\TableGateway\TableGatewayInterface;


class UserFeedback extends AbstractModel {

    public $id;
    public $review_id;
    public $user_id;
    public $feedback;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function addFeedback() {
        $data = $this->toArray();
        $writeGateway = $this->_tableGateway;
        $rowsAffected = $writeGateway->insert($data);
        return $rowsAffected;
    }

}
