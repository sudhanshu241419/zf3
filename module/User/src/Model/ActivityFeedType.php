<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;

class ActivityFeedType extends AbstractModel {

    public $id;
    public $feed_type;
    public $feed_message;
    public $feed_message_others;
    public $status;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        $this->_tableGateway = $tableGateway;
        parent::__construct($tableGateway);
    }

    public function insert($data) {

        $rowsAffected = $this->_tableGateway->insert($data);
        $lastInsertId = $writeGateway->getAdapter()->getDriver()->getLastGeneratedValue();
        return $lastInsertId;
    }
    
    public function activityFeedType($options){
        $activityFeedType = $this->find($options)->toArray();
        if($activityFeedType){
            return $activityFeedType;
        }
        return false;
    }

}
