<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\TableGateway\TableGatewayInterface;

class UserDeals extends AbstractModel {

    public $user_id;
    public $deal_id;
    public $date;
    
    protected $_tableGateway;
    public function __construct(TableGatewayInterface $tableGateway) 
    {
        $this->_tableGateway = $tableGateway;  
        parent::__construct($tableGateway);        
    }
    
    public function saveAbandonedCart($data) 
    {
        $this->_tableGateway->insert($data);
        return true;
    }

    public function getUserDealsResIds($user_id = 0) 
    {    
        $joinsResDeals      = array();
        $joinsResDeals []   = array(
            'name' => array(
                'rdc' => 'restaurant_deals_coupons'
            ),
            'on' => 'user_deals.deal_id = rdc.id',
            'columns' => array(
                'res_id' => 'restaurant_id'
            ),
            'type' => 'inner'
        );
        $options = array(
            'columns' => array(
                'deal_id',
            ),
            'where' => array(
                'user_id' => $user_id,
            ),
            'joins' => $joinsResDeals,
        );
        
        $resIds = [];
        foreach($this->find($options)->toArray() as $k => $v)
        {
            $resIds[] = $v['res_id'];
        }
        return $resIds;
    }

}
