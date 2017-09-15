<?php

namespace City\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;

class City extends AbstractModel {

    public $id;
    public $state_id;
    public $country_id;
    public $city_name;
    public $neighbouring;
    public $locality;
    public $state_code;
    public $latitude;
    public $longitude;
    public $sales_tax;
    public $status;
    public $time_zone;
    public $city_name_alias;
    public $is_browse_only;
    public $seo;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function fetchAll($paginated = false) {
        return $this->_tableGateway->select();
    }

    public function cityDetails($cityId) {
        $options = [
            'columns' => [
                'id',
                'city_name',
                'state_code',
                'latitude',
                'longitude',
                'time_zone',
                'neighbouring'
            ],
            'where' => [
                'id' => $cityId
            ]
        ];
        return $this->find($options)->toArray();
    }

    public function getCityCurrentDateTime($city_id) {
        try {
            $options = array(
                'columns' => array(
                    'time_zone'
                ),
                'where' => array(
                    'id' => $city_id
                )
            );
            $result = $this->find($options)->toArray();
            $date = new \DateTime("now", new \DateTimeZone($result[0]['time_zone']));
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return 'error';
        }
    }

     public function cityAndStateDetails($city_id) {       
        $select = new Select ();        
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'city_id' => 'id',
            'nbd_cities' => 'neighbouring',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'city_name' => 'city_name',
            'locality' => 'locality',
            'is_browse_only'
        ));
        $select->join(array(
            'c' => 'countries'
                ), 'cities.country_id = c.id', array(
            'country_code' => 'country_short_name',            
                ), $select::JOIN_INNER);
        $select->join(array(
            's' => 'states'
                ), 'cities.state_id = s.id', array(
            'state_name' => 'state',
            'state_code'
                ), $select::JOIN_INNER);
        $select->where(array(
            'cities.id' => $city_id
        ));       
        $cityDetails = $this->_tableGateway->selectWith($select)->current()->getArrayCopy();        
        return $cityDetails;
    }

    public function getCity(array $options = array()) {
        return $this->find($options);
    }

}
