<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use Restaurant\OrderFunctions;
use MStripe;
use User\Model\UserOrderDetail;
use User\Model\UserOrderAddons;
use User\Model\UserOrder;
use User\Model\DbTable\UserOrderTable;
use MCommons\StaticOptions;
use User\UserFunctions;
use User\Model\UserNotification;
use User\Model\User;
use User\Model\UserAddress;
use User\Model\UserReservation;
use City\Model\City;
use User\Model\UserPromoCodes;
use Restaurant\Model\RestaurantAccounts;
use User\Model\UserReferrals;
class OrderPlaceController extends AbstractRestfulController {

    public function create($data) {
        $userModel = new User();
        $userOrder = new UserOrder ();
        $userFunctions = new UserFunctions ();
        $orderFunctions = new OrderFunctions ();
        $addressModel = new UserAddress();
        $userreferral = new UserReferrals();
        $notificationMsg = "";
        $dm_register = false;
        ########### Getting Current Time ##################
        $session = $this->getUserSession();
        $selectedLocation = $session->getUserDetail('selected_location', array());
        if(isset($selectedLocation ['city_id']) && !empty($selectedLocation ['city_id'])){
            $cityId = $selectedLocation ['city_id']; //18845;//
            $cityModel = new City ();
            $cityDetails = $cityModel->cityDetails($cityId);
            $currentCityDateTime = StaticOptions::getRelativeCityDateTime(array(
                    'state_code' => $cityDetails [0] ['state_code']
            ));
            $currentDateTimeUnixTimeStamp = strtotime($currentCityDateTime->format('Y-m-d H:i:s'));
        }elseif(isset($data ['order_details'] ['restaurant_id']) && !empty($data ['order_details'] ['restaurant_id'])){
            $currentDateTime = StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $data ['order_details'] ['restaurant_id']
            ))->format("Y-m-d h:i");
            $currentDateTimeUnixTimeStamp = strtotime($currentDateTime);
        }else{
             $currentCityDateTime = StaticOptions::getRelativeCityDateTime(array(
                        'state_code' => "NY"
            ));

            $currentDateTimeUnixTimeStamp = strtotime($currentCityDateTime->format('Y-m-d H:i:s'));
        }
        ###################################################
        $config = $this->getServiceLocator()->get('Config');
        if ($this->getUserSession()->isLoggedIn()) {
            $userId = $session->getUserId();
        } else {
             ########### Register User & Dine-More ####################
            
            if(isset($data['user_details']['dm_register']) && $data['user_details']['dm_register']!=0 && !empty($data['user_details']['dm_register'])){
                $restaurantModel = new \Restaurant\Model\Restaurant();
                $restaurantDetailOption = array('columns' => array('rest_code', 'restaurant_name'), 'where' => array('id' => $data ['order_details'] ['restaurant_id']));
                $restDetail = $restaurantModel->findRestaurant($restaurantDetailOption)->toArray();
                $loyalityCode = substr($restDetail['restaurant_name'],0,1).$data ['order_details'] ['restaurant_id']."00";
                $userDataDuringOrder = array(
                    'first_name' => $data ['user_details'] ['fname'],
                    'last_name' => $data ['user_details'] ['lname'],
                    'email' => $data ['user_details'] ['email'],
                    'phone' => $data ['user_details'] ['phone'],
                    'cityid' => $cityId,
                    'user_source' => (StaticOptions::$_userAgent==="iOS")?"iOS":"android",
                    'loyality_code'=> $loyalityCode,
                    'current_date'=>StaticOptions::getRelativeCityDateTime(array('restaurant_id' => $data ['order_details'] ['restaurant_id']))->format(StaticOptions::MYSQL_DATE_FORMAT),
                    'restaurant_name'=>$restDetail['restaurant_name'],
                    'restaurant_id'=>$data ['order_details'] ['restaurant_id']
                );
                $userFunctions->email = $data ['user_details'] ['email'];
                $dm_register = $userFunctions->dmUserRegisterDuringOrder($userDataDuringOrder);
                $userId = $userFunctions->userId;
            }else{
                $userId = false;
            }
        }
        $reserved_seats = '';
        if (empty($data ['order_details'] ['restaurant_id']) && !isset($data ['order_details'] ['restaurant_id'])) {
            throw new \Exception('Restaurant is not valid.');
        }

        if (isset($data['order_details']['items']) && empty($data['order_details']['items']) && count($data['order_details']['items']) < 1) {
            throw new \Exception('Sorry we could not process your order as some of the items you selected are no longer offered by the restaurant.');
        }

        // $data['deal_id'] = 722;  
        ############## Order Pass Through ##################
        $orderPass = $orderFunctions->getOrderPassThrough($data ['order_details'] ['restaurant_id']);
        ####################################################
        if (isset($data["is_preorder_reservation"]) && $data["is_preorder_reservation"] == true) {
            $data['do_transaction'] = false;
            $data["is_preorder_reservation"] = false;
            return $this->savePreOrderReservation($data, $userId, $orderPass);
        }

        $userAddressData = array();
        $userAddressData ['latitude'] = (isset($data ['user_details']['address_lat']) && !empty($data ['user_details']['address_lat'])) ? $data ['user_details']['address_lat'] : 0;
        $userAddressData ['longitude'] = (isset($data ['user_details']['address_lng']) && !empty($data ['user_details']['address_lng'])) ? $data ['user_details']['address_lng'] : 0;
        $isPreOrderReservation = false;
        if (isset($data['do_transaction']) && $data['do_transaction'] == false) {
            $isPreOrderReservation = true;
        }
         
        
        $dbtable = new UserOrderTable ();
        if (!$isPreOrderReservation) {            
            $dbtable->getWriteGateway()->getAdapter()->getDriver()->getConnection()->beginTransaction();
        } else {
            $reserved_seats = $data["reservation_details"]['reserved_seats'];
        }
        try {

            $itemsId = array();
            $user_address_id = "";
            $user_instruction = "";
            #################bp_status##########
            $bpoption = array('columns' => array('bp_status', 'phone', 'first_name', 'last_name','email'), 'where' => array('id' => $userId));
            $bp_status = $userModel->getUser($bpoption);
            $cod = isset($data['cod'])?(int)$data['cod']:0;
            $restaurantAccount = new RestaurantAccounts ();
            $isRegisterRestaurant = $restaurantAccount->getRestaurantAccountDetail(array(
                'columns' => array(
                    'restaurant_id'
                ),
                'where' => array(
                    'restaurant_id' => $data ['order_details'] ['restaurant_id'],
                    'status' => 1
                )
            ));

            if (isset($isRegisterRestaurant['restaurant_id']) && !empty($isRegisterRestaurant['restaurant_id'])) {
                $registerRestaurant = true;
            } else {
                $registerRestaurant = false;
            }

            ############Deal Data############
            $dealDetails = array();
            if (isset($data['deal_id']) && !empty($data['deal_id'])) {
                $dealDetails = $orderFunctions->restaurantDeal($data['deal_id'], $registerRestaurant, $currentDateTimeUnixTimeStamp);
            }

            #################################
            #### User Promocode Detail ###
            $userPromocodesDetails = [];
            $userFunctions->userId = $userId;
            $userFunctions->getUserPromocodeDetails();
            $userFunctions->currentDateTimeUnixTimeStamp = $currentDateTimeUnixTimeStamp;
            if ($userFunctions->userPromocodes) {
                if ($userFunctions->getNewUserPromotion()) {
                    //if ($registerRestaurant) {
                        $userFunctions->userPromocodes[$userFunctions->promocodeId]['promocodeType'] = (int) 1;
                        $userFunctions->userPromocodes[$userFunctions->promocodeId]['cityDateTime'] = $currentCityDateTime->format('Y-m-d H:i:s');
                        $userPromocodesDetails = $userFunctions->userPromocodes[$userFunctions->promocodeId];
                        $userPromocodesDetails['discount_coupon'] = 0;
                    //}
                } elseif ($userFunctions->getUserPromocode()) {
                    $userFunctions->userPromocodes[$userFunctions->promocodeId]['promocodeType'] = (int) 0;
                    $userFunctions->userPromocodes[$userFunctions->promocodeId]['cityDateTime'] = $currentCityDateTime->format('Y-m-d H:i:s');
                    $userPromocodesDetails = $userFunctions->userPromocodes[$userFunctions->promocodeId];
                    $userPromocodesDetails['discount_coupon'] = 0;
                }
            }
            
            if(isset($data['user_promocode_id']) && !empty($data['user_promocode_id']) && empty($userPromocodesDetails)){
                throw new \Exception("We could not apply promocode, please ensure applied promocode is valid.", 400);
            }
            ##################################            
            $orderFunctions->orderPass = $orderPass;
            #################### Check Exist User Address ######################

            if (isset($data['user_address_id']) && !empty($data['user_address_id']) && $userId) {
                $user_address_id = $data['user_address_id'];
                $options = array(
                    'where' => array(
                        'id' => $user_address_id,
                    )
                );

                $userAddressDetailExist = $addressModel->getUserAddressInfo($options);
                if ($userAddressDetailExist) {
                    $firstNameLastName = explode(" ", $userAddressDetailExist['address_name']);
                    $data ['user_details'] ['fname'] = isset($bp_status['first_name']) ? $bp_status['first_name'] : '';
                    $data ['user_details'] ['lname'] = isset($bp_status['last_name']) ? $bp_status['last_name'] : '';
                    $data ['user_details'] ['email'] = $userAddressDetailExist['email'];
                    $data ['user_details'] ['city'] = $userAddressDetailExist['city'];
                    $data ['user_details'] ['apt_suit'] = $userAddressDetailExist['apt_suite'];
                    $data ['user_details'] ['phone'] = $userAddressDetailExist['phone'];
                    $data ['user_details'] ['state_code'] = $userAddressDetailExist['state'];
                    $data ['user_details'] ['address'] = $userAddressDetailExist['street'];
                    $data ['user_details'] ['zipcode'] = $userAddressDetailExist['zipcode'];
                    if (!empty($userAddressDetailExist['apt_suite'])) {
                        $data ['order_details'] ['delivery_address'] = $userAddressDetailExist['street'] . ", " . $userAddressDetailExist['apt_suite'];
                    } else {
                        $data ['order_details'] ['delivery_address'] = $userAddressDetailExist['street'];
                    }
                    $data['user_details']['address_lat'] = $userAddressDetailExist['latitude'];
                    $data['user_details']['address_lng'] = $userAddressDetailExist['longitude'];
                    $userAddressData ['latitude'] = $userAddressDetailExist['latitude'];
                    $userAddressData ['longitude'] = $userAddressDetailExist['longitude'];
                }
            }
            ############################################################
            
            ######################Redeem point##########################
            $usrTotalPoint = $userFunctions->userTotalPoint($userId);
            $orderFunctions->restaurant_id = $data ['order_details'] ['restaurant_id'];
            if(isset($data ['user_details']['redeem_point']) && !empty($data ['user_details']['redeem_point']) && $usrTotalPoint>=POINT_REDEEM_LIMIT && $orderPass==0){
                $orderFunctions->point = $data ['user_details']['redeem_point'];
            }
            ############################################################           
            
            ################### Calculate final price ##################            
            $finalPrice = $orderFunctions->calculatePrice($data ['order_details'], $dealDetails, $userPromocodesDetails);
            ############################################################  

            if ($finalPrice >= APPLIED_FINAL_TOTAL && $cod == 0) {
                if (isset($bp_status['bp_status']) && $bp_status['bp_status'] == 1) {

                    $data ['card_details'] ['card_number'] = '4242';
                    $data ['card_details'] ['expiry_month'] = '1';
                    $data ['card_details'] ['billing_zip'] = '12345';
                    $data ['card_details'] ['expiry_year'] = '20';
                    $data ['card_details'] ['name_on_card'] = 'demo';
                    $data ['card_details'] ['cvc'] = '123';
                    $cardDetails = $data ['card_details'];
                    $cardDetails = array(
                        'number' => $cardDetails ['card_number'],
                        'exp_month' => $cardDetails ['expiry_month'],
                        'exp_year' => $cardDetails ['expiry_year'],
                        'name' => $cardDetails ['name_on_card'],
                        'cvc' => $cardDetails ['cvc'],
                        'address_zip' => $cardDetails['billing_zip'],
                    );
                } elseif (isset($data ['card_details']['id']) && !empty($data ['card_details']['id']) && $orderPass == 1) {
                    $userCard = new \User\Model\UserCard();
                    $ccDetail = $userCard->getUserDecriptCard($userId, $data ['card_details']['id']);
                    $cc = $orderFunctions->aesDecrypt($ccDetail[0]['encrypt_card_number']);
                    $ccArray = explode("-", $cc);
                    $expMY = explode("/", $ccDetail[0]['expired_on']);
                    $cardDetails = array(
                        'card_no' => $ccArray [0],
                        'expiry_month' => $expMY [0],
                        'expiry_year' => $expMY [1],
                        'name_on_card' => $ccDetail[0] ['name_on_card'],
                        'cvc' => $ccArray [1],
                        'billing_zip' => $ccDetail[0]['zipcode'],
                    );
                } else {
                    $cardDetails = $data ['card_details'];
                }

                if (empty($bp_status['bp_status']) || $bp_status['bp_status'] == NULL || $bp_status['bp_status'] == 0) {
                    if (!isset($cardDetails ['stripe_token_id']) || empty($cardDetails ['stripe_token_id'])) {

                        if (!isset($cardDetails ['card_no']) && !empty($cardDetails ['card_no'])) {
                            throw new \Exception('Card details not sent');
                        }
                        if (!isset($cardDetails ['expiry_month']) && !empty($cardDetails ['expiry_month'])) {
                            throw new \Exception('Expiry month not sent');
                        }
                        if (!isset($cardDetails ['billing_zip']) && !empty($cardDetails ['billing_zip'])) {
                            throw new \Exception('Billing zip not sent');
                        }
                        if (!isset($cardDetails ['expiry_year']) && !empty($cardDetails ['expiry_year'])) {
                            throw new \Exception('Expiry year not sent');
                        }
                        if (!isset($cardDetails ['name_on_card']) && !empty($cardDetails ['name_on_card'])) {
                            throw new \Exception('Name on card not sent');
                        }
                        if (!isset($cardDetails ['cvc']) && !empty($cardDetails ['cvc'])) {
                            throw new \Exception('CVC not sent');
                        }
                        $cardNo = $string = preg_replace('/\s+/', '', $cardDetails ['card_no']);
                        $cardDetails = array(
                            'number' => $cardNo,
                            'exp_month' => $cardDetails ['expiry_month'],
                            'exp_year' => $cardDetails ['expiry_year'],
                            'name' => $cardDetails ['name_on_card'],
                            'cvc' => $cardDetails ['cvc'],
                            'address_zip' => $cardDetails['billing_zip'],
                        );
                        // save the card                
                        if (isset($data ['card_details']['save_card']) && $data ['card_details']['save_card'] == 1) {
                            $saveCard = true;
                        } else {
                            $saveCard = false;
                        }

                        //validate card
                        if ($orderPass == 1) {
                            try {
                                $userFunctions->validateCardFromStripe($cardDetails);
                            } catch (\Exception $e) {                                
                                throw new \Exception("We could not charge your credit card, please ensure all fields are entered correctly.", 400);
                            }
                        }

                        try {
                            $userFunctions->saveCardToStripeAndDatabase($cardDetails, $saveCard);
                        } catch (\Exception $e) {                            
                            throw new \Exception("We could not charge your credit card, please ensure all fields are entered correctly.", 400);
                        }
                    } else {
                        $cardDetails = $cardDetails ['stripe_token_id'];
                    }

                    // charge user
                    if (isset($orderPass) && $orderPass == 0) {// order_pass_through if 1 then not sent to stripe to charge   
                        $stripeModel = new MStripe($this->getStripeKey());
                        $selectedLocation = $this->getUserSession()->getUserDetail('selected_location');
                        $stripeResponse = $stripeModel->chargeCard($cardDetails, $finalPrice,$orderFunctions->restaurantName);
                    }

                    if (isset($stripeResponse ['status']) && !(int) $stripeResponse ['status'] && $orderPass == 0) {
                        throw new \Exception("We could not charge your credit card, please ensure all fields are entered correctly.", 400);
                    }
                }

                if ($orderPass == 0) {
                    if (isset($data ['card_details']['stripe_token_id']) && !empty($data ['card_details']['stripe_token_id'])) {
                        $userOrder->card_number = $data ['card_details']['card_number'];
                    } else {
                        $userOrder->card_number = substr($cardDetails ['number'], - 4);
                    }
                } else {
                    if (isset($data ['card_details']['stripe_token_id']) && !empty($data ['card_details']['stripe_token_id'])) {
                        $userCard = new \User\Model\UserCard();
                        $ccDetail = $userCard->getUserDecriptCard($userId, $data ['card_details']['id']);
                        $cc = $orderFunctions->aesDecrypt($ccDetail[0]['encrypt_card_number']);
                        $ccArray = explode("-", $cc);
                        $expMY = explode("/", $ccDetail[0]['expired_on']);
                        $cardDetails = array(
                            'card_no' => $ccArray [0],
                            'expiry_month' => $expMY [0],
                            'expiry_year' => $expMY [1],
                            'name_on_card' => $ccDetail[0] ['name_on_card'],
                            'cvc' => $ccArray [1],
                            'billing_zip' => $ccDetail[0]['zipcode'],
                        );
                        $userOrder->encrypt_card_number = $orderFunctions->aesEncrypt($cardDetails ['card_no'] . "-" . $cardDetails ['cvc']);
                        $userOrder->card_number = substr($cardDetails ['card_no'], - 4);
                    } else {
                        $userOrder->encrypt_card_number = $orderFunctions->aesEncrypt($cardDetails ['number'] . "-" . $cardDetails ['cvc']);
                        $userOrder->card_number = substr($cardDetails ['number'], - 4);
                    }
                }

                $userOrder->name_on_card = $data ['card_details'] ['name_on_card'];
                $userOrder->card_type = isset($data ['card_details'] ['card_type']) ? $data ['card_details'] ['card_type'] : 'cc';
                $userOrder->expired_on = $data ['card_details'] ['expiry_month'] . '/' . $data ['card_details'] ['expiry_year'];
                $userOrder->stripe_charge_id = isset($stripeResponse['id']) ? $stripeResponse['id'] : '';
            } else {
                $userOrder->card_number = '';
                $userOrder->name_on_card = '';
                $userOrder->card_type = '';
                $userOrder->expired_on = '';
                $userOrder->stripe_charge_id = '';
            }//end of final price condition

            if ($userId) {
                $userOrder->user_id = $userId;
            }

            ################################
            /* Logic for extend delivery time  time difference is less than 45 min */

            $currentTime = StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $data ['order_details'] ['restaurant_id']
                ))->format(StaticOptions::MYSQL_DATE_FORMAT);

            $actualDeliveryDate = $data ['order_details'] ['delivery_date'];
            $actualDeliveryTime = $data ['order_details'] ['delivery_time'];
            $deliveryDateTime = $data ['order_details'] ['delivery_date'] . ' ' . $data ['order_details'] ['delivery_time'] . ':00';

            $differenceOfTimeInMin = (int) ((strtotime($deliveryDateTime) - strtotime($currentTime)) / 60);

            if ($differenceOfTimeInMin < 45) {
                $deliveryTimestamp = strtotime($currentTime) + 45 * 60;
                $actualDeliveryDate = date('Y-m-d', $deliveryTimestamp);
                $actualDeliveryTime = date('H:i', $deliveryTimestamp);
                $deliveryDateTime = date('Y-m-d H:i:s', $deliveryTimestamp);
            }

            $dateOfOrder = StaticOptions::getFormattedDateTime($currentTime, 'Y-m-d H:i:s', 'D, M j, Y');
            $timeOfOrder = StaticOptions::getFormattedDateTime($currentTime, 'Y-m-d H:i:s', 'h:i A');
            $dateTimeOfOrder = $dateOfOrder . ' at ' . $timeOfOrder;
            $dateOfDelivery = StaticOptions::getFormattedDateTime($deliveryDateTime, 'Y-m-d H:i:s', 'D, M j, Y');
            $timeOfDelivery = StaticOptions::getFormattedDateTime($deliveryDateTime, 'Y-m-d H:i:s', 'h:i A');
            $dateTimeOfDelivery = $dateOfDelivery . ' at ' . $timeOfDelivery;
            $userOrder->status = $orderFunctions->getOrderStatus($actualDeliveryDate, $actualDeliveryTime, $data ['order_details'] ['restaurant_id']);
            ################################   
            ############ Get user IP Address ##############           
            $ipAddress = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
            ###############################################

            $user_instruction = isset($data ['order_details'] ['own_instruction']) ? $data ['order_details'] ['own_instruction'] : '';
            $userDetailAddress = isset($data ['user_details'] ['address']) ? trim($data ['user_details'] ['address']) : "";
            $userOrder->order_pass_through = $orderPass;
            $userOrder->order_amount = $orderFunctions->subtotal;
            if($orderFunctions->discountAmountOnPoint > 0){
                $userOrder->total_amount = $orderFunctions->finalTotal+$orderFunctions->discountAmountOnPoint;
            }else{
                $userOrder->total_amount = $orderFunctions->finalTotal;
            }
            $userOrder->deal_discount = $orderFunctions->dealDiscount;
            $userOrder->tax = $orderFunctions->tax;
            $userOrder->delivery_charge = $orderFunctions->deliveryCharge;
            $userOrder->tip_amount = $orderFunctions->tipAmount;
            $userOrder->tip_percent = $orderFunctions->tipPercent;
            $userOrder->restaurant_id = $data ['order_details'] ['restaurant_id'];
            $userOrder->fname = isset($data ['user_details'] ['fname'])?$data ['user_details'] ['fname']:"";
            $userOrder->lname = isset($data ['user_details'] ['lname'])?$data ['user_details'] ['lname']:"";
            $userOrder->email = (empty($data ['user_details'] ['email']))?$bp_status['email']:$data ['user_details'] ['email'];
            $userOrder->city = isset($data ['user_details'] ['city']) ? trim($data ['user_details'] ['city']) : '';
            $userOrder->city_id = $cityId;
            $userOrder->apt_suite = isset($data ['user_details'] ['apt_suit']) ? trim($data ['user_details'] ['apt_suit']) : '';
            $deliveryAddress = '';
            if (isset($data ['order_details'] ['delivery_address']) && !empty($data ['order_details'] ['delivery_address'])) {
                $deliveryAddress = $data ['order_details'] ['delivery_address'];
            } else {
                $deliveryAddress = isset($data ['user_details'] ['address']) ? $data ['user_details'] ['address'] : '';
                if (!empty($userOrder->apt_suite)) {
                    $deliveryAddress .= ', ' . $userOrder->apt_suite;
                }
            }
            $userOrder->cod = $cod;
            $userOrder->delivery_address = $deliveryAddress;
            $userOrder->address = $userDetailAddress;
            $userOrder->miles_away = isset($restaurantDistance ['res_distance']) ? $restaurantDistance ['res_distance'] : 0;
            $userOrder->phone = isset($data ['user_details'] ['phone']) ? $data ['user_details'] ['phone'] : "";
            $userOrder->state_code = isset($data ['user_details'] ['state_code']) ? trim($data ['user_details'] ['state_code']) : "";
            $userOrder->zipcode = isset($data ['user_details'] ['zipcode']) ? trim($data ['user_details'] ['zipcode']) : "";
            $userOrder->billing_zip = $data ['card_details'] ['billing_zip'];
            $userOrder->order_type = ucwords($data ['order_details'] ['order_type']);
            $userOrder->delivery_time = $deliveryDateTime;
            $userOrder->order_type1 = $data ['order_details'] ['order_type1'];
            $userOrder->order_type2 = $data ['order_details'] ['order_type2'];
            $userOrder->special_checks = (isset($data ['order_details'] ['special_instruction']) && !empty($data ['order_details'] ['special_instruction'])) ? $data ['order_details'] ['special_instruction'] : '';
            $userOrder->new_order = 0;
            $userOrder->user_ip = $ipAddress;
            $userOrder->host_name = (StaticOptions::$_userAgent==="iOS")?'iphone':'android';

            $userOrder->payment_receipt = $orderFunctions->generateReservationReceipt();
            $userOrder->created_at = StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $data ['order_details'] ['restaurant_id']
                ))->format(StaticOptions::MYSQL_DATE_FORMAT);
            $userOrder->updated_at = StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $data ['order_details'] ['restaurant_id']
                ))->format(StaticOptions::MYSQL_DATE_FORMAT);
            $userOrder->promocode_discount = $orderFunctions->promocodeDiscount;
            $userOrder->pay_via_point = $orderFunctions->discountAmountOnPoint;
            $userOrder->pay_via_card = ($orderFunctions->finalTotal > APPLIED_FINAL_TOTAL && $cod == 0)?$orderFunctions->finalTotal:0;
            $userOrder->redeem_point = $orderFunctions->point;
            $userOrder->deal_id = $orderFunctions->deal_id;
            $userOrder->deal_title = $orderFunctions->deal_title;
            $userOrder->latitude = $userAddressData['latitude'];
            $userOrder->longitude = $userAddressData['longitude'];
            $userOrderId = $userOrder->addtoUserOrder();
            $userOrderId = $userOrderId ['id'];

            ###########update user phone number if phone number is empty, null or not exist ##########
            if ((empty($bp_status['phone']) || $bp_status['phone'] == null) && $userId) {
                $userModel->id = $userId;
                $phoneData = array('phone' => $userOrder->phone);
                $userModel->update($phoneData);
                ########## salesmanago phone event ##############
                $salesData = array('phone'=>$userOrder->phone,'email'=>$userOrder->email,'owner_email'=>'no-reply@munchado.com', 'identifier'=>'phone');
                //$userFunctions->createQueue($salesData,'Salesmanago');
                }
            ##########################################################################################
            
            ##### Update user procode redeam status ####
            if (!empty($userPromocodesDetails)) {
                if ($orderFunctions->priceForPromocodeCal != 0) {
                    $userPromocodeModel = new UserPromocodes();
                    $userPromocodeModel->id = $userPromocodesDetails['user_promocode_id'];
                    $userPromocodeData = array('order_id' => $userOrderId, 'reedemed' => 1);
                    $userPromocodeModel->update($userPromocodeData);
                }
            }
            ############################################
            
             ############ Transaction amount against point redeem ###########
            if($orderFunctions->discountAmountOnPoint > 0){
                $orderFunctions->transaction('credit','credit amount against redeemed point during order $'.$orderFunctions->discountAmountOnPoint. ' order id : '.$userOrderId);
                $orderFunctions->transaction("debit","debit amount against order $".$orderFunctions->discountAmountOnPoint." order id : ".$userOrderId);
                $orderFunctions->updateUserPoint($userOrderId);
                }
            
            ################################################################
            
            $userOrderDetails = new UserOrderDetail ();
            $userOrderAddons = new UserOrderAddons ();
            $foodBookmark = new \Bookmark\Model\FoodBookmark();
            
            $i = 0;
            $feedItems = '';
            foreach ($orderFunctions->itemDetails as $item) {
                $userOrderDetails->user_order_id = $userOrderId;
                $userOrderDetails->item = html_entity_decode($item ['item_name']);
                $userOrderDetails->item_description = $item ['item_desc'];
                $userOrderDetails->item_id = $item ['item_id'];
                $userOrderDetails->quantity = $item ['quantity'];
                $userOrderDetails->item_price_id = $item ['price_id'];
                $userOrderDetails->unit_price = $item ['unit_price'];
                $userOrderDetails->total_item_amt = $item ['total_item_amount'];
                $userOrderDetails->item_price_desc = $item ['price_desc'];
                $userOrderDetails->special_instruction = $item ['special_instruction'];
                $userOrderDetails->status = 1;
                
                ############# item deal #######################
                if (isset($item['deal_id']) && !empty($item['deal_id'])) {
                    $itemDealDetails = $orderFunctions->restaurantDeal($item['deal_id'], $registerRestaurant, $currentDateTimeUnixTimeStamp);
                    $orderFunctions->deal_id = $itemDealDetails['id'];
                    if($itemDealDetails['user_deals']==1 && $itemDealDetails['deal_used_type']==1){
                        $orderFunctions->dealAvailedByUser();
                    }
                    
                }
                ################################################
                
                ############### Bookmark Menu ##################
                if ($userId) {

                    ########## Check existing bookmark #############
                    $foodBookmark->getDbTable()->setArrayObjectPrototype('ArrayObject');
                    $options = array('columns' => array('menu_id', 'id'),
                        'where' => array(
                            'menu_id' => $item ['item_id'],
                            'user_id' => $userId,
                            'type' => 'ti'
                        )
                    );
                    $isAlreadyBookedmark = $foodBookmark->find($options)->toArray();
                    ################################################

                    if (empty($isAlreadyBookedmark)) {
                        $foodBookmark->user_id = $userId;
                        $foodBookmark->menu_id = $item ['item_id'];
                        $foodBookmark->restaurant_id = $userOrder->restaurant_id;
                        $foodBookmark->type = 'ti';
                        $foodBookmark->menu_name = $item ['item_name'];
                        $foodBookmark->created_on = StaticOptions::getRelativeCityDateTime(array(
                                'restaurant_id' => $userOrder->restaurant_id
                            ))->format(StaticOptions::MYSQL_DATE_FORMAT);

                        $foodBookmark->addBookmark();
                    }
                }
                ############# End of bookmark ###################
                
                $userOrderDetailId = $userOrderDetails->addtoUserOrderDetail();
                $itemsId[$i] = $item ['item_id'];
                $feedItems.=$item ['quantity'] . " " . html_entity_decode($item ['item_name']) . ', ';
                $i++;
                if (!empty($item ['addons'])) {
                    foreach ($item ['addons'] as $addon) {
                        $userOrderAddons->user_order_detail_id = $userOrderDetailId;
                        $userOrderAddons->user_order_id = $userOrderId;
                        $userOrderAddons->addons_option = $addon ['addon_option'];
                        $userOrderAddons->menu_addons_id = $addon ['addon_id'];
                        $userOrderAddons->menu_addons_option_id = $addon ['addon_option_id'];
                        $userOrderAddons->price = $addon ['price'];
                        $userOrderAddons->quantity = $item ['quantity'];
                        $userOrderAddons->selection_type = 1;
                        $userOrderAddons->priority = $addon['priority'];
                        $userOrderAddons->was_free = $addon['was_free'];
                        $userOrderAddons->addtoUserOrderAddons();
                    }
                }
            }

            $webUrl = PROTOCOL . $config ['constants'] ['web_url'];
            $address = "";
            if (isset($data ['order_details'] ['delivery_address'])) {
                $address = $userOrder->delivery_address . ', ' . $userOrder->city . ', ' . $userOrder->state_code . ', ' . $userOrder->zipcode;
            }

            $data = array(
                'name' => isset($data ['user_details'] ['fname'])?$data ['user_details'] ['fname']:"",
                'lname' => isset($data['user_details']['lname'])?$data['user_details']['lname']:"",
                'hostName' => $webUrl,
                'restaurantName' => $orderFunctions->restaurantName,
                'orderType' => ($data ['order_details'] ['order_type1'] == 'I') ? 'Individual ' . ucwords($data ['order_details'] ['order_type']) : 'Group ' . ucwords($data ['order_details'] ['order_type']) . ucwords($data ['order_details'] ['order_type']),
                'receiptNo' => $userOrder->payment_receipt,
                'timeOfOrder' => $dateTimeOfOrder,
                'timeOfDelivery' => $dateTimeOfDelivery,
                'orderData' => $orderFunctions->makeOrderForMail($orderFunctions->itemDetails, $userOrder->restaurant_id, $userOrder->status, $orderFunctions->subtotal),
                'subtotal' => $orderFunctions->subtotal,
                'discount' => $orderFunctions->dealDiscount,
                'tax' => $orderFunctions->tax,
                'tipAmount' => $orderFunctions->tipAmount,
                'total' => $orderFunctions->finalTotal,
                'cardType' => $userOrder->card_type,
                'cardNo' => $userOrder->card_number,
                'expiredOn' => $userOrder->expired_on,
                'email' => $userOrder->email,
                'specialInstructions' => $userOrder->special_checks,
                'type' => ucwords($data ['order_details'] ['order_type']),
                'address' => $address,
                'onlyDate' => StaticOptions::getFormattedDateTime($data ['order_details'] ['delivery_date'], 'Y-m-d', 'D, M j, Y'),
                'onlyTime' => StaticOptions::getFormattedDateTime($data ['order_details'] ['delivery_time'], 'H:i', 'h:i A'),
                'deliveryCharge' => $orderFunctions->deliveryCharge,
                'city' => isset($data ['user_details'] ['city']) ? $data ['user_details'] ['city'] : "",
                'state' => isset($data ['user_details'] ['state_code']) ? $data ['user_details'] ['state_code'] : "",
                'phone' => isset($data ['user_details'] ['phone']) ? $data ['user_details'] ['phone'] : "",
                'zip' => isset($data ['user_details'] ['zipcode']) ? $data ['user_details'] ['zipcode'] : "",
                'orderTime' => $timeOfOrder,
                'orderDate' => $dateOfOrder,
                'dealDiscount' => $orderFunctions->dealDiscount,
                'promocodeDiscount' => $orderFunctions->promocodeDiscount,
                'redeemedPointAmt'=>$orderFunctions->discountAmountOnPoint,
            );

            // create auto restaurant bookmark
            if ($userId) {
//                $restaurantBookmark = new RestaurantBookmark ();
//                $bookmarkData = array(
//                    'restaurant_id' => $userOrder->restaurant_id,
//                    'restaurant_name' => $orderFunctions->restaurantName,
//                    'user_id' => $userId,
//                    'created_on' => StaticOptions::getRelativeCityDateTime(array('restaurant_id' => $userOrder->restaurant_id))->format(StaticOptions::MYSQL_DATE_FORMAT),
//                    'type' => 'wl'
//                );
//                $restaurantBookmark->insertBookmark($bookmarkData);

                /*
                 * Update user instruction
                 */

                if (!empty($user_instruction)) {
                    $userModel->id = $userId;

                    if ($userOrder->order_type === "Takeout") {
                        $userModel->update(array('takeout_instructions' => $user_instruction));
                    } elseif ($userOrder->order_type === "Delivery") {
                        $userModel->update(array('delivery_instructions' => $user_instruction));
                    }
                }
            }

            if (!$isPreOrderReservation) {
                //$userFunctions->sendOrderMail($data, $userOrder->status, $userId, $userOrder->restaurant_id);
                $dbtable->getWriteGateway()->getAdapter()->getDriver()->getConnection()->commit();
                
                /**
                 * Push To Pubnub For User
                 */
                $currentTimeOrder = new \DateTime ();
                $arrivedTimeOrder = \DateTime::createFromFormat(StaticOptions::MYSQL_DATE_FORMAT, $userOrder->delivery_time);
                $currentTimeNewOrder = StaticOptions::getRelativeCityDateTime(array(
                        'restaurant_id' => $userOrder->restaurant_id
                    ))->format(StaticOptions::MYSQL_DATE_FORMAT);
                $differenceOfTimeInMin = round(abs(strtotime($arrivedTimeOrder->format("Y-m-d H:i:s")) - strtotime($currentTimeNewOrder)) / 60);
                //$orderFunctions->sendSmsforOrder($data, $userOrder->status, $userId, $userOrder->restaurant_id, $userOrder->delivery_time);
                ## stop notification as instruction by yogendra sir 24-03-2017
                //$userNotificationModel = new UserNotification ();
//                if ($userOrder->status == 'placed') {
//                    if ($differenceOfTimeInMin <= 90) {
//
//                       if ((isset($orderPass) && $orderPass == 1) && $userOrder->order_type === "Takeout") {
//                            $notificationMsg = 'We got your order and we’re hungry just thinking about it...';
//                        }else if ((isset($orderPass) && $orderPass == 1) && $userOrder->order_type === "Delivery") {
//                            $notificationMsg = 'We got your order and we’re hungry just looking at it...';
//                        } else if (isset($orderPass) && $orderPass == 1) {
//                            $notificationMsg = 'We got your pre-order for today and we’re hungry just thinking about it.';
//                        } else if($registerRestaurant==true && $userOrder->order_type === "Takeout"){
//                            $notificationMsg = "We got your order and we’re hungry just thinking about it...";
//                        } else if($registerRestaurant==true && $userOrder->order_type === "Delivery"){
//                            $notificationMsg = "We got your order and we’re hungry just looking at it...";
//                        } else {
//                            $notificationMsg = 'We got your pre-order and we’re a little jelly you’re the one eating it, not us.';
//                        }
//
//                        $channel = "mymunchado_" . $userId;
//                        $notificationArray = array(
//                            "msg" => $notificationMsg,
//                            "channel" => $channel,
//                            "userId" => $userId,
//                            "type" => 'order',
//                            "restaurantId" => $userOrder->restaurant_id,
//                            'curDate' => StaticOptions::getRelativeCityDateTime(array(
//                                'restaurant_id' => $userOrder->restaurant_id
//                            ))->format(StaticOptions::MYSQL_DATE_FORMAT),
//                            'restaurant_name' => $orderFunctions->restaurantName,
//                            'order_id' => $userOrderId,
//                            'is_live' => 1
//                        );
//                        $notificationJsonArray = array('user_id' => $userId, 'order_id' => $userOrderId, 'restaurant_id' => $userOrder->restaurant_id, 'restaurant_name' => $orderFunctions->restaurantName);
//                        $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
//                        $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
//                    } elseif(strtotime($arrivedTimeOrder->format("Y-m-d H:i:s")) > strtotime($currentTimeNewOrder)){
//                        if($registerRestaurant==true && $userOrder->order_type === "Delivery"){
//                            $notificationMsg = 'We saw your pre-order for today and we’re a little jelly you’re the one eating it, not us.';
//                        } else if ((isset($orderPass) && $orderPass == 1) && $userOrder->order_type === "Delivery") {
//                            $notificationMsg = "We got your pre-order and we’re hungry just thinking about it...";
//                        }else if ((isset($orderPass) && $orderPass == 1) && $userOrder->order_type === "Takeout") {
//                            $notificationMsg = "We got your pre-order for today and we’re hungry just thinking about it...";
//                        } else if($registerRestaurant==true && $userOrder->order_type === "Takeout"){
//                            $notificationMsg = "We saw your pre-order for today and we’re a little jelly you’re the one eating it, not us.";                            
//                        }
//                        
//                        if(strtotime($arrivedTimeOrder->format("Y-m-d")) > strtotime(date('Y-m-d', strtotime($currentTimeNewOrder)))){
//                            if($registerRestaurant==true && $userOrder->order_type === "Takeout"){
//                            $notificationMsg = "We got your pre-order and we’re a little jelly you’re the one eating it, not us.";                            
//                            }
//                            else if ((isset($orderPass) && $orderPass == 1) && $userOrder->order_type === "Takeout") {
//                            $notificationMsg = "We got your pre-order and we’re hungry just thinking about it...";
//                            } else if($registerRestaurant==true && $userOrder->order_type === "Delivery"){
//                            $notificationMsg = 'We got your pre-order and we’re a little jelly you’re the one eating it, not us.';
//                        } else if ((isset($orderPass) && $orderPass == 1) && $userOrder->order_type === "Delivery") {
//                            $notificationMsg = "We got your pre-order and we’re hungry just thinking about it...";
//                        }else{
//                            $notificationMsg = "We got your pre-order and we’re hungry just thinking about it...";
//                        }
//                        }
//                        
//                         $channel = "mymunchado_" . $userId;
//                        $notificationArray = array(
//                            "msg" => $notificationMsg,
//                            "channel" => $channel,
//                            "userId" => $userId,
//                            "type" => 'order',
//                            "restaurantId" => $userOrder->restaurant_id,
//                            'curDate' => StaticOptions::getRelativeCityDateTime(array(
//                                'restaurant_id' => $userOrder->restaurant_id
//                            ))->format(StaticOptions::MYSQL_DATE_FORMAT),
//                            'restaurant_name' => $orderFunctions->restaurantName,
//                            'order_id' => $userOrderId,
//                            'is_live' => 1
//                        );
//                        $notificationJsonArray = array('user_id' => $userId, 'order_id' => $userOrderId, 'restaurant_id' => $userOrder->restaurant_id, 'restaurant_name' => $orderFunctions->restaurantName);
//                        $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
//                        $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
//                    } else {
//                        if ((isset($orderPass) && $orderPass == 1) && $userOrder->order_type === "Takeout") {
//                            $notificationMsg = 'We got your order and we’re hungry just thinking about it...';
//                        } else if ((isset($orderPass) && $orderPass == 1) && $userOrder->order_type === "Delivery") {
//                            $notificationMsg = 'We got your order and we’re hungry just looking at it...';
//                        }else if($registerRestaurant==true && $userOrder->order_type === "Takeout"){
//                            $notificationMsg = 'We saw your pre-order for today and we’re a little jelly you’re the one eating it, not us.';
//                        }else if($registerRestaurant==true && $userOrder->order_type === "Delivery"){
//                            $notificationMsg = 'We saw your pre-order for today and we’re a little jelly you’re the one eating it, not us.';
//                        }else if($registerRestaurant==true && $userOrder->order_type === "Takeout" && (isset($orderPass) && $orderPass == 1)){
//                            $notificationMsg = 'We got your pre-order for today and we’re hungry just thinking about it...';
//                        } else if($registerRestaurant==true){
//                            $notificationMsg = 'We got your order and we’re hungry just looking at it...';
//                        }else {
//                            $notificationMsg = 'We saw your pre-order for today and we’re a little jelly you’re the one eating it, not us.';
//                        }
//
//                        $channel = "mymunchado_" . $userId;
//                        $notificationArray = array(
//                            "msg" => $notificationMsg,
//                            "channel" => $channel,
//                            "userId" => $userId,
//                            "type" => 'order',
//                            "restaurantId" => $userOrder->restaurant_id,
//                            'curDate' => StaticOptions::getRelativeCityDateTime(array(
//                                'restaurant_id' => $userOrder->restaurant_id
//                            ))->format(StaticOptions::MYSQL_DATE_FORMAT),
//                            'restaurant_name' => $orderFunctions->restaurantName,
//                            'order_id' => $userOrderId,
//                            'is_live' => 1
//                        );
//                        $notificationJsonArray = array('user_id' => $userId, 'order_id' => $userOrderId, 'restaurant_id' => $userOrder->restaurant_id, 'restaurant_name' => $orderFunctions->restaurantName);
//                        $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
//                        $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
//                    }
//                }
//                if ($userOrder->status == 'ordered') {
//
//                    ########################### Push pubnub for user ###########
//                                        
//                        if ((isset($orderPass) && $orderPass == 1) && $userOrder->order_type === "Takeout") {
//                            $notificationMsgToUser = 'We got your order and we’re hungry just thinking about it...';
//                        } else if (isset($orderPass) && $orderPass == 1) {
//                            $notificationMsgToUser = 'We got your order and we’re hungry just looking at it...';
//                        } else if($registerRestaurant==true && $userOrder->order_type === "Takeout"){
//                            $notificationMsgToUser = "We got your order and we’re hungry just thinking about it...";
//                        } else {
//                            $notificationMsgToUser = 'We got your order and we’re hungry just looking at it...';
//                        }
//
//                    $channelToUser = "mymunchado_" . $userId;
//                    $notificationArrayToUser = array(
//                        "msg" => $notificationMsgToUser,
//                        "channel" => $channelToUser,
//                        "userId" => $userId,
//                        "type" => 'order',
//                        "restaurantId" => $userOrder->restaurant_id,
//                        'curDate' => StaticOptions::getRelativeCityDateTime(array(
//                            'restaurant_id' => $userOrder->restaurant_id
//                        ))->format(StaticOptions::MYSQL_DATE_FORMAT),
//                        'restaurant_name' => ucfirst($orderFunctions->restaurantName),
//                        'order_id' => $userOrderId,
//                        'is_live' => 1
//                    );
//                    $notificationJsonArrayToUser = array('user_id' => $userId, 'order_id' => $userOrderId, 'restaurant_id' => $userOrder->restaurant_id, 'restaurant_name' => ucfirst($orderFunctions->restaurantName));
//                    $response = $userNotificationModel->createPubNubNotification($notificationArrayToUser, $notificationJsonArrayToUser);
//                    $pubnub = StaticOptions::pubnubPushNotification($notificationArrayToUser);
//
//
//                    ########################### Push pubnub for Restaurant ###################
//                    if(isset($orderPass) && $orderPass == 0){
//                    if($userOrder->order_type === "Takeout"){
//                    $notificationMsg = "You have a new takeout order. (".$userOrder->payment_receipt.") Way to go!";    
//                    }else{
//                    $notificationMsg = "You have a new delivery order. Receipt number: ".$userOrder->payment_receipt.". Way to go!";
//                    }
//                    $channel = "dashboard_" . $userOrder->restaurant_id;
//                    $notificationArray = array(
//                        "msg" => $notificationMsg,
//                        "channel" => $channel,
//                        "userId" => $userId,
//                        "type" => 'order',
//                        "restaurantId" => $userOrder->restaurant_id,
//                        'curDate' => StaticOptions::getRelativeCityDateTime(array(
//                            'restaurant_id' => $userOrder->restaurant_id
//                        ))->format(StaticOptions::MYSQL_DATE_FORMAT),
//                        'order_id' => $userOrderId
//                    );
//                    $notificationJsonArray = array('user_id' => $userId, 'order_id' => $userOrderId);
//                    $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
//                    $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
//                }
//                }
                /*
                 * Push pubnub for cms Dashboard
                 */

                if ($userOrder->order_type === "Takeout") {
//                    $notificationMsg = "You have a new takeout order.";
//                    $channel = "cmsdashboard";

                    if (isset($data ['order_details'] ['own_instruction']) && !empty($data ['order_details'] ['own_instruction'])) {
                        $userModel->update(array('takeout_instructions' => $data ['order_details'] ['own_instruction']));
                    }
                } elseif ($userOrder->order_type === "Delivery") {
//                    $notificationMsg = "You have a new delivery order.";
//                    $channel = "cmsdashboard";
                    if (isset($data ['order_details'] ['own_instruction']) && !empty($data ['order_details'] ['own_instruction'])) {
                        $userModel->update(array('delivery_instructions' => $data ['order_details'] ['own_instruction']));
                    }
                }

//                $notificationArray = array(
//                    "msg" => $notificationMsg,
//                    "channel" => $channel,
//                    "userId" => $userId,
//                    "type" => 'order',
//                    "restaurantId" => $userOrder->restaurant_id,
//                    'curDate' => StaticOptions::getRelativeCityDateTime(array(
//                        'restaurant_id' => $userOrder->restaurant_id
//                    ))->format(StaticOptions::MYSQL_DATE_FORMAT),
//                    'order_id' => $userOrderId
//                );
//                $notificationJsonArray = array('user_id' => $userId, 'order_id' => $userOrderId);
//                $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
//                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                /*
                 * End of Push pubnub for cms Dashboard
                 */
            }
            $userPoints = '';
            if ($userId) {
                $userPointsCount = new \User\Model\UserPoint();
                $userPointsSum = $userPointsCount->countUserPoints($userId);
                $redeem_points = $userPointsSum[0]['redeemed_points'];
                $userPoints = strval($userPointsSum[0]['points'] - $redeem_points);
            }
            
            if($orderFunctions->discountAmountOnPoint > 0){
                $restaurantFunctions = new \Restaurant\RestaurantDetailsFunctions();
                $restuarantAddress = $restaurantFunctions->restaurantAddress($userOrder->restaurant_id);
                
                ############## salesmanago redeem event ##################
//                $salesData['email'] = $userOrder->email;               
//                $salesData['owner_email'] = 'no-reply@munchado.com';                
//                $salesData['identifier'] = "redeemed";
//                $salesData['point'] = (int)$userOrder->redeem_point;
//                $salesData['totalpoint'] = (int)$userPoints;                 
//                $userFunctions->createQueue($salesData,"Salesmanago"); 
//                $salesData['identifier'] = "event";
//                $salesData['description']="redeem";
//                $salesData['restaurant_name']=$orderFunctions->restaurantName;
//                $salesData['location']=$restuarantAddress; 
//                $salesData['value']= (int)$userOrder->redeem_point;
//                $salesData['contact_ext_event_type']="OTHER";
//                $salesData['restaurant_id']= $userOrder->restaurant_id; 
//                $salesData['email'] = $userOrder->email;
//                $userFunctions->createQueue($salesData,"Salesmanago");
                ###################################################
            }

            #######Add address information into  user_addresses table######
            if ($this->getUserSession()->isLoggedIn()) {
                if (empty($user_address_id) && $userOrder->order_type === "Delivery" && $userAddressData ['latitude']!=0 && $userAddressData ['longitude']!=0) {
                    $userAddressData['user_id'] = $userId;
                    $userAddressData['email'] = $userOrder->email;
                    $userAddressData['apt_suite'] = $userOrder->apt_suite;
                    $userAddressData['address_name'] = $userOrder->fname . " " . $userOrder->lname;
                    $userAddressData['street'] = $userDetailAddress;
                    $userAddressData['city'] = $userOrder->city;
                    $userAddressData['state'] = $userOrder->state_code;
                    $userAddressData['phone'] = $userOrder->phone;
                    $userAddressData['zipcode'] = $userOrder->zipcode; //we are getting billing zip so no need to save in user_address table
                    $userAddressData['address_type'] = "s"; //it is default value by requirment instruction
                    $userAddressData ['status'] = 1;
                    $userAddressData ['created_on'] = $currentTime;
                    $userAddressData ['updated_at'] = $currentTime;
                    $userAddressData['google_addrres_type'] = 'street';
                    if ($userOrder->order_type === "Takeout") {
                        $userAddressData ['takeout_instructions'] = isset($data ['order_details'] ['own_instruction']) ? $data ['order_details'] ['own_instruction'] : '';
                    } elseif ($userOrder->order_type === "Delivery") {
                        $userAddressData ['delivery_instructions'] = isset($data ['order_details'] ['own_instruction']) ? $data ['order_details'] ['own_instruction'] : '';
                    }

                    $options = array(
                        'columns' => array('id', 'email', 'street', 'apt_suite', 'status', 'latitude', 'longitude'),
                        'where' => array(
                            'user_id' => $userId,
                            'latitude' => $userAddressData['latitude'],
                            'longitude' => $userAddressData['longitude'],
                            'address_type' => 's'
                        )
                    );

                    $userAddressDetail = $addressModel->getUserAddressInfo($options);
                    //pr($userAddressData,true);
                    
                    if (empty($userAddressDetail)) {
                        $userFunctions->addUserAddress($userAddressData);
                    } else {
                        if (isset($userAddressDetail['id'])) {
                            $addressModel->user_id = $userId;
                            $addressModel->address_name = $userOrder->fname . " " . $userOrder->lname;
                            $addressModel->apt_suite = $userOrder->apt_suite;
                            $addressModel->email = $userAddressDetail ['email'];
                            $addressModel->street = $userDetailAddress;
                            $addressModel->city = $userOrder->city;
                            $addressModel->state = $userOrder->state_code;
                            $addressModel->phone = $userOrder->phone;
                            $addressModel->zipcode = $userOrder->zipcode;
                            $addressModel->status = 1;
                            $addressModel->updated_at = $currentTime;
                            $addressModel->created_on = $currentTime;
                            $addressModel->address_type = "s";
                            $addressModel->google_addrres_type = 'street';
                            $addressModel->latitude = $userAddressDetail ['latitude'];
                            $addressModel->longitude = $userAddressDetail ['longitude'];
                            $addressModel->id = $userAddressDetail['id'];
                            $addressModel->addAddress();
                        }
                    }
                }
            }
            ########End of add user address############
            ################# Assign Muncher ##########                      
//            if ($userOrder->order_type === "Takeout") {
//                $userFunctions->userAvatar('order', 'takeout');
//            } elseif ($userOrder->order_type === "Delivery") {
//                $userFunctions->userAvatar('order', 'delivery');
//            }
//            $cusines = $orderFunctions->getCuisineDetail($itemsId);
//            if (in_array('Asian', $cusines)) {
//                $userFunctions->userAvatar('order', 'asian');
//            }
//
//            if (in_array('Gluten-Free', $cusines) || in_array('Vegetarian', $cusines) || in_array('Vegan', $cusines) || in_array('Health Food', $cusines)) {
//                $userFunctions->userAvatar('order', 'health food');
//            }
//
//            if (in_array('Pizza', $cusines)) {
//                $userFunctions->userAvatar('order', 'pizza');
//            }
//
//            if (in_array('Burgers', $cusines)) {
//                $userFunctions->userAvatar('order', 'burgers');
//            }
//
//            if ($data['orderType'] == 'G') {
//                $userFunctions->userAvatar('order', 'G');
//            }
            ###########################################
            $commonFunctiion = new \MCommons\CommonFunctions();
            $orderTimeSlot = explode("-", ORDER_TIME_SLOT);
            $cappingMessage=(CRM_CAPPING)?'We process all orders and reservation between '.date("h:i A",strtotime($orderTimeSlot[0].":00")).' and '.date("h:i A",strtotime($orderTimeSlot[1])).' EST':'';
          
//            if ($isPreOrderReservation) {
//                $userNotificationModel = new UserNotification ();
//                $replacementData = array('restaurant_name' => $orderFunctions->restaurantName);
//                $otherReplacementData = array();
//                $feedItems = substr($feedItems, 0, -2);
//                $feedDate = date('M d Y', strtotime($dateOfDelivery));
//                $feedTime = date('h:i a', strtotime($timeOfDelivery));
//                $uname = (isset($data['lname']) && !empty($data['lname'])) ? $data ['name'] . " " . $data['lname'] : $data['name'];
//                $feed = array(
//                    'restaurant_id' => $userOrder->restaurant_id,
//                    'restaurant_name' => $orderFunctions->restaurantName,
//                    'user_name' => ucfirst($uname),
//                    'img' => array(),
//                    'amount' => $orderFunctions->finalTotal,
//                    'order_items' => $feedItems,
//                    'reservation_time' => $feedTime,
//                    'reservation_date' => $feedDate,
//                    'no_of_people' => $reserved_seats
//                );

                //$activityFeed = $commonFunctiion->addActivityFeed($feed, 4, $replacementData, $otherReplacementData);
                
               
//                   $notificationMsgToUser = "We got your pre-paid reservation and will let you know once it’s confirmed.";
//                   $channelToUser = "mymunchado_" . $userId;
//                    $notificationArrayToUser = array(
//                        "msg" => $notificationMsgToUser,
//                        "channel" => $channelToUser,
//                        "userId" => $userId,
//                        "type" => 'reservation',
//                        "restaurantId" => $userOrder->restaurant_id,
//                        'curDate' => StaticOptions::getRelativeCityDateTime(array(
//                            'restaurant_id' => $userOrder->restaurant_id
//                        ))->format(StaticOptions::MYSQL_DATE_FORMAT),
//                        'restaurant_name' => ucfirst($orderFunctions->restaurantName),
//                        'reservation_time' => $feedTime,
//                        'reservation_date' => $feedDate,
//                        'no_of_people' => $reserved_seats
//                    );
//                    $notificationJsonArrayToUser = array('reservation_time' => $feedTime,'reservation_date' => $feedDate,'no_of_people' => $reserved_seats,'user_id' => $userId, 'order_id' => $userOrderId, 'restaurant_id' => $userOrder->restaurant_id, 'restaurant_name' => ucfirst($orderFunctions->restaurantName));
//                    $response = $userNotificationModel->createPubNubNotification($notificationArrayToUser, $notificationJsonArrayToUser);
//                    $pubnub = StaticOptions::pubnubPushNotification($notificationArrayToUser);
//                
//                return array(
//                    'id' => $userOrderId,
//                    'receipt' => $userOrder->payment_receipt,
//                    'points' => $userPoints,
//                    'orderpoints' => (int) $orderFunctions->user_order_point,
//                    'order_status' => $userOrder->status,
//                    'subTotal' => $orderFunctions->subtotal,
//                    'tax' => $orderFunctions->tax,
//                    'tipAmount' => $orderFunctions->tipAmount,
//                    'dealDiscount' => $orderFunctions->dealDiscount,
//                    'promocodeDiscount' => $orderFunctions->promocodeDiscount,
//                    'finalTotal' => $orderFunctions->finalTotal,
//                    'phone' => $userOrder->phone,
//                    'capping_message'=>$cappingMessage,
//                    'redeem_point'=>($orderFunctions->point==0)?"":(string)$orderFunctions->point,
//                    'pay_via_point'=>($orderFunctions->discountAmountOnPoint==0)?"":(string)$orderFunctions->discountAmountOnPoint,
//                    'pay_via_card' => ($userOrder->pay_via_card==0)?"":(string)$userOrder->pay_via_card,
//                             
//                );
//            } else {
                $userFunctions->restaurantId = $userOrder->restaurant_id;
                $userTransaction = $userFunctions->getFirstTranSactionUser();
                if($userTransaction==0){
                    $userFunctions->total_order = $userTransaction;
                }else{
                    $userFunctions->total_order = 1;
                }
                
                $orderPoints = $orderFunctions->user_order_point;
                $userPointsOrder = $userPoints + $orderPoints;
                
                ########Dine and more awards point calculation ########
                $userFunctions->userId = $userId;
                
                $userFunctions->order_amount = $orderFunctions->user_order_point;
                $userFunctions->activityDate = $userOrder->delivery_time;
                $userFunctions->restaurant_name = $orderFunctions->restaurantName;
                $userFunctions->orderId = $userOrderId;
                $userFunctions->typeValue = $userOrderId;
                $userFunctions->typeKey = 'order_id';
                $userFunctions->orderType = $userOrder->order_type;
                $awardPoint = $userFunctions->dineAndMoreAwards("order");
                
                if(isset($awardPoint['points'])){
                    $orderPoints = $awardPoint['points'];
                    $userPointsOrder = $userPoints + $orderPoints;
                }
                
                $replacementData = array('restaurant_name' => $orderFunctions->restaurantName);
                $otherReplacementData = array();
                $feedItems = substr($feedItems, 0, -2);
                $uname = (isset($data['lname']) && !empty($data['lname'])) ? $data ['name'] . " " . $data['lname'] : $data['name'];
                $currentTimeOrder = new \DateTime ();
                $arrivedTimeOrder = \DateTime::createFromFormat(StaticOptions::MYSQL_DATE_FORMAT, $userOrder->delivery_time);
                $currentTimeNewOrder = StaticOptions::getRelativeCityDateTime(array(
                        'restaurant_id' => $userOrder->restaurant_id
                    ))->format(StaticOptions::MYSQL_DATE_FORMAT);
                $differenceOfTimeInMin = round(abs(strtotime($arrivedTimeOrder->format("Y-m-d H:i:s")) - strtotime($currentTimeNewOrder)) / 60);
                $feed = array(
                    'restaurant_id' => $userOrder->restaurant_id,
                    'restaurant_name' => $orderFunctions->restaurantName,
                    'user_name' => ucfirst($uname),
                    'img' => array(),
                    'amount' => $orderFunctions->finalTotal,
                    'order_items' => $feedItems
                );

                if ($userOrder->order_type === "Takeout") {
                    if ($differenceOfTimeInMin <= 90) {
                        $activityFeed = $commonFunctiion->addActivityFeed($feed, 15, $replacementData, $otherReplacementData);
                    }
                } else if ($userOrder->order_type === "Delivery") {
                    if ($differenceOfTimeInMin <= 90) {
                        $activityFeed = $commonFunctiion->addActivityFeed($feed, 14, $replacementData, $otherReplacementData);
                    }
                } else {
                    $activityFeed = $commonFunctiion->addActivityFeed($feed, 1, $replacementData, $otherReplacementData);
                }
                //$userreferral->sendReferralMailUserInviter($userId,$userOrder->restaurant_id,$orderPoints);
                
                $restDetails = $userFunctions->getRestOrderFeatures($userOrder->restaurant_id);
                $cleverTap = array(
                        "user_id" => $userOrder->user_id,
                        "name"=> (isset($userOrder->lname) && !empty($userOrder->lname))?$userOrder->fname." ".$userOrder->lname:$userOrder->fname,
                        "orderid"=>$userOrderId,                        
                        "identity"=>$userOrder->email,
                        "restaurant_name" => $orderFunctions->restaurantName,
                        "restaurant_id" => $userOrder->restaurant_id,
                        "eventname" => "order",
                        "earned_points"=>$orderPoints,
                        "paid_with_point"=>($orderFunctions->discountAmountOnPoint == 0) ? 0 : (string) $orderFunctions->discountAmountOnPoint,
                        "paid_with_card"=>($userOrder->pay_via_card == 0 || $cod == 1) ? 0 : (string) $userOrder->pay_via_card,
                        "is_register" => ($userOrder->user_id && $userOrder->user_id!=0)?"yes":"no",                                                            
                        "event"=>1,                        
                        "order_type"=>$userOrder->order_type,
                        "order_date"=> date("Y-m-d",strtotime($userOrder->delivery_time)),
                        "order_time"=>date("H:i",strtotime($userOrder->delivery_time)),
                        "order_amount"=>$userOrder->total_amount,                       
                        "first_order"=>($userFunctions->total_order)?"no":"yes",
                        "delivery_enabled" => $restDetails['delivery'],
                        "takeout_enabled" => $restDetails['takeout'],
                        "reservation_enabled" => $restDetails['reservations'],
                        "deal_offer"=>(isset($userOrder->deal_id) && !empty($userOrder->deal_id))?"yes":"no"
                    );   
                                
               
                $userFunctions->createQueue($cleverTap, 'clevertap');
                return array('order' => array(
                        'id' => $userOrderId,
                        'receipt' => $userOrder->payment_receipt,
                        'points' => $userPointsOrder,
                        'orderpoints' => (string) (int) $orderPoints,
                        'order_status' => $userOrder->status,
                        'delivery_time' => $userOrder->delivery_time,
                        'order_time' => $userOrder->created_at,
                        'phone' => $userOrder->phone,
                        'capping_message'=>$cappingMessage,
                        'redeem_point'=>($orderFunctions->point==0)?"":(string)$orderFunctions->point,
                        'pay_via_point'=>($orderFunctions->discountAmountOnPoint==0)?"":(string)$orderFunctions->discountAmountOnPoint,
                        'pay_via_card' =>($userOrder->pay_via_card==0 || $cod ==1)?"":(string)$userOrder->pay_via_card,
                        'pay_via_cash' => ($cod == 1)?(string)number_format($userOrder->total_amount-$orderFunctions->discountAmountOnPoint,2):0,
                        'tax'=>$userOrder->tax,
                        'delivery_charge'=>$userOrder->delivery_charge,
                        'tip_amount'=>$userOrder->tip_amount,
                        'deal_discount'=>$userOrder->deal_discount,            
                        'subTotal' => $orderFunctions->subtotal,
                        'tip_percent'=>$userOrder->tip_percent,
                        'promocode_discount'=>$userOrder->promocode_discount,
                        'finalTotal' =>  $userOrder->total_amount,
                        'cod'=>$cod,
                        'dm_register'=>$dm_register
                    )
                );
//            }
        } catch (\Exception $ex) {
//            if (!$isPreOrderReservation) {
                $dbtable->getWriteGateway()->getAdapter()->getDriver()->getConnection()->rollback();
//            }
            throw new \Exception($ex->getMessage(), 400);
        }
    }

    public function getList() {
        $userOrderModel = new UserOrder();
        $userFunctions = new UserFunctions();
        $session = $this->getUserSession();
        $isLoggedIn = $session->isLoggedIn();

        $locationData = $session->getUserDetail('selected_location');
        $currentDate = $userFunctions->userCityTimeZone($locationData);
        $orderby = $this->getQueryParams('orderby', 'date');
        $page = $this->getQueryParams('page', 1);
        $limit = $this->getQueryParams('limit', SHOW_PER_PAGE);
        $type = $this->getQueryParams('type');
        $friendId = $this->getQueryParams('friendId', false);

        $offset = 0;
        if ($page > 0) {
            $page = ($page < 1) ? 1 : $page;
            $offset = ($page - 1) * ($limit);
        }
        $sl = $this->getServiceLocator();
        $config = $sl->get('Config');
        $orderStatus = isset($config['constants']['order_status']) ? $config['constants']['order_status'] : array();
        /**
         * Get User Live Orders
         */
        $status[] = $orderStatus[0];
        $status[] = $orderStatus[1];
        $status[] = $orderStatus[2];
        $status[] = $orderStatus[3];
        //$status[] = $orderStatus[5];
        $status[] = $orderStatus[6];
        $status[] = $orderStatus[8];

        if ($isLoggedIn) {
            if ($friendId) {
                $userId = $friendId;
            } else {
                $userId = $session->getUserId();
            }

            $options = array(
                'userId' => $userId,
                'offset' => $offset,
                'orderby' => $orderby,
                'orderStatus' => $status,
                'currentDate' => $currentDate,
                'limit' => $limit
            );
        } else {
            $email = $this->getQueryParams('email', '');
            if (empty($email)) {
                throw new \Exception('User email id is required', 400);
            }

            $userID = $userOrderModel->getUserOrder(
                array(
                    'columns' => array(
                        'user_id'
                    ),
                    'where' => array(
                        'email' => $email
                    )
                )
            );

            if ($userID['user_id'] && !empty($userID['user_id']) && $userID['user_id'] != NULL)
                throw new \Exception('User required sign in', 400);

            $options = array(
                'email' => $email,
                'offset' => $offset,
                'orderby' => $orderby,
                'orderStatus' => $status,
                'currentDate' => $currentDate,
                'limit' => $limit
            );
        }

        $liveOrder = $userOrderModel->getUserLiveOrderForMob($options);

        /**
         * Get User Live Archive Orders
         * */
        if ($isLoggedIn) {
            if ($friendId) {
                $userId = $friendId;
            } else {
                $userId = $session->getUserId();
            }

            $optionsArchive = array(
                'userId' => $userId,
                'offset' => $offset,
                'orderby' => $orderby,
                'currentDate' => $currentDate,
                'limit' => $limit
            );
        } else {
            $email = $this->getQueryParams('email', '');
            if (empty($email)) {
                throw new \Exception('User email id is required', 400);
            }

            $userID = $userOrderModel->getUserOrder(
                array(
                    'columns' => array(
                        'user_id'
                    ),
                    'where' => array(
                        'email' => $email
                    )
                )
            );

            if ($userID['user_id'] && !empty($userID['user_id']) && $userID['user_id'] != NULL)
                throw new \Exception('User required sign in', 400);

            $optionsArchive = array(
                'email' => $email,
                'offset' => $offset,
                'orderby' => $orderby,
                'currentDate' => $currentDate,
                'limit' => $limit
            );
        }
        $archiveOrder = $userOrderModel->getUserArchiveOrderForMob($options);
        $totalArchiveRecords = $archiveOrder['archive_count'];
        unset($archiveOrder['archive_count']);
        $response = array();
        if ($liveOrder) {
            $response['live_order'] = $liveOrder;
        } else {
            $response['live_order'] = array();
        }

        if ($archiveOrder) {
            $response['archive_order'] = $archiveOrder;
        } else {
            $response['archive_order'] = array();
        }
        $response['total_archive_records'] = $totalArchiveRecords;
        return $response;
    }

    public function get($id) {
        $userFunctions = new UserFunctions();
        $userOrderModel = new UserOrder();
        $preOrderModel = new \Restaurant\Model\PreOrder();
        $preOrderItemAddonsModel = new \Restaurant\Model\PreOrderAddons();
        $session = $this->getUserSession();
        $locationData = $session->getUserDetail('selected_location');
        $currentDate = $userFunctions->userCityTimeZone($locationData);
        /**
         * Get User Order Details Using Order Id
         */
        $userOrder = $userOrderModel->getUserOrder(array(
            'columns' => array(
                'id',
                'customer_first_name' => 'fname',
                'customer_last_name' => 'lname',
                'order_date' => 'created_at',
                'status',
                'order_type1',
                'order_type2',
                'delivery_date' => 'delivery_time',
                'delivery_charge',
                'tax',
                'tip_amount',
                'order_type',
                'delivery_address',
                'deal_discount',
                'deal_title',
                'order_amount',
                'card_type',
                'card_number',
                'name_on_card',
                'expired_on',
                'special_instruction' => 'special_checks',
                'user_comments',
                'restaurant_id',
                'city',
                'state_code',
                'zipcode',
                'tip_percent',
                'promocode_discount',
                'redeem_point',
                'pay_via_point',
                'pay_via_card',
                'total_amount',
                'payment_receipt',
                'apt_suite',
                'phone',
                'billing_zip',
                'is_reviewed',
                'review_id',
                'cod'
            ),
            'where' => array(
                'id' => $id
            ),
        ));

        if (!empty($userOrder) && $userOrder != null) {

            $userOrderData = $userOrder->getArrayCopy();
            $userOrderData['cod'] = (int)$userOrderData['cod'];
            $userOrderData['pay_via_cash'] = ($userOrderData['cod'] ==1) ? (string)  number_format($userOrderData['total_amount']-$userOrderData['pay_via_point'],2) : "0";
            $joins = array();
            $restaurantModel = new \Restaurant\Model\Restaurant();
            $joins [] = array(
                'name' => array(
                    'c' => 'cities'
                ),
                'on' => 'restaurants.city_id = c.id',
                'columns' => array(
                    'city_name',
                    'state_code'
                ),
                'type' => 'INNER'
            );
            $restaurant = array(
                'columns' => array(
                    'city_id',
                    'order_pass_through',
                    'restaurant_name',
                    'restaurant_address' => 'address',
                    'zipcode',
                    'rest_code',
                    'restaurant_image_name',
                    'accept_cc_phone',
                    'delivery',
                    'takeout',
                    'reservations',
                ),
                'where' => array(
                    'restaurants.id' => $userOrderData['restaurant_id']
                ),
                'joins' => $joins
            );
            $restaurantModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
            $restaurantData = $restaurantModel->find($restaurant)->toArray();
            $userOrderData['restaurant_name'] = $restaurantData[0]['restaurant_name'];
            $userOrderData['restaurant_address'] = $restaurantData[0]['restaurant_address'] . ", " . $restaurantData[0]['city_name'] . ", " . $restaurantData[0]['state_code'] . ", " . $restaurantData[0]['zipcode'];
            $userOrderData['rest_code'] = strtolower($restaurantData[0]['rest_code']);
            $userOrderData['restaurant_image_name'] = strtolower($restaurantData[0]['restaurant_image_name']);
            if($restaurantData[0]['accept_cc_phone']==1){
                $userOrderData['is_takeout'] = (int)$restaurantData[0]['takeout'];
                $userOrderData['is_delevery'] = (int)$restaurantData[0]['delivery'];                
            }else{
                $userOrderData['is_takeout'] = (int)0;
                $userOrderData['is_delevery'] = (int)0;                
            }
            
            $userOrderData['promocode_discount'] = number_format($userOrderData['promocode_discount'], 2, '.', '');
            $userOrderData['deal_discount'] = number_format($userOrderData['deal_discount'], 2, '.', '');
            $userOrderData['my_payment_details']['card_name'] = $userOrderData['name_on_card'];
            $userOrderData['my_payment_details']['card_number'] = $userOrderData['card_number'];
            $userOrderData['my_payment_details']['card_type'] = $userOrderData['card_type'];
            if($userOrderData['expired_on']){
            $expired_on = explode("/", $userOrderData['expired_on']);
            $userOrderData['my_payment_details']['expiry_year'] = $expired_on[1];
            $userOrderData['my_payment_details']['expiry_month'] = $expired_on[0];
            }else{
            $expired_on[1] = '';
            $expired_on[0] = '';
            $userOrderData['my_payment_details']['expiry_year'] = '';
            $userOrderData['my_payment_details']['expiry_month'] = '';
            }
            $userOrderData['my_payment_details']['billing_zip'] = $userOrderData['billing_zip'];

            $userOrderData['my_delivery_detail']['first_name'] = $userOrderData['customer_first_name'];
            $userOrderData['my_delivery_detail']['last_Name'] = $userOrderData['customer_last_name'];
            $userOrderData['my_delivery_detail']['city'] = $userOrderData['city'];
            $userOrderData['my_delivery_detail']['apt_suite'] = $userOrderData['apt_suite'];
            $userOrderData['my_delivery_detail']['state'] = $userOrderData['state_code'];
            $userOrderData['my_delivery_detail']['phone'] = $userOrderData['phone'];
            if (!empty($userOrderData['delivery_address'])) {
                $address = $userOrderData['delivery_address'] . ', ' . $userOrderData['city'] . ', ' . $userOrderData['state_code'] . ', ' . $userOrderData['zipcode'];
            } else {
                $address = $userOrderData['city'] . ', ' . $userOrderData['state_code'] . ', ' . $userOrderData['zipcode'];
            }
            $userOrderData['my_delivery_detail']['address'] = $address;
            $userOrderData['my_delivery_detail']['zipcode'] = $userOrderData['zipcode'];

            $userOrderData ['order_amount_calculation']['subtotal'] = number_format($userOrderData['order_amount'], 2, '.', '');
            $userOrderData ['order_amount_calculation']['tax_amount'] = $userOrderData['tax'];
            $userOrderData ['order_amount_calculation']['tip_amount'] = $userOrderData['tip_amount'];
            $userOrderData ['order_amount_calculation']['delivery_charge'] = $userOrderData['delivery_charge'];
            $userOrderData ['order_amount_calculation']['discount'] = ($userOrderData['deal_discount'] > 0) ? $userOrderData['deal_discount'] : "0";
            $userOrderData ['order_amount_calculation']['promocode_discount'] = ($userOrderData['promocode_discount'] > 0) ? $userOrderData['promocode_discount'] : "0";
            $userOrderData ['order_amount_calculation']['redeem_point'] = ($userOrderData['redeem_point'] > 0) ? $userOrderData['redeem_point'] : "0";
            $userOrderData ['order_amount_calculation']['pay_via_point'] = ($userOrderData['pay_via_point'] > 0) ? $userOrderData['pay_via_point'] : "0";
            $userOrderData ['order_amount_calculation']['pay_via_card'] = ($userOrderData['pay_via_card'] > 0 && $userOrderData['cod'] == 0) ? $userOrderData['pay_via_card'] : "0";
            $userOrderData ['order_amount_calculation']['pay_via_cash'] = ($userOrderData['cod'] ==1) ? $userOrderData['total_amount']-$userOrderData['pay_via_point'] : "0";
            $userOrderData ['order_amount_calculation']['total_order_price'] = $userOrderData['total_amount'];
            $userOrderData['delivery_address'] = $userOrderData['delivery_address'] . ", " . $userOrderData['city'] . ", " . $userOrderData['state_code'] . ", " . $userOrderData['zipcode'];

            if (isset($userOrderData) && !empty($userOrderData)) {
                if ($userOrderData['expired_on']) {
                    $months = explode('/', $userOrderData['expired_on']);
                    $year = substr($months[1], - 2, 2);
                    $userOrderData['expired_on'] = $months[0] . '/' . $year;
                } else {
                    $userOrderData['expired_on'] = '';
                }

                $userOrderData['card_type'] = strtoupper($userOrderData['card_type']);
                $userOrderData['order_type'] = ucfirst($userOrderData['order_type']);
                $userOrderDetailModel = new UserOrderDetail();
                $joins_city = array();
                $joins_city [] = array(
                    'name' => array(
                        'm' => 'menus'
                    ),
                    'on' => 'user_order_details.item_id=m.id',
                    'columns' => array(
                        'item_status' => 'status',
                        'online_order_allowed'
                    ),
                    'type' => 'left'
                );
                $userOrderItem = $userOrderDetailModel->getAllOrderDetail(array(
                    'columns' => array(
                        'id',
                        'order_item_id' => 'item_id',
                        'item_name' => 'item',
                        'item_price_id',
                        'item_qty' => 'quantity',
                        'unit_price' => 'unit_price',
                        'item_special_instruction' => 'special_instruction',
                    ),
                    'where' => array(
                        'user_order_id' => $id
                    ),
                    'joins' => $joins_city
                ));
                $itemStatus = (int)$userOrderItem[0]['item_status'];
                $onlineOrderAllowed = (int)$userOrderItem[0]['online_order_allowed'];
                
                $userOrderItem[0]['item_status'] = ($itemStatus==1 && $onlineOrderAllowed==1)?(int)1:(int)0;
                $userOrderItem[0]['item_name'] = html_entity_decode(htmlspecialchars_decode($userOrderItem[0]['item_name'], ENT_QUOTES));
                $userOrderItem[0]['item_special_instruction'] = html_entity_decode(htmlspecialchars_decode($userOrderItem[0]['item_special_instruction'], ENT_QUOTES));
                $userOrderData['item_list'] = $userOrderItem;
                $userOrderData['card_type'] = strtoupper($userOrderData['card_type']);
                $expiredOn = $userOrderData['expired_on'];
                if (!empty($expiredOn)) {
                    $months = explode('/', $expiredOn);
                    $year = substr($months[1], - 2, 2);
                    $userOrderData['expired_on'] = $months[0] . '/' . $year;
                }

                /**
                 * Get User Order Item Addons Using User Order Item Id
                 */
                $userOrderAddonsModel = new UserOrderAddons();
                $i = 0;
                $joins_addons = array();
                $joins_addons [] = array(
                        'name' => array(
                            'ma' => 'menu_addons'
                        ),
                        'on' => new \Zend\Db\Sql\Expression("user_order_addons.menu_addons_option_id=ma.id"),
                        'columns' => array(
                            'addon_status' => 'status',                        
                        ),
                        'type' => 'left'
                    );
                foreach ($userOrderData['item_list'] as $key1 => $value) {
                    $orderItemId = $userOrderData['item_list'][$i]['id'];
                    
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
                        ),
                        'joins'=>$joins_addons
                    ));
                    $cc = array();
                    $j = 0;

                    foreach ($addon as $key => $result) {

                        if ($result['addons_option'] == 'None') {
                            continue;
                        } else {
                            $addons['addons_id'] = $result['menu_addons_id'];
                            $addons['addon_name'] = $userFunctions->to_utf8($result['addons_option']);
                            $addons['addon_price'] = $result['price'];
                            $addons['addons_total_price'] = number_format($result['price'] * $result['quantity'], 2);
                            $addons['addon_quantity'] = $result['quantity'];
                            $addons['addon_status']= $result['addon_status'];
                            $cc[$j] = $addons;
                            $j++;
                        }
                    }
                    $userOrderData['item_list'][$i]['item_name'] = $userFunctions->to_utf8($userOrderData['item_list'][$i]['item_name']);

                    if (!empty($cc)) {
                        $userOrderData['item_list'][$i]['addons_list'] = $cc;
                        $cc = array();
                    } else {
                        $userOrderData['item_list'][$i]['addons_list'] = array();
                    }
                    $i ++;
                }
                $orderSubTotal = 0;
                $orderSubTotal = (float) $userOrderData['order_amount'];

                $orderTax = (float) $userOrderData['tax'];
                if (is_numeric($orderTax) & $orderTax != 0) {
                    $userOrderData['tax'] = number_format($orderTax, 2);
                    $orderSubTotal = $orderSubTotal + $orderTax;
                }
                $orderTip = (float) $userOrderData['tip_amount'];
                if (is_numeric($orderTip) & $orderTip != 0) {
                    $userOrderData['tip_amount'] = number_format($orderTip, 2);
                    $orderSubTotal = $orderSubTotal + $orderTip;
                }
                $orderDelCharge = (float) $userOrderData['delivery_charge'];
                if (is_numeric($orderDelCharge) & $orderDelCharge != 0) {
                    $userOrderData['delivery_charge'] = number_format($orderDelCharge, 2);
                    $orderSubTotal = $orderSubTotal + $orderDelCharge;
                }
                $orderDiscount = (float) $userOrderData['deal_discount'];
                if (is_numeric($orderDiscount) & $orderDiscount != 0) {
                    $userOrderData['deal_discount'] = number_format($orderDiscount, 2);
                    $orderSubTotal = $orderSubTotal - $orderDiscount;
                }
                $userOrderData['order_amount'] = number_format($userOrderData['order_amount'], 2, '.', ',');
                $userOrderData['is_reviewed'] = ($userOrderData['is_reviewed'] == "") ? intval(0) : intval($userOrderData['is_reviewed']);
                $userOrderData['review_id'] = ($userOrderData['review_id'] == "") ? intval(0) : intval($userOrderData['review_id']);

                unset($userOrderData['name_on_card'], $userOrderData['card_number'], $userOrderData['card_type'], $expired_on[1], $expired_on[0], $userOrderData['billing_zip'], $userOrderData['delivery_charge'], $userOrderData['tax'], $userOrderData['tip_amount'], $userOrderData['delivery_address'], $userOrderData['deal_discount'], $userOrderData['deal_title'], $userOrderData['order_amount'], $userOrderData['expired_on'], $userOrderData['user_comments'], $userOrderData['city'], $userOrderData['state_code'], $userOrderData['zipcode'], $userOrderData['apt_suite'], $userOrderData['phone'], $userOrderData['promocode_discount'],$userOrderData['redeemed_point'], $userOrderData['item_list'][0]['id'], $userOrderData['item_list'][0]['item_price_id']);
                return $userOrderData;
            }
        }else{
            throw new \Exception('Order details not found');
        }
    }

    private function savePreOrderReservation($data, $userId, $orderPass) {

        $orderReturnData = array();
        $dbtable = new UserOrderTable ();
        $userId = $this->getUserSession()->getUserId();
        $userFunctions = new UserFunctions();
        $userreferral = new \User\Model\UserReferrals();
        $userPoints = 0;
        $userPointsOrder = 0;
        $orderPoints = 0;
        if ($userId) {
            $userPointsCount = new \User\Model\UserPoint();
            $userPointsSum = $userPointsCount->countUserPoints($userId);
            $redeem_points = $userPointsSum[0]['redeemed_points'];
            $userPoints = strval($userPointsSum[0]['points'] - $redeem_points);
        }
        $data["reservation_details"]["user_instruction"] = $data ['order_details'] ['special_instruction'];
        try {
            $dbtable = new UserOrderTable ();
            $orderData = $data;
            /* Create order */            
            $connection = $dbtable->getWriteGateway()->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();
            $data['do_transaction'] = false;
            $data["is_preorder_reservation"] = false;
            $orderReturnData = $this->create($orderData);
            if (isset($orderReturnData['error'])) {
                throw new \Exception($orderReturnData['error']);
            }
            ###########################################   

            $returnData = array("order" => $orderReturnData);

            if (isset($data["reservation_details"]['reservation_id']) && !empty($data["reservation_details"]['reservation_id'])) {
                $userReservation = new UserReservation();
                $updateOrderIdData = array(
                    'order_id' => $orderReturnData['id'],
                    'party_size' => $data["reservation_details"]['reserved_seats'],
                    'reserved_seats' => $data["reservation_details"]['reserved_seats'],
                    'time_slot' => $data["reservation_details"]['time_slot'],
                );
                $userReservation->id = $data["reservation_details"]['reservation_id'];
                $userReservation->update($updateOrderIdData);
                $resDetail = $userReservation->getUserReservation(array(
                    'columns' => array(
                        'restaurant_name',
                        'receipt_no'
                    ),
                    'where' => array(
                        'id' => $data["reservation_details"]['reservation_id']
                    )
                ));

                $updateReturn = $userReservation->update($updateOrderIdData);
                if ($updateReturn) {
                    $userTransaction = $userFunctions->getFirstTranSactionUser();
                    if ($userTransaction == 0) {
                        $orderPoints = $orderReturnData['orderpoints'] + 110;
                        $userPointsOrder = $userPoints + $orderPoints;
                    } else {
                        $orderPoints = $orderReturnData['orderpoints'] + 10;
                        $userPointsOrder = $userPoints + $orderPoints;
                    }
//                        $userPointsModel = new \User\Model\UserPoint();
//                        $message = "You have upcoming plans! This calls for a celebration, here are " . $orderPoints . " points!";
//                        $pointdata = array(
//                            'user_id' => $userId,
//                            'point_source' => '3',
//                            'points' => $orderPoints,
//                            'points_descriptions' => $message,
//                            'ref_id' => $userReservation->id
//                        );
//                       
//                      $userPointsModel->ref_id =$userReservation->id; 
//                      $updatepoints =  $userPointsModel->updatePointDetail($pointdata);
//                    if($updatepoints){
//                        $userPointsOrder = $userPointsOrder-10;
//                    }
                    $returnData["reservation"]['reservation_id'] = $data["reservation_details"]['reservation_id'];
                    $returnData["reservation"]['receipt_no'] = $resDetail[0]['receipt_no'];
                    $returnData["reservation"]['date'] = $data["reservation_details"]['date'];
                    $returnData["reservation"]['time'] = $data["reservation_details"]['time'];
                    $returnData["reservation"]['reserved_seats'] = $data["reservation_details"]['reserved_seats'];
                    $returnData["reservation"]['time_slot'] = $data["reservation_details"]['time_slot'];
                    $data["reservation_details"]['restaurant_name'] = $resDetail[0]['restaurant_name'];
                    $returnData["reservation"]['points'] = $userPoints;
                    $returnData["reservation"]['orderpoints'] = (int) $orderPoints;
                    $returnData['order']['points'] = $userPoints;
                    $returnData['order']['orderpoints'] = (int) $orderPoints;
                    $returnData['order']['redeem_point']="";
                    $returnData['order']['pay_via_point']="";
                    $returnData['order']['pay_via_card']="";
                }
            } else {
                $reservationController = $this->getServiceLocator()->get("User\Controller\ReservationController");
                $reservationData = $data["reservation_details"];
                $reservationData['token'] = $data['token'];
                $reservationData["order_id"] = $orderReturnData["id"];
                $reservationData["order_point"] = $orderReturnData["orderpoints"];
                $reservationData["finalTotal"] = $returnData['order']['finalTotal'];
                $reservationReturnData = $reservationController->create($reservationData);
                if (isset($reservationReturnData['error']) && $reservationReturnData['error'] == 1) {
                    throw new \Exception($reservationReturnData['msg']);
                }
                $returnData['order']['points'] = $reservationReturnData['points'];
                $returnData['order']['orderpoints'] = $reservationReturnData['orderpoints'];
                $returnData["reservation"] = $reservationReturnData;
                $returnData["reservation"]['reservation_status'] = 1;
                $returnData["order"]['dine_more_wards'] = $reservationReturnData['dine_more_wards'];
                $returnData['order']['redeem_point']=$orderReturnData['redeem_point'];
                $returnData['order']['pay_via_point']=$orderReturnData['pay_via_point'];
                $returnData['order']['pay_via_card'] = $orderReturnData['pay_via_card'];
            }
            $connection->commit();
            //$userreferral->sendReferralMailUserInviter($userId,$reservationReturnData['restaurant_id']);
            //$userFunctions->preOrderReservationMail($data, $returnData, $userId, $orderPass);
        } catch (\Exception $ex) {
            $connection->rollback();
            throw new \Exception($ex->getMessage(), 400);
        }

        unset($returnData['order']['subTotal']);
        unset($returnData['order']['tax']);
        unset($returnData['order']['tipAmount']);
        unset($returnData['order']['dealDiscount']);
        unset($returnData['order']['promocodeDiscount']);
        unset($returnData['order']['finalTotal']);
        $orderTimeSlot = explode("-", ORDER_TIME_SLOT);
        $cappingMessage=(CRM_CAPPING)?'We process all orders and reservation between '.date("h:i A",strtotime($orderTimeSlot[0].":00")).' and '.date("h:i A",strtotime($orderTimeSlot[1])).' EST':'';
        $returnData['capping_message']=$cappingMessage;
        return $returnData;
    }

    public function update($order_id, $data) {

        $session = $this->getUserSession();
        $isLoggedIn = $session->isLoggedIn();
        if ($isLoggedIn) {
            $user_id = $session->getUserId();
        } else {
            throw new \Exception("Not a valid user", 400);
        }

        if ($order_id) {
            $userOrder = new UserOrder ();
            $options = array('columns' => array('status'), 'where' => array('id' => $order_id, 'user_id' => $user_id));
            if ($userOrder->getUserOrder($options)) {
                $orderDetail = $userOrder->getUserOrder($options)->getArrayCopy();
            } else {
                throw new \Exception("Order not exist", 400);
            }
            if ($orderDetail['status'] == 'placed') {
                $userOrder->id = $order_id;
                $userOrder->user_comments = $data['comment'];
                $userOrder->status = 'cancelled';
                $deleted = $userOrder->delete();
                return array(
                    "canceled" => (bool) $deleted
                );
            } else {
                throw new \Exception("Order will not canceled", 400);
            }
        } else {
            throw new \Exception('Order id is not valid');
        }
    }

}
