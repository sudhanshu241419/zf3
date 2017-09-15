<?php
namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

class UserOrderDetail extends AbstractModel
{

    public $id;

    public $user_id;

    public $order_addon_data;

    public $user_order_id;

    public $item;

    public $quantity;

    public $unit_price;

    public $item_description;

    public $total_item_amt;

    public $item_id;

    public $item_price_id;

    public $status;

    public $order_token;

    public $special_instruction;
    
    public $item_price_desc;

    protected $_primary_key = 'id';

    protected $_db_table_name = 'User\Model\DbTable\UserOrderDetailTable';

    public function addtoUserOrderDetail()
    {
        $data = $this->toArray();
        
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->insert($data);
        
        // Get the last insert id and update the model accordingly
        $lastInsertId = $writeGateway->getAdapter()
            ->getDriver()
            ->getLastGeneratedValue();
        
        if ($rowsAffected >= 1) {
            return $lastInsertId;
        }
        return false;
    }

    public function getAllOrderDetail(array $options = array())
    {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $orderDetail = $this->find($options)->toArray();
        return $orderDetail;
    }

    public function getOrderDetailItems($orderId)
    {
        $select = new Select();
        $select->from($this->getDbTable()
            ->getTableName());
        $select->columns(array(
            'id',
            'user_order_id',
            'item',
            'quantity',
            'unit_price',
            'special_instruction',
            'total_item_amt'
        )
        );
        $where = new Where();
        $where->equalTo('user_order_details.user_order_id', $orderId);
        $select->where($where);
        $orderData = $this->getDbTable()
            ->setArrayObjectPrototype('ArrayObject')
            ->getReadGateway()
            ->selectWith($select)
            ->toArray();
        return $orderData;
    }
    
    public function getUserOrderItemId($order=false){
       $options=array('columns' => array('item_id'),'where'=>array('user_order_id'=>$order)); 
       $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
       $menus = $this->find($options)->toArray();
       return $menus;
    }
    public function getRestaurantTotalOrderItems($orderIds) {
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->columns(array(
            'total_items' => new Expression('COUNT(id)'),
        ));
        $where = new Where ();
        $where->in('user_order_id', $orderIds);
        $select->where($where);
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $data = $this->getDbTable()
                        ->setArrayObjectPrototype('ArrayObject')
                        ->getReadGateway()
                        ->selectWith($select)->toArray();
        if (!empty($data)) {
            return $data[0]['total_items'];
        } else {
            return '';
        }
    }
    public function getRestaurantMostPopularItems($restId,$restStartDate,$restEndDate) {
        $status = array('confirmed', 'delivered', 'arrived', 'archived','cancelled','rejected');
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'item',
            'total_items' => new Expression('COUNT(user_order_details.item_id)')
        ));
        $select->join(array(
            'uo' => 'user_orders'
                ), 'user_order_details.user_order_id = uo.id', array(
                ), $select::JOIN_INNER);
        $where = new Where ();
        $where->equalTo('uo.restaurant_id', $restId);
        $where->in('uo.status', $status);
        $where->between('uo.created_at', $restStartDate, $restEndDate);
        $select->where($where);
        $select->group('user_order_details.item_id');
        $select->order('total_items DESC');
        $select->limit('20');
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $data = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        if (empty($data)) {
            return '';
        } else {
            return $data;
        }
    }

}
