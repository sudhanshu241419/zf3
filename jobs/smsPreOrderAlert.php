<?php
use MCommons\StaticOptions;
use User\Model\UserOrder;
use Zend\Db\Sql\Predicate\Expression;
use Restaurant\OrderFunctions;


defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$orderModel = new UserOrder();
$orderFunctions = new OrderFunctions();
$userFunctions = new User\UserFunctions();
$userNotificationModel = new \User\Model\UserNotification();
define('PROTOCOL', 'http://');
$joins [] = array(
    'name' => array(
        'uo' => 'restaurants'
    ),
    'on' => new Expression("(user_orders.restaurant_id = uo.id)"),
    'columns' => array(
        'restaurant_name',
        'city_id',
    ),
    'type' => 'inner'
);
$joins [] = array(
    'name' => array(
        'ci' => 'cities'
    ),
    'on' => new Expression("(ci.id = uo.city_id)"),
    'columns' => array(
        'time_zone'
    ),
    'type' => 'inner'
);
$options = array(
    'columns' => array(
        '*'
    ),
    'joins' => $joins,
    'where' => array('user_orders.status = "Confirmed" and user_orders.order_type = "Delivery" and user_orders.cronsmsupdate="0" and uo.order_pass_through = 0')
);
$orderModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$allOrders = $orderModel->find($options)->toArray();
$cronUpdate=0;
if (!empty($allOrders)) {
    foreach ($allOrders as $key => $value) {
            $arrivedTime = \DateTime::createFromFormat(StaticOptions::MYSQL_DATE_FORMAT, $value ['delivery_time'], new \DateTimeZone($value ['time_zone']));
            $created_at = \DateTime::createFromFormat(StaticOptions::MYSQL_DATE_FORMAT, $value ['created_at'], new \DateTimeZone($value ['time_zone']));
            $currentTimeNew = StaticOptions::getRelativeCityDateTime(array(
                                'restaurant_id' => $value ['restaurant_id']
                            ))->format(StaticOptions::MYSQL_DATE_FORMAT);
            $differenceOfTimeInMin = round(abs(strtotime($arrivedTime->format("Y-m-d H:i:s"))-strtotime($currentTimeNew))/60); 
            $delivery_time = explode(" ", $value ['delivery_time']);
            $webUrl = PROTOCOL . $config ['constants'] ['web_url'];
            $deliveryTimestamp = strtotime($currentTimeNew) + 45 * 60;
                $actualDeliveryDate = date('Y-m-d', $deliveryTimestamp);
                $actualDeliveryTime = date('H:i', $deliveryTimestamp);
                $deliveryDateTime = date('Y-m-d H:i:s', $deliveryTimestamp);
            $dateOfOrder = StaticOptions::getFormattedDateTime($currentTimeNew, 'Y-m-d H:i:s', 'D, M j, Y');
            $timeOfOrder = StaticOptions::getFormattedDateTime($currentTimeNew, 'Y-m-d H:i:s', 'h:i A');
            $dateTimeOfOrder = $dateOfOrder . ' at ' . $timeOfOrder;
            $dateOfDelivery = StaticOptions::getFormattedDateTime($deliveryDateTime, 'Y-m-d H:i:s', 'D, M j, Y');
            $timeOfDelivery = StaticOptions::getFormattedDateTime($deliveryDateTime, 'Y-m-d H:i:s', 'h:i A');
            $dateTimeOfDelivery = $dateOfDelivery . ' at ' . $timeOfDelivery;
            $order_status = $value['status'];
            $address = "";
            if (isset($value['delivery_address']) && isset($value['city']) && isset($value['state_code']) && isset($value['zipcode'])) {
                $address = $value['delivery_address'] . ', ' . $value['city'] . ', ' . $value['state_code'] . ', ' . $value['zipcode'];
            }
                $userOrderDetailModel = new \User\Model\UserOrderDetail();
                $userOrderItem = $userOrderDetailModel->getAllOrderDetail(array(
                    'columns' => array(
                        'id',
                        'item_name' => 'item',
                        'item_id',
                        'item_price_id',
                        'quantity' => 'quantity',
                        'unit_price' => 'unit_price',
                        'total_item_amt' => 'total_item_amt',
                        'special_instruction' => 'special_instruction',
                        'item_price_desc' => 'item_price_desc'
                    ),
                    'where' => array(
                        'user_order_id' => $value['id']
                    )
                ));
                $userOrderData['order_details'] = $userOrderItem;
                $userOrderAddonsModel = new \User\Model\UserOrderAddons();
                
                $i = 0;
                foreach ($userOrderData['order_details'] as $key1 => $itemvalue) {
                    
                    $orderItemId = $userOrderData['order_details'][$i]['id'];
                    
                    $addon = $userOrderAddonsModel->getAllOrderAddon(array(
                        'columns' => array(
                            'menu_addons_id',
                            'menu_addons_option_id',
                            'addons_option',
                            'price',
                            'quantity',
                        	'priority',
                        	'was_free'                        	
                        ),
                        'where' => array(
                            'user_order_detail_id' => $orderItemId
                        )
                    ));
                    $cc = array();
                    $j=0;
                    
                    foreach ($addon as $key => $result) {
                        
                        if($result['addons_option']=='None'){
                            continue;
                        }else{
                        $addons['addon_name'] = $userFunctions->to_utf8($result['addons_option']);
                        $addons['addon_price'] = $result['price'];
                        $addons['menu_addons_id'] = $result['menu_addons_id'];
                        $addons['menu_addons_option_id'] = $result['menu_addons_option_id'];
                        $addons['addon_quantity'] = $result['quantity'];
                        $addons['addon_total'] = number_format($result['price'] * $result['quantity'], 2);
                        $addons['priority']=$result['priority'];
                        $addons['was_free']=$result['was_free'];
                        $cc[$j] = $addons;
                        $j++; 
                        }
                    }
                    $totalQuantity = $userOrderData['order_details'][$i]['quantity'];
                    $totalUnitPrice = $userOrderData['order_details'][$i]['unit_price'];
                    $totalPrice = number_format($totalQuantity * $totalUnitPrice,2);
                    $userOrderData['order_details'][$i]['total_price'] = $totalPrice;
                    $userOrderData['order_details'][$i]['item_name'] = $userFunctions->to_utf8($userOrderData['order_details'][$i]['item_name']);
                  
                    if (! empty($cc)) {
                        $userOrderData['order_details'][$i]['addon'] = $cc;
                        $cc = array();
                    } else {
                        $userOrderData['order_details'][$i]['addon'] = array();
                    }
                    $i ++;
                }
                $orderSubTotal = 0;
                $orderSubTotal = (float) $value['order_amount'];
                
                $orderTax = (float) $value['tax'];
                if (is_numeric($orderTax) & $orderTax != 0) {
                    $value['tax'] = number_format($orderTax, 2);
                    $orderSubTotal = $orderSubTotal + $orderTax;
                }
                $orderTip = (float) $value['tip_amount'];
                if (is_numeric($orderTip) & $orderTip != 0) {
                    $value['tip_amount'] = number_format($orderTip, 2);
                    $orderSubTotal = $orderSubTotal + $orderTip;
                }
                $orderDelCharge = (float) $value['delivery_charge'];
                if (is_numeric($orderDelCharge) & $orderDelCharge != 0) {
                    $value['delivery_charge'] = number_format($orderDelCharge, 2);
                    $orderSubTotal = $orderSubTotal + $orderDelCharge;
                }
                $orderDiscount = (float) $value['deal_discount'];
                if (is_numeric($orderDiscount) & $orderDiscount != 0) {
                    $value['deal_discount'] = number_format($orderDiscount, 2);
                    $orderSubTotal = $orderSubTotal - $orderDiscount;
                }
                
            $data = array(
                'name' => $value['fname'],
                'hostName' => $webUrl,
                'restaurantName' => $value['restaurant_name'],
                'orderType' => ($value['order_type1'] == 'I') ? 'Individual ' . ucwords($value['order_type']) : 'Group ' . ucwords($value['order_type']) . ucwords($value['order_type']),
                'receiptNo' => $value['payment_receipt'],
                'timeOfOrder' => $dateTimeOfOrder,
                'timeOfDelivery' => $dateTimeOfDelivery,
                'orderData' => $orderFunctions->makeOrderForMail($userOrderData['order_details'], $value['restaurant_id'], $order_status, $value['order_amount']),
                'subtotal' => $value['order_amount'],
                'discount' => $value['deal_discount'],
                'tax' => $value['tax'],
                'tipAmount' => $value['tip_amount'],
                'total' => $orderSubTotal,
                'cardType' => $value['card_type'],
                'cardNo' => $value['card_number'],
                'expiredOn' => $value['expired_on'],
                'email' => $value['email'],
                'specialInstructions' => $value['special_checks'],
                'type' => ucwords($value['order_type']),
                'address' => $address,
                'onlyDate' => $dateOfDelivery,
                'onlyTime' => $timeOfDelivery,
                'deliveryCharge' => $value['delivery_charge'],
                'city' => isset($value['city']) ? $value ['city'] : "",
                'state' => isset($value['state_code']) ? $value['state_code'] : "",
                'phone' => isset($value['phone']) ? $value['phone'] : "",
                'zip' => isset($value['zipcode']) ? $value['zipcode'] : "",
                'orderTime' => $timeOfOrder,
                'orderDate' => $dateOfOrder,
                'dealDiscount' => $value['deal_discount'],
                'promocodeDiscount' => $value['promocode_discount'],
                'order_pass_through' => $value['order_pass_through'],
            );
            if(strtotime($currentTimeNew) < strtotime($arrivedTime->format("Y-m-d H:i:s")))
            {
               //if (($differenceOfTimeInMin > 55)&&($differenceOfTimeInMin < 61)) {
                $userFunctions->reminderOrderMail($data);
                $cronUpdate = 1;
                }
                if($cronUpdate==1){
                    //$orderModel->updateCronPreOrder($value['id']);
                }
            //}
    }
}