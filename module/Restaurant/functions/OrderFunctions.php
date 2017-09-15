<?php

namespace Restaurant;

use Restaurant\Model\Menu;
use Restaurant\Model\MenuPrices;
use Restaurant\Model\MenuAddons;
use User\Model\UserCard;
use Restaurant\Model\Restaurant;
use Home\Model\City;
use MCommons\StaticOptions;
use Restaurant\Model\MenuBookmark;
use Restaurant\Model\MenuAddonsSetting;
use Restaurant\Model\RestaurantAccounts;

class OrderFunctions {

    public $itemDetails = array();
    public $subtotal;
    public $tax = 0;
    public $dealDiscount = 0;
    public $finalTotal = 0;
    public $tipAmount = 0;
    public $tipPercent = 0;
    public $restaurantName;
    public $deliveryCharge = 0.00;
    public $dealAmount = 0;
    public $procodeAmount = 0;
    public $promocodeDiscount = 0;
    public $priceForPromocodeCal = 0;
    public $deal_id = '';
    public $deal_title = '';
    public $user_order_point = 0;
    public $orderPass = 0;
    public $point=0;
    public $discountAmountOnPoint=0;
    public $restaurant_id;
    public $pay_via_card;
    public $pay_via_point;
    public $deal_type='';
    public $dine_more_point = 0;
    public $host_name;
    public $isAppliedPromo=0;
    public $user_id=0;
    public $order_id;
    public $promocodeType=0;
    public $balenceBudget = 0;
    public $promocodeId = 0;
  
    public function calculatePrice($data, $dealDetails = array(), $promocodeDetails = array()) {
        //$data = $this->formatData ( $data );        
        $orderType = strtolower($data['order_type']);
        $price = 0;
        foreach ($data ['items'] as $key => $value) {
            $price = $price + $this->calculatePriceForSingleItem($data ['items'] [$key], $data ['restaurant_id']);
        }

        $this->subtotal = $price;
        $this->tax = $this->calculateTax($data ['restaurant_id']);
        if ($orderType === 'delivery') {
            $this->deliveryCharge = $this->calculateDeliveryCharge($data ['restaurant_id']);

            //$this->checkDealDiscount($data ['restaurant_id']);
            if (isset($data ['tiptype'])) {
                if ($data ['tiptype'] == 'c') {
                    $this->tipPercent = 0;
                    $this->tipAmount = $data ['tip_percent'];
                } elseif ($data ['tiptype'] == 'p') {
                    if (isset($data ['tip_percent']) && $data ['tip_percent'] != '') {
                        $this->tipPercent = $data ['tip_percent'];
                        $this->tipAmount = $this->calculateTipAmount($data ['tip_percent']);
                    }
                }
            } else {
                if (isset($data ['tip_percent']) && $data ['tip_percent'] != '') {
                    $this->tipPercent = $data ['tip_percent'];
                    $this->tipAmount = $this->calculateTipAmount($data ['tip_percent']);
                }
            }
        }

        if (!empty($dealDetails)) {
            if (in_array($orderType, explode(",", $dealDetails['deal_for']))) {
                if ($dealDetails['discount_type'] === 'flat' && $price >= $dealDetails['minimum_order_amount']) {
                    $this->dealDiscount = $dealDetails['discount'];
                    $price = $price - $dealDetails['discount'];
                    $this->deal_id = $dealDetails['id'];
                    $this->deal_title = $dealDetails['title']; 
                    $this->deal_type = $dealDetails['discount_type'];
                    if($dealDetails['user_deals']==1 && $dealDetails['deal_used_type']==1){
                        $this->dealAvailedByUser();                        
                    }
                } elseif ($dealDetails['discount_type'] === 'percent' && $price >= $dealDetails['minimum_order_amount']) {
                    $this->dealDiscount = $this->calculatePercentAmount($this->subtotal, $dealDetails['discount']);
                    $price = $price - $this->dealDiscount;
                    $this->deal_id = $dealDetails['id'];
                    $this->deal_title = $dealDetails['title'];
                    $this->deal_type = $dealDetails['discount_type'];
                    if($dealDetails['user_deals']==1 && $dealDetails['deal_used_type']==1){
                        $this->dealAvailedByUser();
                    }
                }
            }
        }
        if (!empty($promocodeDetails) && $this->orderPass==0 && isset($promocodeDetails['minimum_order_amount']) && $price >= $promocodeDetails['minimum_order_amount'] && $promocodeDetails['discount_coupon']==1) {
            if ($promocodeDetails['discount_type'] === 'flat') {
                $this->promocodeType = $promocodeDetails['promocodeType'];
                $this->priceForPromocodeCal = $price;
                $this->procodeAmount = $promocodeDetails['discount'];
                if($this->checkBudgetAvailable($promocodeDetails)){
                    $price = $price - $promocodeDetails['discount'];
                    $this->promocodeDiscount = $promocodeDetails['discount'];  
                    $this->isAppliedPromo = 1;
                }
            } elseif ($promocodeDetails['discount_type'] === 'percent') {
                $this->promocodeType = $promocodeDetails['promocodeType'];
                $this->priceForPromocodeCal = $price;
                $this->procodeAmount = $this->calculatePercentAmount($price, $promocodeDetails['discount']);
                if($this->checkBudgetAvailable($promocodeDetails)){
                    $price = $price - $this->procodeAmount;

                    if ($price > 0 && $price < APPLIED_FINAL_TOTAL) {
                        $this->procodeAmount = $this->procodeAmount + $price;
                        $this->promocodeDiscount = $this->procodeAmount; 
                        $this->isAppliedPromo = 1;
                    }
                    $this->promocodeDiscount = $this->procodeAmount;
                }
            }
            $this->user_order_point = floor($price);
            $this->dine_more_point = $price;
        }
        
        $price = $price + $this->tax;
        $price = $price + $this->deliveryCharge;
        $price = $price + $this->tipAmount;
        $price = $this->trimDecimals($price, 2);
        $this->user_order_point = floor($price);
        $this->dine_more_point = $price;
        if (!empty($promocodeDetails) && $this->orderPass==0 && $price >= $promocodeDetails['minimum_order_amount'] && $promocodeDetails['discount_coupon']==0) {
            if ($promocodeDetails['discount_type'] === 'flat') {
                $this->priceForPromocodeCal = $price;
                $this->procodeAmount = $promocodeDetails['discount'];
                $price = $price - $promocodeDetails['discount'];
                $this->promocodeDiscount = $promocodeDetails['discount'];  
                $this->isAppliedPromo = 1;
            } elseif ($promocodeDetails['discount_type'] === 'percent') {
                $this->priceForPromocodeCal = $price;
                $this->procodeAmount = $this->calculatePercentAmount($price, $promocodeDetails['discount']);
                $price = $price - $this->procodeAmount;
                if ($price > 0 && $price < APPLIED_FINAL_TOTAL) {
                    $this->procodeAmount = $this->procodeAmount + $price;
                    $this->promocodeDiscount = $this->procodeAmount; 
                     $this->isAppliedPromo = 1;
                }
                $this->promocodeDiscount = $this->procodeAmount;
            }
            $this->user_order_point = floor($price);
            $this->dine_more_point = $price;
        }
        
        if($this->point > 0 && $this->orderPass==0){
            if($this->checkBalencePoint() && $price >= APPLIED_FINAL_TOTAL){
                $this->discountOnRedeemPoint();
                $this->user_order_point = floor($price);
                $this->dine_more_point = $price;
                $price = $price-$this->discountAmountOnPoint;                                 
            }
        }elseif (floor($price) < 0 || floor($price) < APPLIED_FINAL_TOTAL) {
            $this->user_order_point = 0;
            $this->dine_more_point = 0;
        }

        if ($price <= 0) {
            $this->finalTotal = 0;
        } else {
            $this->finalTotal = $price;
        }
        //$amount = array('finalTotal'=>$this->finalTotal,'priceForPromocodeCal'=>$priceForPromocodeCal,'dealDiscount'=>$this->dealDiscount,'promocodeDiscount'=>$this->promocodeDiscount);
        return $this->finalTotal;
    }
    
    public function checkBudgetAvailable($promocodeDetails){       
        $this->balenceBudget = $promocodeDetails['budget'];        
        if($promocodeDetails['promocodeType']==3){
            if($promocodeDetails['budget']>=$this->procodeAmount){
                $this->promocodeId = $promocodeDetails['id'];
                $this->promocodeType = $promocodeDetails['promocodeType'];
                $this->balenceBudget = $promocodeDetails['budget']-$this->procodeAmount;     
                
                return true;
            }else{
                return false;
            }              
        }else{
            return true;
        }
    }
    
    public function calculatePriceForCron($data, $dealDetails = array(), $promocodeDetails = array(),$paramUserId=false) {
        //$data = $this->formatData ( $data );
        $orderType = strtolower($data['order_type']);
        $price = 0;
        foreach ($data ['items'] as $key => $value) {
            $price = $price + $this->calculatePriceForSingleItemForCron($data ['items'] [$key], $data ['restaurant_id'],$paramUserId);
        }

        $this->subtotal = $price;
        $this->tax = $this->calculateTax($data ['restaurant_id']);
        if ($orderType === 'delivery') {
            $this->deliveryCharge = $this->calculateDeliveryCharge($data ['restaurant_id']);

            //$this->checkDealDiscount($data ['restaurant_id']);
            if (isset($data ['tiptype'])) {
                if ($data ['tiptype'] == 'c') {
                    $this->tipPercent = 0;
                    $this->tipAmount = $data ['tip_percent'];
                } elseif ($data ['tiptype'] == 'p') {
                    if (isset($data ['tip_percent']) && $data ['tip_percent'] != '') {
                        $this->tipPercent = $data ['tip_percent'];
                        $this->tipAmount = $this->calculateTipAmount($data ['tip_percent']);
                    }
                }
            } else {
                if (isset($data ['tip_percent']) && $data ['tip_percent'] != '') {
                    $this->tipPercent = $data ['tip_percent'];
                    $this->tipAmount = $this->calculateTipAmount($data ['tip_percent']);
                }
            }
        }

        if (!empty($dealDetails)) {
            if (in_array($orderType, explode(",", $dealDetails['deal_for']))) {
                if ($dealDetails['discount_type'] === 'flat' && $dealDetails['status'] == 1 && $price >= $dealDetails['minimum_order_amount']) {
                    $this->dealDiscount = $dealDetails['discount'];
                    $price = $price - $dealDetails['discount'];
                    $this->deal_id = $dealDetails['id'];
                    $this->deal_title = $dealDetails['title'];
                } elseif ($dealDetails['discount_type'] === 'percent' && $dealDetails['status'] == 1 && $price >= $dealDetails['minimum_order_amount']) {
                    $this->dealDiscount = $this->calculatePercentAmount($this->subtotal, $dealDetails['discount']);
                    $price = $price - $this->dealDiscount;
                    $this->deal_id = $dealDetails['id'];
                    $this->deal_title = $dealDetails['title'];
                }
            }
        }

        $price = $price + $this->tax;
        $price = $price + $this->deliveryCharge;
        $price = $price + $this->tipAmount;
        $price = $this->trimDecimals($price, 2);
        $this->user_order_point = floor($price);
        if (!empty($promocodeDetails)) {
            if ($promocodeDetails['discount_type'] === 'flat') {
                $this->priceForPromocodeCal = $price;
                $this->procodeAmount = $promocodeDetails['discount'];
                $price = $price - $promocodeDetails['discount'];
                $this->promocodeDiscount = $promocodeDetails['discount'];
            } elseif ($promocodeDetails['discount_type'] === 'percent') {
                $this->priceForPromocodeCal = $price;
                $this->procodeAmount = $this->calculatePercentAmount($price, $promocodeDetails['discount']);
                $price = $price - $this->procodeAmount;
                if ($price > 0 && $price < APPLIED_FINAL_TOTAL) {
                    $this->procodeAmount = $this->procodeAmount + $price;
                    $this->promocodeDiscount = $this->procodeAmount;
                }
                $this->promocodeDiscount = $this->procodeAmount;
            }
            $this->user_order_point = floor($price);
        }

        if (floor($price) < 0 || floor($price) < APPLIED_FINAL_TOTAL) {
            $this->user_order_point = 0;
        }

        if ($price <= 0) {
            $this->finalTotal = 0;
        } else {
            $this->finalTotal = $price;
        }
        //$amount = array('finalTotal'=>$this->finalTotal,'priceForPromocodeCal'=>$priceForPromocodeCal,'dealDiscount'=>$this->dealDiscount,'promocodeDiscount'=>$this->promocodeDiscount);
        return $this->finalTotal;
    }

    public function formatData($data) {
        if (isset($data ['items']) && is_array($data ['items'])) {
            foreach ($data ['items'] as $key => $value) {
                if (isset($data ['items'] [$key] ['addons']) && $data ['items'] [$key] ['addons'] != '') {

                    $data ['items'] [$key] ['addons'] = explode(',', $data ['items'] [$key] ['addons']);
                }
            }
        }
        return $data;
    }

    public function calculatePriceForSingleItem($item, $restaurantId) {
        $price = 0;
        $userId = StaticOptions::getUserSession()->getUserId();
        $currentTime = StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurantId
                ))->format(StaticOptions::MYSQL_DATE_FORMAT);
        $menu = new Menu ();
        $options = array(
            'columns' => array(
                'id',
                'item_name',
                'item_desc'
            ),
            'where' => array(
                'id' => $item ['item_id'],
            //'status'=>1
            )
        );
        $menu->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $menuDetails = $menu->find($options)->toArray();

//        $joins = array();
//        $joins [] = array(
//            'name' => array(
//                'm' => 'menus'
//            ),
//            'on' => 'm.id = menu_prices.menu_id',
//            'columns' => array(
//                'id',
//                'item_name',
//                'item_desc'
//            ),
//            'type' => 'inner'
//        );
//        
//        $options = array(
//                'columns' => array(
//                    'price',
//                    'price_desc'
//                ),
//                'where' => array(
//                    'menu_prices.id' => isset($item ['price_id'])?$item ['price_id']:$item['item_price_id'],
//                    'm.status'=>1,
//                    'm.id'=>$item ['item_id']
//                ),
//                'joins'=>$joins,
//            );
//         $menuPrice = new MenuPrices ();
//         $menuPrice->getDbTable()->setArrayObjectPrototype('ArrayObject');
//         $menuPriceDetails = $menuPrice->find($options)->toArray();

        if ($menuDetails) {
            $menuPrice = new MenuPrices ();
            $menuPrice->getDbTable()->setArrayObjectPrototype('ArrayObject');
            $options = array(
                'columns' => array(
                    'price',
                    'price_desc'
                ),
                'where' => array(
                    'id' => isset($item ['price_id']) ? $item ['price_id'] : $item['item_price_id']
                )
            );
            $menuPriceDetails = $menuPrice->find($options)->toArray();

            // if($menuPriceDetails){
            $this->itemDetails [$item ['id']] = array(
                'item_id' => $item ['item_id'],
                //'item_name' => $menuPriceDetails [0] ['item_name'],
                //'item_desc' => $menuPriceDetails [0] ['item_desc'],
                'item_name' => $menuDetails [0] ['item_name'],
                'item_desc' => $menuDetails [0] ['item_desc'],
                'quantity' => $item ['quantity'],
                'price_id' => isset($item ['price_id']) ? $item ['price_id'] : $item['item_price_id'],
                'unit_price' => isset($menuPriceDetails [0] ['price']) ? $menuPriceDetails [0] ['price'] : 0,
                'total_item_amount' => isset($menuPriceDetails [0] ['price']) ? $menuPriceDetails [0] ['price'] * $item ['quantity'] : 0,
                'price_desc' => isset($menuPriceDetails [0] ['price_desc']) ? $menuPriceDetails [0] ['price_desc'] : "",
                'special_instruction' => isset($item ['special_instruction'])?$item ['special_instruction']:"",
                'deal_id'=>isset($item['deal_id'])?$item['deal_id']:""
            );
            $addonsWorthValue = 0;
            if (!empty($item ['addons'])) {
                $addonsWorthValue = $this->getAddonsPrice($item ['addons'], $item ['id']);
            }
            if (isset($menuPriceDetails [0] ['price'])) {
                $price = ($addonsWorthValue + $menuPriceDetails [0] ['price']) * $item ['quantity'];
            } else {
                $price = ($addonsWorthValue + 0) * $item ['quantity'];
            }
            $this->itemDetails [$item ['id']] ['total_item_amount'] = $price;
            if ($userId) {
                // create auto bookmarks
                $menuBookmarkModel = new MenuBookmark ();
                $bookmarkData = array(
                    'user_id' => $userId,
                    'menu_name' => $menuDetails [0] ['item_name'],
                    'menu_id' => $item ['item_id'],
                    'restaurant_id' => $restaurantId,
                    'created_on' => $currentTime,
                    'type' => 'ti'
                );
                $menuBookmarkModel->createBookmark($bookmarkData);
            }
//            }else{
//                
//                $sl = Staticoptions::getServiceLocator();
//                $request = $sl->get('request');
//                $requestType = (bool) $request->getQuery('mob', false) ? true : false;
//        
//                $msg = "";
//                $orderEditLink = "go back to edit your order";
//                $msg .= "<div>Sorry we could not process your order as some of the items you selected are no longer offered by the restaurant. Please "; 
//                $msg .= '<span class="txt_editorder">'.$orderEditLink.'</span>';
//                $msg .= " and checkout again</div>";
//                if($requestType){
//                   $msg = "Sorry we could not process your order as some of the items you selected are no longer offered by the restaurant. Please go back to edit your order and checkout again"; 
//                }
//                throw new \Exception($msg);                
//            }
        }
        return $price;
    }
    
    
    public function calculatePriceForSingleItemForCron($item, $restaurantId,$uId) {
        $price = 0;
        $userId = $uId;
        $currentTime = StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurantId
                ))->format(StaticOptions::MYSQL_DATE_FORMAT);
        $menu = new Menu ();
        $options = array(
            'columns' => array(
                'id',
                'item_name',
                'item_desc'
            ),
            'where' => array(
                'id' => $item ['item_id'],
            //'status'=>1
            )
        );
        $menu->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $menuDetails = $menu->find($options)->toArray();

        if ($menuDetails) {
            $menuPrice = new MenuPrices ();
            $menuPrice->getDbTable()->setArrayObjectPrototype('ArrayObject');
            $options = array(
                'columns' => array(
                    'price',
                    'price_desc'
                ),
                'where' => array(
                    'id' => isset($item ['price_id']) ? $item ['price_id'] : $item['item_price_id']
                )
            );
            $menuPriceDetails = $menuPrice->find($options)->toArray();

            // if($menuPriceDetails){
            $this->itemDetails [$item ['id']] = array(
                'item_id' => $item ['item_id'],
                //'item_name' => $menuPriceDetails [0] ['item_name'],
                //'item_desc' => $menuPriceDetails [0] ['item_desc'],
                'item_name' => $menuDetails [0] ['item_name'],
                'item_desc' => $menuDetails [0] ['item_desc'],
                'quantity' => $item ['quantity'],
                'price_id' => isset($item ['price_id']) ? $item ['price_id'] : $item['item_price_id'],
                'unit_price' => isset($menuPriceDetails [0] ['price']) ? $menuPriceDetails [0] ['price'] : 0,
                'total_item_amount' => isset($menuPriceDetails [0] ['price']) ? $menuPriceDetails [0] ['price'] * $item ['quantity'] : 0,
                'price_desc' => isset($menuPriceDetails [0] ['price_desc']) ? $menuPriceDetails [0] ['price_desc'] : "",
                'special_instruction' => $item ['special_instruction']
            );
            $addonsWorthValue = 0;
            if (!empty($item ['addons'])) {
                $addonsWorthValue = $this->getAddonsPrice($item ['addons'], $item ['id']);
            }
            if (isset($menuPriceDetails [0] ['price'])) {
                $price = ($addonsWorthValue + $menuPriceDetails [0] ['price']) * $item ['quantity'];
            } else {
                $price = ($addonsWorthValue + 0) * $item ['quantity'];
            }
            $this->itemDetails [$item ['id']] ['total_item_amount'] = $price;
            if ($userId) {
                // create auto bookmarks
                $menuBookmarkModel = new MenuBookmark ();
                $bookmarkData = array(
                    'user_id' => $userId,
                    'menu_name' => $menuDetails [0] ['item_name'],
                    'menu_id' => $item ['item_id'],
                    'restaurant_id' => $restaurantId,
                    'created_on' => $currentTime,
                    'type' => 'ti'
                );
                $menuBookmarkModel->createBookmark($bookmarkData);
            }
        }
        return $price;
    }

    public function getAddonsPrice($addons, $id) {
        $addons_option_id = array();
        foreach ($addons as $key => $optionValue) {
            $addons_option_id[] = $optionValue['optionId'];
        }
        $addons_option_count = count($addons_option_id);
        $addonPrice = new MenuAddons ();
        $options = array(
            'columns' => array(
                'id',
                'addon_option',
                'price',
                'addon_id'
            ),
            'where' => array(
                'id' => $addons_option_id,
                'status' => 1
            )
        );
        $addonPrice->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $price = $addonPrice->find($options)->toArray();
        $countPrice = count($price);
//        if($addons_option_count!=$countPrice){ 
//            $sl = Staticoptions::getServiceLocator();
//            $request = $sl->get('request');
//            $requestType = (bool) $request->getQuery('mob', false) ? true : false;
//            
//            $msg = "";
//            $orderEditLink = "go back to edit your order";
//            $msg .= "<div>Sorry we could not process your order as some of the items you selected are no longer offered by the restaurant. Please "; 
//            $msg .= '<span class="txt_editorder">'.$orderEditLink.'</span>';
//            $msg .= " and checkout again</div>";
//            if($requestType){
//               $msg = "Sorry we could not process your order as some of the items you selected are no longer offered by the restaurant. Please go back to edit your order and checkout again"; 
//            }
//            throw new \Exception($msg);
//        }
        $totalPrice = 0;
        foreach ($price as $k => $price) {
            if (!isset($this->itemDetails [$id] ['addons'])) {
                $this->itemDetails [$id] ['addons'] = array();
            }
            if (!isset($this->itemDetails [$id] ['addons'][$price['addon_id']])) {
                $this->itemDetails [$id] ['addons'][$price['addon_id']] = array();
            }
            $this->itemDetails [$id] ['addons'][$price['addon_id']] [] = array(
                'addon_option' => $price ['addon_option'],
                'price' => $price ['price'],
                'addon_id' => $price['addon_id'],
                'addon_option_id' => $price['id'],
                'priority' => isset($addons[$k]['priority']) ? $addons[$k]['priority'] : 0,
            );
        }
        $menuAddonsSettings = new MenuAddonsSetting();
        $e = "";
        foreach ($this->itemDetails [$id] ['addons'] as $addon_id => &$optionDetails) {
            $options = array(
                'columns' => 'enable_pricing_beyond',
                'where' => array('addon_id' => $addon_id, 'menu_id' => $this->itemDetails [$id]['item_id'])
            );

            $epb = $menuAddonsSettings->getEnablePriceBeyound($options);
            $epb = isset($epb[0]['enable_pricing_beyond']) && !empty($epb[0]['enable_pricing_beyond']) ? intval($epb[0]['enable_pricing_beyond']) : 0;
            usort($optionDetails, function($a, $b) {
                return $a['priority'] > $b['priority'];
            });

            $i = 0;
            foreach ($optionDetails as &$a) {
                if ($i < $epb) {
                    $a['price'] = 0;
                    $a['was_free'] = 1;
                } else {
                    $a['was_free'] = 0;
                }
                $totalPrice = $totalPrice + $a['price'];
                $i++;
            }
        }
        $orderedAddons = array();
        foreach ($this->itemDetails [$id] ['addons'] as $add) {
            foreach ($add as $ad) {
                $orderedAddons[] = $ad;
            }
        }
        $this->itemDetails [$id] ['addons'] = $orderedAddons;

        return $totalPrice;
    }

    public function fetchCardDetailsFromStripe($cardId) {
        $userCardModel = new UserCard ();
        $userCardModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $response = $userCardModel->find(array(
                    'columns' => array(
                        'stripe_token_id'
                    ),
                    'where' => array(
                        'id' => $cardId
                    )
                ))->toArray();
        if (!empty($response)) {
            return $response [0] ['stripe_token_id'];
        }
        throw new \Exception('Invalid card id');
    }

    public function sendOrderMail() {
        
    }

    public function generateReservationReceipt() {
        $timestamp = date('mdhis');
        $keys = rand(0, 9);
        $randString = 'M' . $timestamp . $keys;
        return $randString;
    }

    public function calculateTax($restaurantId) {
        $userRestaurantModel = new Restaurant ();
        $userRestaurantModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $options = array(
            'columns' => array(
                'city_id',
                'restaurant_name'
            ),
            'where' => array(
                'id' => $restaurantId
            )
        );
        $city = $userRestaurantModel->find($options)->toArray();
        $this->restaurantName = $city [0] ['restaurant_name'];
        $cityModel = new City ();
        $cityModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $options = array(
            'columns' => array(
                'sales_tax'
            ),
            'where' => array(
                'id' => $city [0] ['city_id']
            )
        );
        $tax = $cityModel->find($options)->toArray();
        $tax = ($this->subtotal * $tax [0] ['sales_tax']) / 100;
        return $this->trimDecimals($tax, 2);
    }

    public function checkDealDiscount($restaurantId) {
        $restaurantFunctions = new RestaurantDetailsFunctions ();
        $deals = $restaurantFunctions->getDealsForRestaurant($restaurantId);
        throw new \Exception("Do Deals work over here");
        if (!empty($deals)) {
            $deals = array_pop($deals);
            isset($deals ['minimum_order_amount']) ? $deals ['minimum_order_amount'] : 0;
            if ($this->subtotal >= $deals ['minimum_order_amount']) {
                if ($deals ['discount_type'] == 'p') {
                    $this->dealDiscount = ($this->subtotal * $deals ['discount']) / 100;
                } else {
                    if ($deals ['discount'] != null) {
                        $this->dealDiscount = $this->trimDecimals($deals ['discount'], 2);
                    }
                }
            }
        }
    }

    public function calculateTipAmount($tipPercent) {
        $calculate_amt = (($this->subtotal * $tipPercent) / 100);
        $final_amt = explode(".", $calculate_amt);
        if (isset($final_amt[1]))
            $finalpAmt = $final_amt[0] . '.' . substr($final_amt[1], 0, 2);
        else
            $finalpAmt = $calculate_amt;
        return $finalpAmt;
        //return $this->trimDecimals(($this->subtotal * $tipPercent) / 100, '2');
    }


    public function makeOrderForMail($itemDetails, $restaurant_id, $status, $subtotal = false) {
        $order_string = '';
        $price_desc = '';

        $restaurantAccount = new RestaurantAccounts();
        $order_string .= ' <tr>
                                <td bgcolor="#fff0e1" style="padding:14px;">
                                   <p style="margin:0;font-family:arial;font-size:18px;font-weight:bold;padding-bottom:9px;padding-top:4px;">Your Order:</p>
                                   <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-family:arial;font-size:18px;">';
        foreach ($itemDetails as $item) {
            //if ($item['price_desc']) {
            //  $price_desc = " <span style='font-weight: normal;'>(" . $item['price_desc'] . ")</span>";
            //}
            $special_instruction = '';
            if (!empty($item ['special_instruction'])) {
                $special_instruction = $item ['special_instruction'];
            }

            $order_string .= '<tr style="border-bottom: 1px dotted #3d3a39;">
                                         <td width="8%" align="left" valign="top" style="padding-top:18px;">' . $item ['quantity'] . '</td>
                                         <td width="67%" align="left" valign="top" style="padding-bottom:16px;padding-top:18px;">' . $item ['item_name'] . '<br/><i style="display:block;color:#7d7674;font-size:16px;padding-top:5px;">' . $special_instruction . '</i></td>
                                         <td width="25%" align="right" valign="top" style="padding-top:18px;">$' . $this->trimDecimals($item ['unit_price'] * $item ['quantity'], 2) . '</td>
                                      </tr>';
            if (!empty($item ['addons'])) {
                foreach ($item ['addons'] as $addon) {
                    if ($addon ['addon_option'] != 'None') {
                        $freeText = "";
                        $sendMailToRestaurant = $restaurantAccount->checkRestaurantForMail($restaurant_id, 'orderconfirm');
                        if ($addon ['was_free'] == 1 && $status === "ordered") {
                            if ($sendMailToRestaurant == true || $sendMailToRestaurant == 1) {
                                $freeText = " (Included in base price)";
                            }
                        }
                        $order_string .= '<tr> 
                        <td width="8%" align="left" valign="top" style="font-size:12px;padding-top:5px;padding-bottom:5px">' . $item ['quantity'] . '</td>
                        <td width="67%" align="left" valign="top" style="font-size:12px;padding-top:5px;padding-bottom:5px;padding-left:5px">&nbsp;&nbsp;+ ' . $addon ['addon_option'] . $freeText . '</td>
                        <td width="25%" align="right" valign="top" style="font-size:12px;padding-top:5px;padding-bottom:5px">$' . $this->trimDecimals($addon ['price'] * $item ['quantity'], 2) . '</td>   
                    </tr>';
                    }
                }
            }
        }
        $order_string .= '</table><table width="100%" border="0" cellpadding="0" cellspacing="0" style="padding-top:19px;padding-bottom:3px;font-family:arial;font-size:22px;font-weight:bold;">
                                      <tr>
                                         <td>TOTAL:</td>
                                         <td align="right">$' . $this->trimDecimals($subtotal, 2) . '</td>
                                      </tr>
                                   </table>
                                </td>
                             </tr>';
        return $order_string;
    }

    public function makeOrderForMailInvite($itemDetails, $restaurant_id, $status, $subtotal = false) {
        $order_string = '';
        $price_desc = '';
        $restaurantAccount = new RestaurantAccounts();
        $order_string .= ' <tr>
                    <td height="30" colspan="3" align="left" style="font-size:13px; color:#666666;" >
                      <strong>Your Order</strong>
                    </td>
                  </tr>';
        foreach ($itemDetails as $item) {
            if ($item['price_desc']) {
                $price_desc = " <span style='font-weight: normal;'>(" . $item['price_desc'] . ")</span>";
            }
            $order_string .= '<tr>
                    <td align="left" valign="top" style="color:#666666;" width="160" nowrap><strong>' . $item ['item_name'] . $price_desc . '</strong></td>
                    <td align="center" valign="top" style="color:#666666;" width="30" >' . $item ['quantity'] . '</td>
                    <td align="right" valign="top" style="color:#666666;" width="119" ><strong>$' . $this->trimDecimals($item ['unit_price'] * $item ['quantity'], 2) . '</strong></td>
                  </tr>';
            if (!empty($item ['special_instruction'])) {
                $order_string .= '<tr>
                    <td height="20" colspan="3" align="left" valign="top" style="color:#666666;" >
                      <i>' . $item ['special_instruction'] . '</i>
                    </td>
                  </tr>';
            }
            if (!empty($item ['addons'])) {
                foreach ($item ['addons'] as $addon) {
                    if ($addon ['addon_option'] != 'None') {
                        $freeText = "";
                        $sendMailToRestaurant = $restaurantAccount->checkRestaurantForMail($restaurant_id, 'orderconfirm');
                        if ($addon ['was_free'] == 1 && $status === "ordered") {
                            if ($sendMailToRestaurant == true || $sendMailToRestaurant == 1) {
                                $freeText = " (Included in base price)";
                            }
                        }
                        $order_string .= '<tr>
                            <td align="left" valign="top" style="color:#666666;" width="160" >&nbsp;&nbsp;+ ' . $addon ['addon_option'] . $freeText . '</td>
                            <td align="center" valign="top" style="color:#666666; font-size:11px;" width="30" >' . $item ['quantity'] . '</td>
                            <td align="right" valign="top" style="color:#666666;font-size:11px;" width="119" >$' . $this->trimDecimals($addon ['price'] * $item ['quantity'], 2) . '</td>
                          </tr>';
                    }
                }
            }
        }
        $order_string .= '<tr>
                    <td height="20" colspan="3" align="left" valign="top"
                    style="color: #666666;padding-top:10px;padding-bottom:10px"><img src="' . TEMPLATE_IMG_PATH . 'border.png" width="309" height="1" /></td>
                </tr>';
        $order_string .= '<tr>
                    <td align="left" valign="top" style="color:#666666;font-size:11px;" width="160" >&nbsp;&nbsp;</td>
                    <td align="center" valign="top" style="color:#666666;font-size:11px;" width="30" >&nbsp;</td>
                    <td align="right" valign="top" height="25" style="color:#666666;font-size:12px;" width="119" ><strong>$' . $this->trimDecimals($subtotal, 2) . '</strong></td>
                  </tr> ';
        return $order_string;
    }

    public function getOrderStatus($orderDate, $orderTime, $restaurant_id) {
        return 'placed';//15 Dec 2016, Asked by parmanad to chage always order status should be placed. So we have return placed from this function before going to calculate any thisgs.
        $currentDateTime = StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));
        $orderTime = StaticOptions::getAbsoluteCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                        ), $orderDate . ' ' . $orderTime, 'Y-m-d H:i');
        $upComingOrder = $currentDateTime->add(new \DateInterval('PT1H'))->add(new \DateInterval('PT29M'))->add(new \DateInterval('PT59S'));
        if ($orderTime <= $upComingOrder) {
            return 'ordered';
        } else {
            return 'placed';
        }
    }

    public function calculateDeliveryCharge($restaurant_id) {
        $restaurantModel = new Restaurant ();
        $restaurantModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $oprions = array(
            'columns' => array(
                'delivery_charge'
            ),
            'where' => array(
                'id' => $restaurant_id
            )
        );
        $charges = $restaurantModel->find($oprions)->current()->getArrayCopy();
        return $this->trimDecimals($charges ['delivery_charge'], '2');
    }

    public function trimDecimals($number, $precision) {
        $formattedNumber = $number;
        $decimals = explode(".", $number + "");
        $decimals = isset($decimals [1]) ? strlen($decimals [1]) : 0;
        if ($decimals > $precision) {
            $num = ($number * 100) / 100;
            $formattedNumber = round($num, $precision);
        } else {
            $formattedNumber = ($number * 100) / 100;
        }
        return $formattedNumber;
    }

    public function calculatePercentAmount($price, $amountPercent) {
        return $this->trimDecimals(($price * $amountPercent) / 100, '2');
    }

    public function getCuisineDetail($itemId = array()) {
        $cuisines1 = array();
        $allcuisines = array();
        $cuisinesName = array();
        if (!empty($itemId)) {
            $menu = new Menu();
            $cuisines = new Model\MasterCuisines();
            $options = array(
                'columns' => array(
                    'cuisines_id'
                ),
                'where' => array(
                    'id' => $itemId
                ),
            );
            $menu->getDbTable()->setArrayObjectPrototype('ArrayObject');
            $cuisinesIds = $menu->find($options)->toArray();
            if (count($cuisinesIds) > 0) {
                $i = 0;
                foreach ($cuisinesIds as $keys => $val) {
                    $cuisineIdArray = explode(',', $val['cuisines_id']);
                    foreach ($cuisineIdArray as $key => $id) {
                        if ($id)
                            $cuisines1[$i] = $id;
                        $i++;
                    }
                }
            }
        }
        if (!empty($cuisines1)) {

            $optionsCuisines = array(
                'columns' => array(
                    'cuisine'
                ),
                'where' => array(
                    'id' => $cuisines1
                ),
            );
            $cuisines->getDbTable()->setArrayObjectPrototype('ArrayObject');
            if ($cuisines->find($optionsCuisines)->toArray()) {
                $allcuisines = $cuisines->find($optionsCuisines)->toArray();

                $i = 0;

                foreach ($allcuisines as $k => $v) {
                    $cuisinesName[$i] = $v['cuisine'];
                    $i++;
                }
            }
        }
        return $cuisinesName;
    }

    public function getResCuisineDetail($itemId = array()) {
        $cuisines1 = array();
        $allcuisines = array();
        if (!empty($itemId)) {
            $restauModel = new \Restaurant\Model\Cuisine();
            $cuisines = new Model\MasterCuisines();
            $options = array(
                'columns' => array(
                    'cuisine_id'
                ),
                'where' => array(
                    'restaurant_id' => $itemId
                ),
            );
            $restauModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
            $cuisinesIds = $restauModel->find($options)->toArray();
            if ($cuisinesIds) {
                $i = 0;

                foreach ($cuisinesIds as $keys => $val) {
                    $cuisines1[$i] = $val['cuisine_id'];
                    $i++;
                }
            }
        }

        if (!empty($cuisines1)) {

            $optionsCuisines = array(
                'columns' => array(
                    'cuisine'
                ),
                'where' => array(
                    'id' => $cuisines1
                ),
            );
            $cuisines->getDbTable()->setArrayObjectPrototype('ArrayObject');
            if ($cuisines->find($optionsCuisines)->toArray()) {
                $allcuisines = $cuisines->find($optionsCuisines)->toArray();

                $i = 0;
                $cuisinesName = array();
                foreach ($allcuisines as $k => $v) {
                    $cuisinesName[$i] = $v['cuisine'];
                    $i++;
                }
            }
        }
        return $cuisinesName;
    }

    public function getResFeatureDetail($itemId = array()) {
        if (!empty($itemId)) {
            $restauModel = new \Restaurant\Model\Feature();
            $featureRestaurent = $restauModel->getRestaurantPlaceFeaturesDetails($itemId, 'Restaurant Features');
        }

        return $featureRestaurent;
    }

    public function getResFeatureDetailOption($itemId = array()) {
        if (!empty($itemId)) {
            $restauModel = new \Restaurant\Model\Feature();
            $featureRestaurent = $restauModel->getRestaurantPlaceFeaturesDetailOtion($itemId, 'Restaurant Features');
        }

        return $featureRestaurent;
    }

    /**
     * Encrypts plaintext using aes algorithm
     * @author dsyzug
     * @param string $plaintext plain text
     * @return mixed <b>string</b> encrypted-text  or <b>false</b> on failure
     */
    public function aesEncrypt($plaintext) {
        try {
            $aes_params = StaticOptions::getAesOptions();
            //256-bit $key which is a SHA256 hash of $salt and $password.
            $key = hash('SHA256', $aes_params['aes_salt'] . $aes_params['aes_pass'], true);
            //$iv and $iv_base64.  Use a block size of 128 bits (AES compliant) and CBC mode. (Note: ECB mode is inadequate as IV is not used.)
            srand();
            if (function_exists('mcrypt_create_iv')) {
                $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
                if (strlen($iv_base64 = rtrim(base64_encode($iv), '=')) != 22) {
                    return false;
                }
                // Encrypt $decrypted and an MD5 of $decrypted using $key.  MD5 is fine to use here because it's just to verify successful decryption.
                $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $plaintext . md5($plaintext), MCRYPT_MODE_CBC, $iv));
                return $iv_base64 . $encrypted;
            } else {
                throw new \Exception('Something Went Wrong On encryption card details');
            }
        } catch (\Exception $e) {
            \MUtility\MunchLogger::writeLog($e, 1, 'Something Went Wrong To Encryption Card Details');
            throw new \Exception($e->getMessage(), 400);
        }
    }

    /**
     * Decrypts ciphertext using aes algorithm
     * @author dsyzug
     * @param string $encryptedtext plain text
     * @return mixed <b>string</b> decrypted-text or <b>false</b> on failure
     */
    public function aesDecrypt($encryptedtext) {
        $aes_params = StaticOptions::getAesOptions();
        //256-bit $key which is a SHA256 hash of $salt and $password.
        $key = hash('SHA256', $aes_params['aes_salt'] . $aes_params['aes_pass'], true);
        // Retrieve $iv which is the first 22 characters plus ==, base64_decoded.
        $iv = base64_decode(substr($encryptedtext, 0, 22) . '==');
        // Remove $iv from $encrypted.
        $encryptedtext = substr($encryptedtext, 22);
        // Decrypt the data.  rtrim won't corrupt the data because the last 32 characters are the md5 hash; thus any \0 character has to be padding.
        $plaintext = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($encryptedtext), MCRYPT_MODE_CBC, $iv), "\0\4");
        // Retrieve $hash which is the last 32 characters of $decrypted.
        $hash = substr($plaintext, -32);
        // Remove the last 32 characters from $decrypted.
        $plaintext = substr($plaintext, 0, -32);
        //Integrity check.
        if (md5($plaintext) != $hash) {
            return false; //data corrupted, or the password/salt was incorrect
        }
        return $plaintext;
    }

    public function restaurantDeal($dealId, $registerRestaurant = false, $currentDateTimeUnixTimeStamp) {
        $dealDetails = array();
        if (isset($dealId) && !empty($dealId) && $registerRestaurant) {
            $dealCouponsModel = new \Restaurant\Model\DealsCoupons();
            $joins = [];
            $joins [] = array('name' => array('ud' => 'user_deals'),
                        'on' => new \Zend\Db\Sql\Expression("(ud.deal_id = restaurant_deals_coupons.id)"),
                        'columns' => array('availed'),
                        'type' => 'left'
                        );
            $dealOptions = array('column' => array(), 'where' => array('restaurant_deals_coupons.id' => $dealId), 'joins'=>$joins);
            
            $dealData = $dealCouponsModel->find($dealOptions)->toArray();
            if(isset($dealData[0]) && !empty($dealData[0])){
                $dealDetails = $dealData[0];
                $slotsArray = explode(",", $dealDetails['slots']);
                $daysArray = explode(",", $dealDetails['days']);
                $dealsEndDateTimeUnixTimeStamp = strtotime($dealDetails['end_date'])+(30*60);
                $dealsStartDateTimeUnixTimeStamp = strtotime($dealDetails['start_on']);
                $dealsExpireDateTimeUnixTimeStamp = strtotime($dealDetails['expired_on'])+(30*60);
                if ($currentDateTimeUnixTimeStamp > $dealsEndDateTimeUnixTimeStamp || $currentDateTimeUnixTimeStamp > $dealsExpireDateTimeUnixTimeStamp || $dealsStartDateTimeUnixTimeStamp > $currentDateTimeUnixTimeStamp) {
                    $dealDetails = array();
                }
            }
        }
        return $dealDetails;
    }

    public function getOrderPassThrough($restaurantId) {
        $orderPass = 0;
        if (isset($restaurantId) && !empty($restaurantId)) {
            $restaurantModel = new \Restaurant\Model\Restaurant();
            $optionOrderPass = array('columns' => array('order_pass_through'), 'where' => array('id' => $restaurantId));
            $orderPassThrough = $restaurantModel->findRestaurant($optionOrderPass)->toArray();
            $orderPass = $orderPassThrough['order_pass_through'];
        }
        return $orderPass;
    }

    public function getRestaurentCuisineDetail($restaurantId = false) {
        if (isset($restaurantId) && !empty($restaurantId)) {
            $restaurantModel = new \Restaurant\Model\Cuisine();
            $option = array('columns' => array('restaurant_id' => $restaurantId));
            $cuisine = $restaurantModel->getRandRestaurantCuisineDetails($option);
            return $cuisine;
        }
    }

    public function getRandRestaurentCuisineDetail($restaurantId = false) {
        if (isset($restaurantId) && !empty($restaurantId)) {
            $restaurantModel = new \Restaurant\Model\Cuisine();
            $option = array('columns' => array('restaurant_id' => $restaurantId));
            $cuisine = $restaurantModel->getRandRestaurantCuisineDetails($option);
            return $cuisine;
        }
    }

    public function sendSmsforOrder($data, $orderstatus, $userId, $restaurant_id, $delivery_time) {
        $userFunctions = new \User\UserFunctions ();
        $totalordercount = $userFunctions->sendSmsonTransactionCount($userId);
        $userSmsData = array();
        $currentTimeOrder = new \DateTime ();
        $arrivedTimeOrder = \DateTime::createFromFormat(StaticOptions::MYSQL_DATE_FORMAT, $delivery_time);
        $currentTimeNewOrder = StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->format(StaticOptions::MYSQL_DATE_FORMAT);
        $currentDateNewOrder = StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
                ))->format("Y-m-d");
        $differenceOfTimeInMin = round(abs(strtotime($arrivedTimeOrder->format("Y-m-d H:i:s")) - strtotime($currentTimeNewOrder)) / 60);
        $differenceOfDate = floor(strtotime($arrivedTimeOrder->format("Y-m-d")) - strtotime($currentDateNewOrder)) / 3600 / 24;
        $sl = StaticOptions::getServiceLocator();
        $config = $sl->get('Config');
        $specChar = $config ['constants']['special_character'];
        $restaurantName = strtr($data['restaurantName'], $specChar);
        $order_pass_through = $this->getOrderPassThrough($restaurant_id);//$data['order_pass_through'];
        $userSmsData['user_mob_no'] = $data['phone'];
        if ($order_pass_through == 1) {  // for pass through restaurant
            if ($data['type'] == "Delivery") {
                if ($differenceOfTimeInMin <= 90) {
                    $userSmsData['message'] = "Hey! We got your Munch Ado food order from " . $restaurantName . "...and the internet. We're a little jelly you'll be the one eating it in 45-60 min, not us. You can add a tip when you sign the restaurant's physical receipt.";
                } else if ($differenceOfTimeInMin > 90 && $differenceOfDate == 0) {
                    $userSmsData['message'] = "Hey! We got your Munch Ado food order from " . $restaurantName . "...and the internet. We're a little jelly you'll be the one eating it at " . $data['onlyTime'] . ", not us. You can add a tip when you sign the restaurant's physical receipt.";
                } else if ($differenceOfDate > 0) {
                    $userSmsData['message'] = "Hey! We got your Munch Ado food order from " . $restaurantName . ". We're a little jelly you'll be the one eating it at " . $data['onlyTime'] . " on " . date('F j, Y',strtotime($data['onlyDate'])) . ", not us. You can add a tip when you sign the restaurant's physical receipt.";
                }
            } else {
                $userSmsData['message'] = "Hey! We got your Munch Ado food order from " . $restaurantName . ". We're a little jelly you'll be the one eating it. You can add a tip when you sign the restaurant's physical receipt.";
            }
        } else { //for SignedUp Restaurant
           if ($data['type'] == "Delivery") { 
                if ($differenceOfTimeInMin <= 90) {
                    $userSmsData['message'] = "Hey! We got your Munch Ado food order from " . $restaurantName . "...and the internet. We're a little jelly you'll be the one eating it in 45-60 min, not us.";
                } else if ($differenceOfTimeInMin > 90 && $differenceOfDate == 0) {
                    $userSmsData['message'] = "Hey! We got your Munch Ado food order from " . $restaurantName . "...and the internet. We're a little jelly you'll be the one eating it at " . $data['onlyTime'] . ", not us.";
                } else if ($differenceOfDate > 0) {
                    $userSmsData['message'] = "Hey! We got your Munch Ado food order from " . $restaurantName . ". We're a little jelly you'll be the one eating it at " . $data['onlyTime'] . " on " . date('F j, Y',strtotime($data['onlyDate'])) . ", not us.";
                }
            }else{
                    $userSmsData['message'] = "Hey! We got your Munch Ado food order from " . $restaurantName . ". We're a little jelly you'll be the one eating it.";
            }    
        }
        if ($totalordercount == 1) {
            $userSmsData['message'] .=" Want to just unplug from food updates? Reply \"Unsubscribe\" and embrace the peace and tranquility of the unknown.";
        } else {
            $userSmsData['message'] .="";
        }
        StaticOptions::sendSmsClickaTell($userSmsData, $userId);
    }
    
    public function discountOnRedeemPoint(){
       $sl = StaticOptions::getServiceLocator();
       $config = $sl->get('Config');       
       $pointEqualDollar = $config ['constants']['pointEqualDollar'];
       $point = $pointEqualDollar[0];
       $dollar = $pointEqualDollar[1];
       $this->discountAmountOnPoint = ($this->point*$dollar)/$point;
    }
    
    public function checkBalencePoint(){
        $userId = StaticOptions::getUserSession()->getUserId();        
        $userPoint = new \User\Model\UserPoint();
        $totalPoints = $userPoint->countUserPoints($userId);        
        $previousRedeemPoint = ($totalPoints[0]['redeemed_points'] > 0) ? intval($totalPoints[0]['redeemed_points']) : intval(0);
        $balancePoint = $totalPoints[0]['points'] - $previousRedeemPoint;        
        if($balancePoint >= $this->point){
            return true;
        }        
        return false;
    }
    
    public function transaction($type,$pointDescription){
        $userTransaction = new \User\Model\UserTransactions();
        $userId = StaticOptions::getUserSession()->getUserId();
        $transactionData = array(
        'user_id'=>$userId,
        'transaction_type'=>$type,
        'transaction_amount'=> $this->discountAmountOnPoint,
        'remark' => $pointDescription,
        'transaction_date'=>StaticOptions::getRelativeCityDateTime(array('restaurant_id' => $this->restaurant_id))->format("Y-m-d H:i"),
        'category'=>3
        );
        $userTransaction->doTransactionOrder($transactionData);
    }
    
    public function updateUserPoint($orderId){
        $userFunctions = new \User\UserFunctions();
        $userId = StaticOptions::getUserSession()->getUserId();
        $usrTotalPoint = $userFunctions->userTotalPoint($userId);
        if($usrTotalPoint >= $this->point){
            $userPoint = new \User\Model\UserPoint();
            $pointDescription = "You paid with points at ".$this->restaurantName."!";
            $insertData = array(
                'user_id' => $userId,
                'point_source' => '47',
                'points_descriptions' => $pointDescription,
                'redeemPoint' => $this->point,
                'promotionId' => 0,
                'points' => '0',
                'created_at' => StaticOptions::getRelativeCityDateTime(array('restaurant_id' => $this->restaurant_id))->format("Y-m-d H:i"),
                'status' => '1',
                'restaurant_id'=>$this->restaurant_id);        
            $userPoint->createPointDetail($insertData);
        }else{
            throw new \Exception("You don't have enough points to complete this action",400);
        }
        
    }
    
    public function dealAvailedByUser(){
        $userDeals = new \User\Model\UserDeals();
        $userDeals->user_id = StaticOptions::getUserSession()->getUserId();
        $userDeals->deal_id = $this->deal_id;
        $userDeals->availed = 1;
        return $userDeals->updateUserDeals();
    }
    
    public static function orderTransection($userId=false,$param=false){
         $orderTransection=new \User\Model\OrderTransaction();
         if($userId){        
            $varGetOrderTransection=$orderTransection->getUserOrderTransection($userId);
            if($param=='del'){
                $orderTransection->deleteRecord($userId);
            }else if(!empty($varGetOrderTransection)){
               $orderTransection->deleteRecord($userId); 
               throw new \Exception('Something went wrong, Try again!'); 
            }else{
                $orderTransection->insertRecord($userId);
            }
            
        }
        
    }
    
    public function addUserPromocode($promocodeDetails){
        if($this->host_name !=PROTOCOL.SITE_URL){
            $userPromocode = new Model\UserPromocodes();
            $data=array('promo_id'=>$promocodeDetails['id'],'order_id'=>$this->order_id,'restaurant_id'=>$this->restaurant_id,'reedemed'=>1,'user_id'=>$this->user_id);
            $userPromocode->insert($data);
        } 
    }

}
