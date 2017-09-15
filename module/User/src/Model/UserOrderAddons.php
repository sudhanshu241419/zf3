<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class UserOrderAddons extends AbstractModel {
	public $id;
	public $user_order_detail_id;
	public $user_order_id;
	public $menu_addons_id;
	public $addons_name;
	public $addons_option;
	public $price;
	public $quantity;
	public $selection_type;
	public $menu_addons_option_id;
	public $priority;
	public $was_free;
	protected $_primary_key = 'id';
	protected $_db_table_name = 'User\Model\DbTable\UserOrderAddonsTable';
	public function addtoUserOrderAddons() {
		$data = $this->toArray ();
		
		$writeGateway = $this->getDbTable ()->getWriteGateway ();
		
		$rowsAffected = $writeGateway->insert ( $data );
		
		// Get the last insert id and update the model accordingly
		$lastInsertId = $writeGateway->getAdapter ()->getDriver ()->getLastGeneratedValue ();
		
		if ($rowsAffected >= 1) {
			return $lastInsertId;
		}
		return false;
	}
	public function getAllOrderAddon(array $options = array()){
		$this->getDbTable ()->setArrayObjectPrototype ( 'ArrayObject' );
		$orderAddonDetail = $this->find ( $options )->toArray ();
		return $orderAddonDetail;
	}

	public function getUserOrderAddons($orderItemId) {
        $select = new Select ();
        $where = New Where();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'menu_addons_id',
            'menu_addons_option_id',
            'quantity',
            'priority',
            'was_free'            
        ));

        $select->join(array(
            'ma' => 'menu_addons'
            ), 'user_order_addons.menu_addons_option_id=ma.id and user_order_addons.menu_addons_id=ma.addon_id', array(
            
            'price' => new \Zend\Db\Sql\Expression('IF(ma.price IS NULL,0,round(ma.price,2))') ,
            'price_description',
            'addon_option',
            'addon_status'=>'status'
            ), $select::JOIN_RIGHT);

       $where->equalTo('user_order_detail_id', $orderItemId);
       $select->where($where);
//     var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $alladdons = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();

        return $alladdons;
    }
}
