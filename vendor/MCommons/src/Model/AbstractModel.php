<?php

namespace MCommons\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Delete;

abstract class AbstractModel {

    protected $_tableGateway;
    protected $_db_table_name;
    protected $_primary_key = 'id';

    public function __construct(TableGatewayInterface $tableGateway) {
        $this->_tableGateway = $tableGateway;
    }

    /**
     * Return the Abstract DB Table Instance
     *
     * @return \MCommons\Model\DbTable\AbstractDbTable
     * @throws \Exception
     */
    public function getDbTable() {
      return $this->_tableGateway;
    }    
    
    /**
     * Exchange the data with model variables
     *
     * @param array $data        	
     * @return \MCommons\Model\AbstractModel
     */
    public function exchangeArray(array $data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    /**
     * get array of the variables of respective class
     *
     * @return multitype:
     */
    public function toArray() {
        $reflect = new \ReflectionClass($this);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $arr = array();
        foreach ($props as $refProp) {
            $arr [$refProp->name] = $this->{$refProp->name};
        }
        return $arr;
    }

    public function find(array $options = array()) {
        
        $select = new Select ();
        
        $select->from($this->_tableGateway->getTable());
        // Select required columns
        if (isset($options ['columns'])) {
            $select->columns($options ['columns']);
        }
        // Set the where condition
        if (isset($options ['where'])) {
            $select->where($options ['where']);
        }
        // set Predicate or condition in where condition
        if (isset($options ['orWhere'])) {
            $select->where($options ['orWhere'], \Zend\Db\Sql\Predicate\PredicateSet::OP_OR);
        }

        // Set the order type
        if (isset($options ['order']) && $options ['order']) {
            $select->order($options ['order']);
        }

        // Limit the number of output rows
        if (isset($options ['limit']) && $options ['limit']) {
            $select->limit($options ['limit']);
        }
        // Set the offset to start the result from
        if (isset($options ['offset']) && $options ['offset']) {
            $select->offset($options ['offset']);
        }

        // Set the GroupBy
        if (isset($options ['group']) && $options ['group']) {
            $select->group($options ['group']);
        }

        // Set the Like
        if (isset($options ['like']) && $options ['like']) {
            $select->where->like($options ['like'] ['field'], $options ['like'] ['like']);
        }

        // Set the Joins
        if (isset($options ['joins']) && $options ['joins'] && is_array($options ['joins'])) {
            foreach ($options ['joins'] as $join) {
                $select->join($join ['name'], $join ['on'], $join ['columns'], $join ['type']);
            }
        }       

        //echo $this->_tableGateway->getSql()->getSqlStringForSqlObject($select);     
        
        $results = $this->_tableGateway->selectWith($select);
        return $results;
    }

    public function getPlatform($type = 'READ') {
        
        if (strtoupper($type) == 'READ') {
            
            $name = \MCommons\Db\Adapter\ReadAdapter::class;
            //$service = new \RestFunctions\Db\Adapter\ReadAdapter('PDO');
            
            $sm = \MCommons\StaticFunctions::getServiceLocator();
            pr($s,1);
            
            $platform = $this->getDbTable()->getReadGateway()->getAdapter()->getPlatform();
            pr($platform,1);
        } else if (strtoupper($type) == 'WRITE') {
            $platform = $this->getDbTable()->getWriteGateway()->getAdapter()->getPlatform();
        } else {
            throw new \Exception("Please provie the platform type needed READ/WRITE");
        }
        return $platform;
    }

    public function abstractUpdate($data, $predicate) {
        $update = new Update ();
        $update->table($this->getDbTable()->getTableName());
        $update->set($data);
        $update->where($predicate);
        $this->getDbTable()->getWriteGateway()->updateWith($update);
    }

    public function abstractDelete($predicate) {
        $delete = new Delete();
        $delete->from($this->getDbTable()->getTableName());
        $delete->where($predicate);
        $this->getDbTable()->getWriteGateway()->deleteWith($delete);
    }

    public static function to_utf8($in) {
        if (is_array($in)) {
            foreach ($in as $key => $value) {
                $out[self::to_utf8($key)] = self::to_utf8($value);
            }
        } elseif (is_string($in)) {
            if (mb_detect_encoding($in) != "UTF-8")
                return utf8_encode($in);
            else
                return $in;
        } else {
            return $in;
        }
        return $out;
    }
     public function connectionObject(){
        return $this->_tableGateway->getAdapter()->getDriver()->getConnection();
    }

}
