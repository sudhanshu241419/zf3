<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

class UserOrder extends AbstractModel {

    public $id;
    public $user_id;
    public $restaurant_id;
    public $fname;
    public $lname;
    public $state_code;
    public $phone;
    public $apt_suite;
    public $city;
    public $order_amount;
    public $deal_discount;
    public $order_type;
    public $created_at;
    public $updated_at;
    public $user_comments;
    public $restaurants_comments;
    public $special_checks;
    public $zipcode;
    public $payment_status;
    public $status;
    public $delivery_time;
    public $tax;
    public $tip_amount;
    public $tip_percent;
    public $delivery_charge;
    public $delivery_address;
    public $frozen_status;
    public $user_sess_id;
    public $stripes_token;
    public $card_number;
    public $name_on_card;
    public $card_type;
    public $expired_on;
    public $billing_zip;
    public $payment_receipt;
    public $order_type1;
    public $order_type2;
    public $email;
    public $miles_away;
    public $stripe_card_id;
    public $user_card_id;
    public $total_amount;
    public $new_order = 1;
    public $approved_by = 0;
    public $is_read = 0;
    public $crm_update_at = '';
    public $host_name;
    public $is_deleted = 0;
    public $crm_comments = '';
    public $is_reviewed = 0;
    public $review_id;
    public $stripe_charge_id;
    protected $_primary_key = 'id';
    public $promocode_discount;
    public $deal_id;
    public $deal_title;
    public $order_pass_through = 0;
    public $encrypt_card_number = NULL;
    public $user_ip = NULL;
    public $address = NULL;
    public $longitude = 0;
    public $latitude = 0;
    public $city_id = 0;
    public $pay_via_point;
    public $pay_via_card;
    public $redeem_point;
    public $cod = 0;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    /*     * ****************Get user total placed order by order id************** */

    public function getTotalPlacedOrder($userId) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(['total_order' => new Expression('COUNT(id)')]);
        $where = new Where();
        $where->NEST->in('status', array('placed', 'ordered', 'confirmed', 'delivered', 'arrived', 'archived', 'rejected', 'cancelled'))->UNNSET->OR->NEST->equalTo('order_type', 'Dinein')->UNNEST->UNNEST->AND->equalTo('user_id', $userId);
        $select->where($where);
        $totalOrder = $this->_tableGateway->selectWith($select)->current();
        return (int) $totalOrder['total_order'];
    }

    public function getArchiveOrderForNotification($oId = false, $current_date = false) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(['id']);
        $where = new Where();
        $where->equalTo('id', $oId);
        $where->notEqualTo('order_type', 'Dinein');
        $where->NEST->equalTo('status', 'archived')->
                        OR->NEST->equalTo('status', 'rejected')->AND->
                        lessThan('delivery_time', $current_date)->UNNEST->
                OR->NEST->equalTo('status', 'cancelled')->UNNEST->UNNEST;
        $select->where($where);
        return $this->_tableGateway->selectWith($select)->toArray();
    }

    public function getCurrentNotificationOrder($user_id, $today) {
        $output = [];
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(['id', 'created_at']);
        $select->where([
            'user_id' => $user_id
        ]);
        $select->where->greaterThan('delivery_time', $today);
        $select->order('delivery_time DESC');
        $currentNotification = $this->_tableGateway->selectWith($select)->toArray();
        if (!empty($currentNotification)) {
            $output['order_created_time'] = 'available';
        }
        return $output;
    }

    public function getCountUserOrders($userId, $status = NULL) {
        $select = new Select();
         $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'total_order' => new Expression('COUNT(id)'),
            'user_id'
        ));
        $select->where(array(
            'user_id' => $userId,
            'order_type1' => $status
        ));
        $select->where->notEqualto('order_type', 'Dinein');
        $totalOrder = $this->_tableGateway->selectWith($select)->toArray();
        return $totalOrder;
    }

}
