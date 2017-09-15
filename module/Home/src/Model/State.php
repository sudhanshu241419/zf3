<?php

namespace Home\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\TableGateway\TableGatewayInterface;

class State extends AbstractModel {

    public $id;
    public $country_id;
    public $state;
    public $state_code;
    public $zone;
    public $status;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function getStates() {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'id',
            'state',
            'state_code',
            'zone',
            'status'
        ));
        $select->join(array(
            'c' => 'cities'
                ), 'c.state_id = states.id', array(
            'cty_id' => 'id',
            'cty_name' => 'city_name',
            'cty_latitude' => 'latitude',
            'cty_longitude' => 'longitude',
            'cty_browseonly' => 'is_browse_only',
            'cty_status' => new Expression('c.status'),
                ), $select::JOIN_INNER);
        $select->where(array(
            'states.status' => 1,
            'c.status' => 1,
            'c.latitude != ?' => 0,
            'c.longitude != ?' => 0
        ));
        $stateDetail = $this->_tableGateway->selectWith($select)->toArray();
        return $stateDetail;
    }
}
