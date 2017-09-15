<?php

/*
 * This file is use to send notification to checkin user friend
 */

use MCommons\StaticOptions;
use Zend\Db\Sql\Predicate\Expression;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$userInvitationModel = new \User\Model\UserInvitation();
$joins [] = array(
    'name' => array(
        'res' => 'restaurants'
    ),
    'on' => new Expression("(res.id = user_reservation_invitation.restaurant_id)"),
    'columns' => array(
        'rest_code', 'restaurant_name'
    ),
    'type' => 'inner'
);
$joins [] = array(
    'name' => array(
        'reser' => 'user_reservations'
    ),
    'on' => new Expression("(reser.id = user_reservation_invitation.reservation_id)"),
    'columns' => array(
        'restaurant_name',
        'restaurant_id',
        'time_slot',
        'email',
        'first_name',
        'order_id',
        'party_size',
        'receipt_no', 'status'
    ),
    'type' => 'inner'
);
$joins [] = array(
    'name' => array(
        's' => 'users'
    ),
    'on' => new Expression("(s.id = user_reservation_invitation.user_id)"),
    'columns' => array(
        'senderFname' => 'first_name', 'senderLname' => 'last_name'
    ),
    'type' => 'inner'
);
$joins [] = array(
    'name' => array(
        'r' => 'users'
    ),
    'on' => new Expression("(r.id = user_reservation_invitation.to_id)"),
    'columns' => array(
        'receiverFname' => 'first_name', 'receiverLname' => 'last_name'
    ),
    'type' => 'inner'
);
$options = array(
    'columns' => array(
        'id', 'message', 'friend_email', 'reservation_id', 'user_id', 'restaurant_id'
    ),
    'joins' => $joins,
    'where' => array('user_reservation_invitation.cronUpdate = "0"')
);
$userInvitationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$allInvitations = $userInvitationModel->find($options)->toArray();
//pr($allInvitations,1);
//above find the recent invitation which have (coulmn) cronUpdate=0

if (count($allInvitations) > 0) {
    $user_function = new \User\UserFunctions();
    //echo date('H:i:s');
    foreach ($allInvitations as $key => $invitation) { //pr($invitation,1);
        $host_name = $invitation['first_name'];
        $friend_name = $invitation['receiverFname'];
        $mailText = $invitation['message'];
        $accept_url_template = '<img style="margin-top:8px;" src="' . TEMPLATE_IMG_PATH . 'accept-the-invitation.gif" alt="Accept Invitation" width="209" height="33" border="0">';
        $restaurantName = $invitation['restaurant_name'];
        $reservationDate = StaticOptions::getFormattedDateTime($invitation['time_slot'], 'Y-m-d H:i:s', 'D, M d, Y');
        $reservationTime = StaticOptions::getFormattedDateTime($invitation['time_slot'], 'Y-m-d H:i:s', 'h:i A');
        $webUrl = PROTOCOL . $config['constants']['web_url'];
        $acceptUrl = $webUrl;
        $recievers = array(
            $invitation['friend_email']
        );

        //for prepaid reservation
        if (isset($invitation['order_id']) && !empty($invitation['order_id']) && $invitation['order_id'] != NULL) {
            $template = "is_buying_food_you_in";
            $layout = 'email-layout/default_new';
            $subject = "You, Us, Them, & Food";
            $userTokenAccept = base64_encode($invitation['friend_email'] . '##' . $invitation['user_id'] . '##1##' . $invitation['reservation_id']);
            $userTokenDeny = base64_encode($invitation['friend_email'] . '##' . $invitation['user_id'] . '##2##' . $invitation['reservation_id']);
            $acceptUrl = $webUrl;
            //$deny_link = WEB_URL . 'wapi/user/reservationdecline/' . $invitation['id'] . "?token=" . $data['token'] . "&orderid=" . $invitation['order_id'];
            //$accept_url_template = '<br><a href="' . $accept_link . '" target="_blank"><img style="margin-top:8px;" src="' . TEMPLATE_IMG_PATH . 'accept-the-invitation.gif" alt="Accept Invitation" width="209" height="33" border="0"></a>';
            $accept_url_template = '<img style="margin-top:8px;" src="' . TEMPLATE_IMG_PATH . 'accept-the-invitation.gif" alt="Accept Invitation" width="209" height="33" border="0">';
            // $deny_url_template = '<a href="' . $deny_link . '"><img style="margin-top:8px; margin-left:15px;" src="' . TEMPLATE_IMG_PATH . 'deny-the-invitation.gif" alt="Deny the Invitation" width="209" height="33" border="0"></a>';
            $orderFunctions = new \Restaurant\OrderFunctions();
            $WebUserOrder = new \User\Controller\WebUserOrderController();
            $orderDetails = $WebUserOrder->cronOrderDetails($invitation['order_id'], $invitation['user_id']);

            $deliveryDateTime = explode(" ", $orderDetails['delivery_time']);
            $orderDatas['order_details']['delivery_date'] = $deliveryDateTime[0];
            $orderDatas['order_details']['delivery_time'] = $deliveryDateTime[1];
            $orderDatas['order_details']['email'] = '';
            $orderDatas['order_details']['items'] = $orderDetails['order_details'];
            $orderDatas['order_details']['order_type'] = $orderDetails['order_Type'];
            $orderDatas['order_details']['order_type1'] = '';
            $orderDatas['order_details']['order_type2'] = '';
            $orderDatas['order_details']['restaurant_id'] = $invitation['restaurant_id'];
            $orderDatas['order_details']['special_instruction'] = (isset($orderDetails['special_checks'])) ? explode("||", $orderDetails['special_checks']) : '';
            $orderDatas['order_details']['tax'] = $orderDetails['tax'];
            $orderDatas['order_details']['tip_percent'] = $orderDetails['tip_percent'];
            $finalPrice = $orderFunctions->calculatePriceForCron($orderDatas ['order_details'], '', '', $invitation['user_id']);
            $subtotal = $orderDetails['order_amount'];
            $tax = $orderDetails['tax'];
            $tipAmount = $orderDetails['tip_amount'];
            $total = $orderDetails['total_amount'];
            $deal_discount = $orderDetails['deal_discount'];
            $promocode_discount = $orderDetails['promocode_discount'];
            $status = $orderFunctions->getOrderStatus($orderDatas['order_details']['delivery_date'], $orderDatas['order_details'] ['delivery_time'], $invitation['restaurant_id']);
            $orderData = $orderFunctions->makeOrderForMail($orderFunctions->itemDetails, $invitation['restaurant_id'], $status, $orderDetails['order_amount']);
            $orderDataInvite = $orderFunctions->makeOrderForMailInvite($orderFunctions->itemDetails, $invitation['restaurant_id'], $status, $orderDetails['order_amount']);

            $variables = array(
                'username' => ucfirst($host_name),
                'friendname' => ucfirst($friend_name),
                'peopleNo' => $invitation['party_size'],
                'restaurantName' => $restaurantName,
                'reservationDate' => $reservationDate,
                'reservationTime' => $reservationTime,
                'host_name' => $webUrl,
                'orderType' => "Pre-paid Reservation",
                'receiptNo' => $invitation['receipt_no'],
                'specialInstructions' => isset($orderDatas['order_details']['special_instruction']) ? implode(", ", $orderDatas['order_details']['special_instruction']) : '',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'tipAmount' => $tipAmount,
                'total' => $total,
                'cardType' => $orderDetails['card_type'],
                'cardNo' => $orderDetails['card_number'],
                'expiredOn' => $orderDetails['expired_on'],
                'acceptlink' => $accept_url_template,
                'acceptUrl' => $acceptUrl,
                'orderData' => $orderData,
                'dealDiscount' => $deal_discount,
                'promocodeDiscount' => $promocode_discount,
                'mailtext' => $mailText,
            );
            //pr($variables,1);
        } else {
            //normal reservation  

            $template = "friends-reservation-Invitation";
            $subject = 'Someone Wants To Grab Food With You';
            $layout = 'email-layout/default_new';
            $variables = array(
                'username' => $host_name,
                'friendname' => $friend_name,
                'mailtext' => $mailText,
                'acceptlink' => $accept_url_template,
                'acceptUrl' => $acceptUrl,
                'restaurantName' => $restaurantName,
                'reservationDate' => $reservationDate,
                'reservationtime' => $reservationTime,
                'hostname' => $webUrl
            );
        }
        ###################
        $emailData = array(
            'receiver' => $recievers,
            'variables' => $variables,
            'subject' => $subject,
            'template' => $template,
            'layout' => $layout
        );
        $user_function->sendMails($emailData);
        //update cron status after sending mail to user
        $userInvitationModel->updateCron($invitation['id']);
        echo '++++++++++'.date('H:i:s');
        ###################
    }
    //echo '============='.date('H:i:s');
}