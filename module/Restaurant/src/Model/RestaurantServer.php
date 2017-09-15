<?php

namespace Restaurant\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\StaticFunctions;

class RestaurantServer extends AbstractModel {

    public $id;
    public $user_id;
    public $restaurant_id;
    public $code;
    public $date;
    public $status = 0;

    protected $_tableGateway;
    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function userDineAndMoreRestaurant($userId) {
        $tags = StaticFunctions::getServiceLocator()->get(Tags::class);
        $tagDetails = $tags->getTagDetailByName("dine-more");
        $joins [] = [
            'name' => [
                'r' => 'restaurants'
            ],
            'on' => 'restaurant_servers.restaurant_id = r.id',
            'columns' => [
                'restaurant_id' => 'id',
                'restaurant_name',
                'restaurant_image_name',
                'rest_code',
            ],
            'type' => 'inner'
        ];
        $joins[] = [
            'name' => [
                'rt' => 'restaurant_tags'
            ],
            'on' => 'restaurant_servers.restaurant_id = rt.restaurant_id',
            'columns' => ['tag_id', 'rest_short_url'],
            'type' => 'inner'
        ];

        $options = [
            'columns' => [
                'code'
            ],
            'where' => ['user_id' => $userId, 'r.closed' => 0, 'r.inactive' => 0, 'rt.status' => 1, 'rt.tag_id' => $tagDetails[0]['tags_id']],
            'joins' => $joins,
        ];
        $userDineAndMoreRestaurant = $this->find($options)->toArray();
        return $userDineAndMoreRestaurant;
    }
    
    public function registerRestaurantServer() {
        $data = $this->toArray();
                
        if (!$this->id) {
            $rowsAffected = $this->_tableGateway->insert($data);
            // Get the last insert id and update the model accordingly
            $lastInsertId = $this->_tableGateway->lastInsertValue;
        } else {
            $rowsAffected = $this->_tableGateway->update($data, array(
                'id' => $this->id
            ));
            $lastInsertId = $this->id;
        }

        if ($rowsAffected >= 1) {
            return $this->id = $lastInsertId;            
        }
        return false;
    }
    public function isUserRegisterWithRestaurant(){
        $joins[] = [
            'name' => [
                'rt'=>'restaurant_tags'
            ],
            'on' => 'restaurant_servers.restaurant_id = rt.restaurant_id',
            'columns' => ['tag_id','rest_short_url'],
            'type' => 'inner'
        ];
        $options = [
            'columns' => [
                'id'=>new \Zend\Db\Sql\Expression('count(restaurant_servers.id)')                
            ],
            'where' => ['user_id' => $this->user_id,'restaurant_servers.restaurant_id'=>$this->restaurant_id,'rt.status'=>1],
            'joins'=>$joins
        ];
        return $this->find($options)->toArray();  
    }
     public function isUserRegisterWithAnyRestaurant(){             
        $options = array(
            'columns' => array(
                'id'=>new \Zend\Db\Sql\Expression('count(id)')                
            ),
            'where' => array('user_id' => $this->user_id),
        );
        return $this->find($options)->toArray();  
    }

}
