<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Utility
 *
 * @author bravvura
 */
namespace Search\Common;

use MCommons\StaticFunctions;
use Search\Solr\SearchHelpers;
use Search\Common\DateTimeUtility;

class Utility 
{
    private static $nextDay2Char = array(
        "su" => "mo",
        "mo" => "tu",
        "tu" => "we",
        "we" => "th",
        "th" => "fr",
        "fr" => "sa",
        "sa" => "su"
    );
    
    public function getTest(\Zend\ServiceManager\ServiceManager $sm)
    {
        if ( $sm->has('ApplicationConfig') )
        {
            pr($sm->get('Config'));
        }
        
    }


    
    /**
     * Check if required search params are present. If not set their default values.
     * @param array $params search parameters
     * @param string $stype Search Type = {search,auto,facet}
     * @return array
     */
    public static function cleanMobileSearchParams($params)
    {
        $ret = array();
        $ret['DeBuG']       = isset($params['DeBuG']) ? $params['DeBuG'] : 0;
        $ret ['city_id']    = isset($params ['city_id']) ? (int) $params ['city_id'] : 18848;
        $ret ['city_name']  = isset($params ['city_name']) ? $params ['city_name'] : 'New York';
        $ret ['is_registered'] = isset($params ['is_registered']) ? $params ['is_registered'] : '';      
        $ret ['accepts_order'] = isset($params ['accepts_order']) ? (int) $params ['accepts_order'] : 0;
        $ret ['sst']    =   isset($params ['sst']) ? $params ['sst'] : 'all'; // vt = {restaurant, food, suggest} (not in use)
        $ret['ovt']     =   isset($params['ovt']) ? $params['ovt'] : 'restaurant'; //open view type = {food, restaurant}
        $ret['reqtype'] =   isset($params['reqtype']) ? $params['reqtype'] : '';
        $ret['rt']      =   isset($params['rt']) ? $params['rt'] : '';
        $ret ['rrt']    =   isset($params ['rrt']) ? $params ['rrt'] : 'breakfast';
        $ret ['sq']     =   isset($params ['sq']) ? $params ['sq'] : '';  // sq=search_query, sdt=search_datatype 
        $ret ['sdt']    =   isset($params ['sdt']) ? $params ['sdt'] : ''; // data_type: dt contains {cui,fav,pref,top,feat,amb} delimited by ||
        $ret ['fq']     =   isset($params ['fq']) ? $params ['fq'] : '';
        $ret ['fdt']    =   isset($params ['fdt']) ? $params ['fdt'] : ''; // data_type: dt contains {cui,fav,pref,top,feat,amb} delimited by ||
        $ret ['at']     =   isset($params ['at']) ? $params ['at'] : 'city';  // at = { address','street','nbd','zip','city'}, av=address value
        $ret ['av']     =   isset($params ['av']) ? $params ['av'] : '';
        
        $ret ['lat']    =   isset($params ['lat']) ? $params ['lat'] : 0;
        $ret ['lng']    =   isset($params ['lng']) ? $params ['lng'] : 0;
        $ret ['price']  =   isset($params ['price']) ? (int) $params ['price'] : 0; // price = {0,1,2,3,4}
        $ret ['deals']  =   0;//isset($params ['deals']) ? (int) $params ['deals'] : 0; //deals part of solr fq parameter
        
        //sorting
        $ret ['sort_by']    = isset($params ['sort_by']) ? $params ['sort_by'] : 'relevancy';
        $ret ['sort_type']  = isset($params ['sort_type']) ? $params ['sort_type'] : 'asc';
        
        $ret['view_type']   = isset($params['view_type']) ? $params['view_type'] : 'restaurant';
        $ret ['cuisines']   = isset($params ['cuisines']) ? $params ['cuisines'] : '';
        $ret ['features']   = isset($params ['features']) ? $params ['features'] : '';
        $ret ['orn']        = isset($params ['orn']) ? (int) $params ['orn'] : 0;
        
        // start and rows
        $ret ['start']  = isset($params ['start']) ? $params ['start'] : 0;
        $ret ['rows']   = isset($params ['rows']) ? $params ['rows'] : 12;
        $ret ['page']   = isset($params ['page']) ? $params ['page'] : 0;
        
        $ret ['aor']    = isset($params ['aor']) && ($params['aor'] == '1') ? 1 : 0;
        $ret ['acp']    = isset($params ['acp']) && ($params['acp'] == '1') ? 1 : 0;

        //params required for autosuggestion only
        $ret ['term']   = isset($params ['term']) ? rawurlencode(strtolower(trim($params ['term']))) : '';
        $ret ['limit']  = isset($params ['limit']) ? $params ['limit'] : 5;
        
        $ret ['sec']    = isset($params ['sec']) ? $params ['sec'] : '';  //this is to identify autosuggest from checkin
        $ret ['tab']    = isset($params ['tab']) ? $params ['tab'] : 'all';  
        
        $ret ['latlong']    = isset($params ['latlong']) ? $params ['latlong'] : "0,0";
        $ret ['cl_latlong'] = isset($params ['cl_latlong']) ? $params ['cl_latlong'] : $ret ['latlong'];//current location latlong in mobile
        $ret ['zm_level']   = isset($params ['zm_level']) ? $params ['zm_level'] : 10;//city level
        
        $ret ['q']      = isset($params ['q']) ? $params ['q'] : '';
        $ret ['dt']     = isset($params ['dt']) ? $params ['dt'] : '';  
        if ($ret['q'] != '' && $ret['dt'] != 'curated') {
            $ret['q'] = \Search\Solr\Synonyms::applySynonyFilter($ret['q']);
            $ret['dt'] = self::getQueryType($params['q'], $ret['view_type']);
        }
        
        $dayDateTime        =   DateTimeUtility::getCityDayDateAndTime24F($ret['city_id'])        ;
        $ret['curr_time']   =   $dayDateTime ['time'];
        $ret['curr_date']   =   $dayDateTime ['date'];
        //pr($params);
        if(!isset($params['sdate']) || $params['sdate'] == '' || empty($params['sdate']) ) 
        {
            $ret ['sdate'] =  $dayDateTime ['date'];
        }
        else
        { 
            $ret ['sdate'] =  $params['sdate']; 
        }

        if( !isset($params['stime']) || $params['stime'] == '' || empty($params['stime']) )
        {
            $ret ['stime'] = self::getSearchTime($ret ['sst'], $ret['curr_time']);
        }
        else 
        {
            $ret ['stime'] = intval(str_replace(':', '',$params['stime']));
        }
                
        /*
        if(!isset($params['sdate']) || !isset($params['stime']) || $params['sdate'] == '' || $params['stime'] == ''){
            $ret ['stime'] = self::getSearchTime($ret ['sst'], $ret['curr_time']);
            $ret ['sdate'] =  $dayDateTime ['date'];
        } else {
            $ret ['stime'] = intval(str_replace(':', '',$params['stime']));
            $ret ['sdate'] =  $params['sdate'];
        }*/
        $ret ['day'] =  substr(strtolower(date('D', strtotime($ret['sdate']))), 0, 2);
        
        // reservation_request_type = rrt = {breakfast,lunch,dinner}
        
        // latitude and longitude of the address
        if($ret ['at'] == 'zip'){
            preg_match("/\d+/", $ret['av'], $zipcodes);
            $ret['av'] = isset($zipcodes[0]) ? $zipcodes[0] : '';
        } elseif($ret ['at'] == 'nbd'){
            preg_match("/[^,]+/", $ret['av'], $nbds);
            $ret['av'] = isset($nbds[0]) ? $nbds[0] : '';
        }
        //if 'av' is empty fallback to city
        if($ret['av'] == ''){
            $ret['at'] = 'city';
        }
        return $ret;
    }
    
    /**
     * To check if user's query match a cuisine of type of place
     * @param String $q
     * @return string {ft,cuisine,top}
     */
    public static function getQueryType($q, $view_type = 'restaurant') 
    {
        $query      =   rawurlencode(strtolower(trim(str_replace('"','\"',$q))));
        $solr_host  =   StaticFunctions::getSolrUrl();
        
        //type of place check
        $url        = $solr_host.'hbr/select?rows=0&fq=feature_fct:"'. $query.'"';
        $response   = SearchHelpers::getCurlUrlData($url);
        
        if ($response['status_code'] == 200) {
            $json = json_decode($response['data'], true);
            if ($json['response']['numFound'] > 0) {
                return 'top';
            }
        }
        //cuisnie_check
        if ($view_type == 'restaurant') 
        {
            $url =  $solr_host.'hbr/select?rows=0&fq=cuisine_fct:"'.$query.'"';
        } 
        elseif($view_type == 'food') 
        {
            $url =  $solr_host.'hbm/select?rows=0&fq=menu_cuisine_fct:"'.$query.'"';
        }
        
        $response   =   SearchHelpers::getCurlUrlData($url);
        if ($response['status_code'] == 200) {
            $json   =   json_decode($response['data'], true);
            if ($json['response']['numFound'] > 0) {
                return 'cuisine';
            }
        }
        return 'ft';
    }
    
    /**
     * Mobile API Only: Used for adding extra fields in search response
     * @param array $response
     */
    public function updateRestaurantDataMobile(&$response, $request) 
    {
        $deliver_flag   =   ($request['at'] == 'street') ? true : false;
        $latlong        =   explode(',', $request['latlong']);
        $user_lat       =   $latlong[0]; 
        $user_lng       =   $latlong[1];
        // Restaurant open and close time
              
        $calender = StaticFunctions::getServiceLocator()->get(\Restaurant\Model\RestaurantCalendar::class);

        $totalData  =   count($response ['data']);
        
        for ($i = 0; $i < $totalData; $i ++) {
            $response ['data'] [$i] ['tags_fct']    =   isset($response ['data'] [$i] ['tags_fct']) ? $response ['data'] [$i] ['tags_fct'] : [];
            $response ['data'] [$i] ['res_name']    =   html_entity_decode($response ['data'] [$i] ['res_name']);
            $response ['data'] [$i] ['can_deliver'] =   1;
            
            if ($deliver_flag) {//update if this needs to be updated
                $response ['data'] [$i] ['can_deliver'] =   (int) CityDeliveryCheck::canDeliver($response ['data'] [$i]['res_id'], $user_lat, $user_lng);
            }
            
            if (isset($response ['data'] [$i] ['distance'])) 
            {
                $response ['data'] [$i] ['distance']    =   round($response ['data'] [$i] ['distance'], 2);
            }
            $res_code   =   $response ['data'][$i]['res_code'];
            if (isset($response ['highlight'][$res_code]['res_cuisine'])) 
            {
                $response ['data'] [$i] ['res_cuisine'] =   $response ['highlight'][$res_code]['res_cuisine'][0];
            }
            //fill res_description
            $res_description = '';
            if (isset($response ['highlight'][$res_code]['res_menu'])) 
            {
                $res_description .= $response ['highlight'][$res_code]['res_menu'][0];
            }
            if (isset($response ['highlight'][$res_code]['feature_name'])) 
            {
                $res_description .= '... ' . $response ['highlight'][$res_code]['feature_name'][0];
            }
            if (isset($response ['highlight'][$res_code]['res_description'])) 
            {
                $res_description .= '... ' . $response ['highlight'][$res_code]['res_description'][0];
            }
            //echo $res_description;die;
            if ($res_description != '') 
            {
                if (isset($response ['highlight'][$res_code]['res_description'])) 
                {
                    $res_description .= '... ' . $response ['highlight'][$res_code]['res_description'][0];
                }
                $res_description .= '... ' . $response ['data'] [$i] ['res_description'];
                $response ['data'] [$i] ['res_description'] =   substr($res_description, 0, 250);
            }
            $response ['data'] [$i] ['res_description']     =   html_entity_decode($response ['data'] [$i] ['res_description']);

            //check if the restaurant is currently open. if not add next open time
            $open_now = $calender->isRestaurantOpen($response ['data'] [$i] ['res_id']);
            $response ['data'] [$i] ['is_currently_open'] = $open_now;
            if (!$open_now && isset($response ['data'] [$i]['oh_ft'])) {
                $response ['data'] [$i] ['opens_at'] = self::getNextOpenTime($response ['data'] [$i]['oh_ft'], $request);
            } else {
                $response ['data'] [$i] ['opens_at'] = '';
            }

            // deal price
            if (!empty($response ['data'] [$i] ['deal_price'])) {
                if ($response ['data'] [$i] ['deal_discount_type'] == 'f') {
                    $response ['data'] [$i] ['deal_price_after_discount'] = $response ['data'] [$i] ['deal_price'] - $response ['data'] [$i] ['deal_discount'];
                } else {
                    $response ['data'] [$i] ['deal_price_after_discount'] = $response ['data'] [$i] ['deal_price'] - (($response ['data'] [$i] ['deal_price'] * $response ['data'] [$i] ['deal_discount']) / 100);
                }
            } else {
                $response ['data'] [$i] ['deal_price_after_discount'] = "";
            }
            
            $response ['data'] [$i] ['bookmarks'] = $this->getRestBookmarksMob($response ['data'] [$i] ['res_id']);

            /*
             * aor=1 if Accept Online Orders else 0
             * ordering_enabled = 1 iff ($doc['accept_cc_phone']) && ($doc['res_delivery'] || $doc['res_takeout'] || $doc['res_reservations'])
             */
            $response ['data'] [$i] ['aor'] = $response ['data'] [$i]['ordering_enabled'];

            //acp =1 if registered restaurants or with some tag
            $acp = ($response ['data'] [$i]['is_registered'] == 1) || (count($response ['data'] [$i]['tags_fct']) > 0);
            $response ['data'] [$i] ['acp'] = ($acp) ? 1 : 0;
            $response ['data'] [$i] ['user_craved_it'] = rand(0,1) == 0 ? false : true;
        }
        
        if(isset($response ['highlight'])){
            unset($response ['highlight']);
        }
    }
    
    /**
     * Mobile API Only: Used for adding extra fields in search response
     * @param array $response
     */    
    public function updateFoodDataMobile(&$response, $request) 
    {
        $deliver_flag   =   ($request['at'] == 'street') ? true : false;
        $latlong        =   explode(',', $request['latlong']);
        $user_lat       =   $latlong[0]; 
        $user_lng       =   $latlong[1];
        $totalData      =   count($response ['data']);
        for ($i = 0; $i < $totalData; $i ++) {
            $response ['data'] [$i] ['tags_fct']    =   isset($response ['data'] [$i] ['tags_fct']) ? $response ['data'] [$i] ['tags_fct'] : [];
            $response ['data'] [$i] ['distance']    =   round($response ['data'] [$i] ['distance'], 2);
            $response ['data'] [$i] ['menu_name']   =   html_entity_decode($response ['data'] [$i] ['menu_name']);
            $menu_id                                =   $response ['data'][$i]['menu_id'];
            $response ['data'] [$i] ['can_deliver'] =   1;
            
            if ($deliver_flag) {//update if this needs to be updated
                $response ['data'] [$i] ['can_deliver'] = (int) CityDeliveryCheck::canDeliver($response ['data'] [$i]['res_id'], $user_lat, $user_lng);
            }
            
            if (isset($response ['highlight'][$menu_id]['res_cuisine'])) 
            {
                $response ['data'] [$i] ['res_cuisine'] = $response ['highlight'][$menu_id]['res_cuisine'][0];
            } 
            else if (isset($response ['highlight'][$menu_id]['menu_cuisine'])) 
            {
                $response ['data'] [$i] ['res_cuisine'] = $response ['highlight'][$menu_id]['menu_cuisine'][0];
            } 
            else if (isset($response ['highlight'][$menu_id]['feature_name'])) 
            {
                $response ['data'] [$i] ['res_cuisine'] = $response ['highlight'][$menu_id]['feature_name'][0];
            }
            
            if (isset($response ['highlight'][$menu_id]['menu_item_desc'])) 
            {
                $response ['data'] [$i] ['menu_item_desc'] = $response ['highlight'][$menu_id]['menu_item_desc'][0];
            }
            $response ['data'] [$i] ['menu_item_desc'] = html_entity_decode($response ['data'] [$i] ['menu_item_desc']);

            $calender = StaticFunctions::getServiceLocator()->get(\Restaurant\Model\RestaurantCalendar::class);
            
            $isRestaurantOpen   =   $calender->isRestaurantOpen($response ['data'] [$i] ['res_id']);
            pr($isRestaurantOpen);
            $response ['data'] [$i] ['is_currently_open']   = $isRestaurantOpen;
            $response ['data'] [$i] ['bookmarks']           = $this->getFoodBookmarksMob($menu_id);
            
            //$response ['data'] [$i] ['res_primary_image']   = $this->getResPrimaryImgName($response ['data'] [$i] ['res_id']);
            
            $restaurantModel = StaticFunctions::getServiceLocator()->get(\Restaurant\Model\Restaurant::class);
            $response ['data'] [$i] ['res_primary_image']   = $restaurantModel->getResPrimaryImgName($response ['data'] [$i] ['res_id']);
            
            /*
             * aor=1 if Accept Online Orders else 0
             * ordering_enabled = 1 iff ($doc['accept_cc_phone']) && ($doc['res_delivery'] || $doc['res_takeout'] || $doc['res_reservations'])
             */
            $response ['data'] [$i] ['aor'] =   $response ['data'] [$i]['ordering_enabled'];

            //acp =1 if registered restaurants or with some tag
            $acp = ($response ['data'] [$i]['is_registered'] == 1) || (count($response ['data'] [$i]['tags_fct']) > 0);
            $response ['data'] [$i] ['acp'] =   ($acp) ? 1 : 0;
        }
        
        if(isset($response ['highlight'])){
            unset($response ['highlight']);
        }
    }
    
    /**
     * 
     * @param string $oh_ft operating hours as in ft sheet
     * @param array $input must have keys day and curr_time
     * @return string
     */
    public static function getNextOpenTime($oh_ft, $input) 
    {
        if ($oh_ft == "") 
        {
            return "";
        }
        //$input['time'] = 2230; //sample data
        //flag to check if next open time has been found
        $found = false;

        try 
        {
            $ohft_arr   =   explode('$', $oh_ft);
            $day        =   $input['day'];
            $count      =   -1; // used in adding days when restaurant next opens
            while (!$found && $count < 7) 
            {
                $count++; // how many times
                $match  =   preg_grep('/' . $day . '/', $ohft_arr);

                //length of $match = 1
                foreach ($match as $value) 
                {
                    $tmp    =   explode('|', $value); //$value = fr|11:00 AM - 01:00 PM, 02:00 PM - 11:00  PM
                    $times  =   explode(',', $tmp[1]);
                    $expTime=   0;
                    foreach ($times as $time) {//$time = 11:00 AM - 01:00 PM
                        preg_match_all("/\d+:\d+\s*[APM]+/", $time, $pat_array);

                        if (isset($pat_array[0][0])) 
                        {
                            $timestring =   $pat_array[0][0];
                            if (preg_match('/AM/', $pat_array[0][0])) 
                            {
                                if ($count == 0) 
                                {
                                    $expTime    =   (int) str_replace(':', '', $timestring);
                                    if ($input['curr_time'] <= $expTime) 
                                    {
                                        $found          =   true;
                                        $nextOpenTime   =   $timestring;
                                        break;
                                    }
                                } 
                                else 
                                {
                                    $found          =   true;
                                    $nextOpenTime   =   $timestring;
                                    break;
                                }
                            } 
                            elseif (preg_match('/PM/', $pat_array[0][0])) 
                            {
                                if ($count == 0) 
                                {
                                    $expTime    =   (int) str_replace(':', '', $timestring) + 1200;
                                    if ($input['curr_time'] <= $expTime) 
                                    {
                                        $found          =   true;
                                        $nextOpenTime   =   $timestring;
                                        break;
                                    }
                                } 
                                else 
                                {
                                    $found          =   true;
                                    $nextOpenTime   =   $timestring;
                                    break;
                                }
                            }
                        }
                        //print_r($timestring);die;
                    }//end inner foreach
                }//end out foreach
                $day = self::$nextDay2Char[$day];
            }//end while loop
        } catch (\Exception $e) {
          echo "Exception occured with code:".$e->getCode()." and message:".$e->getMessage();
        }

        $opens_at = '';
        if ($found) 
        {
            if($count == 1)
            {
                $opens_at .= 'Tomorrow';
            } 
            elseif ($count > 1) 
            {
                $opens_at .= date('M d', strtotime($input['curr_date'] . ' + ' . $count . ' day'));
            }
            $opens_at .= " at " . $nextOpenTime;
        }
        return $opens_at;
    }
    
    /**
     * 
     * @param string $tab all,delivery,takeout,dinein,reservation
     * @param int $current_time in range 0-2359
     * @return int time in range 0-2359
     */
    public static function getSearchTime($tab, $current_time)
    {
        if( in_array($tab, array('delivery', 'deliver', 'takeout')) )
        {
            $selected_tab   =   'order';
        } 
        elseif( in_array($tab, array('dinein', 'reservation')) )
        {
            $selected_tab   =   'reservation';
        } 
        else 
        {
            $selected_tab   =   'all';
        }
        
        $hour   =   intval($current_time / 100);
        $minuts =   $current_time % 100;
        switch ($selected_tab) 
        {
            case 'order':
                if($minuts <= 30)
                {
                    $ansHour        =   $hour + 1;
                    $ansMinutes     =   0;
                } 
                else 
                {
                    $ansHour        =   $hour + 1;
                    $ansMinutes     =   30;
                }
                break;
            case 'reservation':
                if($minuts <= 30)
                {
                    $ansHour        =   $hour;
                    $ansMinutes     =   30;
                } 
                else 
                {
                    $ansHour        =   $hour + 1;
                    $ansMinutes     =   0;
                }
                break;
            default:
                $ansHour            =   $hour;
                $ansMinutes         =   $minuts;
                break;
        }
        $search_time    = (100 * $ansHour) + $ansMinutes;
        
        if($search_time == 2400)
        {
            $search_time    =   2359;
        } 
        else if($search_time > 2400)
        {
            $search_time    =   $current_time;
        }
        return $search_time;
    }
    
    /**
     * For Mobile API
     * @param int $rest_id restaurant_id
     * @return array
     */
    public static function getRestBookmarksMob($rest_id) 
    {
        //data to return
        $result =  array(
            'been_count' => 0,
            'love_count' => 0,
            'review_count' => 0,
            'user_been_there' => false,
            'user_loved_it' => false,
            'user_reviewed_it' => false,
            
        );
        
        //$userReviewModel    =   new UserReview();
        $userReviewModel    =   StaticFunctions::getServiceLocator()->get(\User\Model\UserReview::class);
        $resUserReview      =   $userReviewModel->getRestaurantReviewCount($rest_id);
        
        $resreviewModel     =   StaticFunctions::getServiceLocator()->get(\Restaurant\Model\RestaurantReview::class);
        $resReview          =   $resreviewModel->getRestaurantReviewCount($rest_id);
        
        $userTip            =   StaticFunctions::getServiceLocator()->get(\User\Model\UserTip::class);
        $resTip             =   $userTip->restaurantTotalTips($rest_id);
        $totalReview        =   $resReview['total_count'] + $resUserReview['total_count'] + $resTip['total_count'];
        
        $result ['review_count'] =  (int) $totalReview;
        
        $restaurantBookmarkModel =  StaticFunctions::getServiceLocator()->get(\Restaurant\Model\RestaurantBookmark::class);
        $bookmarks               =  $restaurantBookmarkModel->getRestaurantBookmarkCount($rest_id);
       
        if (!empty($bookmarks)) 
        {
            foreach ($bookmarks as $bitem) 
            {
                switch ($bitem ['type']) 
                {
                    case 'bt' :
                        $result ['been_count'] = (int) $bitem ['total_count'];
                        break;
                    case 'lo' :
                        $result ['love_count'] = (int) $bitem ['total_count'];
                        break;
                }
            }
        } 
        
        $session        =   StaticFunctions::getUserSession();
        $isLoggedIn     =   $session->isLoggedIn();
        
        if ($isLoggedIn) 
        {
            $userBookmarks                  = $restaurantBookmarkModel->getRestaurantBookmarksByUserId($rest_id, $session->getUserId());
            $result ['user_been_there']     = isset($userBookmarks ['bt']) && (int) $userBookmarks ['bt'] ? true : false;
            $result ['user_loved_it']       = isset($userBookmarks ['lo']) && (int) $userBookmarks ['lo'] ? true : false;
            $result ['user_reviewed_it']    = isset($userBookmarks ['re']) && (int) $userBookmarks ['re'] ? true : false;
        }
        
        return $result;
    }
    
    
    /**
     * For Mobile API
     * @param int $menu_id menu id
     * @return array
     */
    public static function getFoodBookmarksMob($menu_id) 
    {
        $result = array(
            'craving_count' => 0,
            'love_count' => 0,
            'tried_count' => 0,
            'user_craving_it' => false,
            'user_loved_it' => false,
            'user_tried_it' => false,
        );
        // menu bookmark
        $menuBookmarkModel  =   StaticFunctions::getServiceLocator()->get(\Restaurant\Model\MenuBookmark::class);
        $queryArr           =   array('columns' => array('menu_id' => $menu_id));
        $bookmark           =   $menuBookmarkModel->menuBookmarksCounts($queryArr);

        if (!empty($bookmark)) 
        {
            foreach ($bookmark as $bitem) 
            {
                switch ($bitem ['type']) 
                {
                    case 'wi' :
                        $result['craving_count'] = (int) $bitem ['total_count'];
                        break;
                    case 'lo' :
                        $result['love_count'] = (int) $bitem ['total_count'];
                        break;
                    case 'ti' :
                        $result['tried_count'] = (int) $bitem ['total_count'];
                        break;
                }
            }
        }

        $session    =   StaticFunctions::getUserSession();
        $isLoggedIn =   $session->isLoggedIn();
        
        if ($isLoggedIn) {
            $bookmark_user  =   $menuBookmarkModel->getMenuBookmarksByUserId($menu_id, $session->getUserId());
            $result ['user_craving_it'] = isset($bookmark_user ['wi']) && (int) $bookmark_user ['wi'] ? true : false;
            $result ['user_loved_it'] = isset($bookmark_user ['lo']) && (int) $bookmark_user ['lo'] ? true : false;
            $result ['user_tried_it'] = isset($bookmark_user ['ti']) && (int) $bookmark_user ['ti'] ? true : false;
        }

        return $result;
    }
}
