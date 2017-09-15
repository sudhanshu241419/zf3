<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class PointSourceDetails extends AbstractModel {

    public $id;
    public $name;
    public $points;
    public $csskey;
    public $created_at;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function getPointSource(array $options = array()) {
        $point = $this->find($options)->toArray();
        return $point;
    }

    public function getPoint($ids) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'points',
            'name'
                )
        );
        $where = new Where();
        $where->in('id', $ids);
        $select->where($where);
        // var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $points = $this->_tableGateway->selectWith($select)->toArray();
        return $points;
    }

    public function getPointsSourceDetail($id = 0) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'id',
            'name',
            'points',
            'csskey',
            'created_at',
            'identifier'
        ));
        $res = $this->_tableGateway->selectWith($select)->toArray();
        $response = array();
        foreach ($res as $keys => $values) {
            $response [] = $values;
        }
        return $response;
    }

    public function getPointsSourceDetailApp($id = 0) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'id',
            'name',
            'points',
            'csskey',
            'created_at',
            'identifier'
        ));
        $where = new Where();
        $where->in('points_for', array('bt', 'ap'));
        $where->equalTo('dstatus', '1');
        $select->where($where);
        $select->order('dindex ASC');
        $res = $this->_tableGateway->selectWith($select)->toArray();
        $responseapp = array();
        foreach ($res as $keys => $values) {
            $responseapp [] = $values;
        }
        return $responseapp;
    }

    public function getPointSourceDetail(array $options = array()) {
        return $this->find($options)->current();
    }

    public function getPointsOnCssKey($key) {
        $points = $this->find(
                        array('columns' => array('id', 'points'),
                            'where' => array('identifier' => $key)))->current();
        if (is_object($points)) {
            $points->getArrayCopy();
        }
        return $points;
    }

}
