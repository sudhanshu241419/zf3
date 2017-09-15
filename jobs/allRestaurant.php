<?php
use MCommons\StaticOptions;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$totalResCount=0;
$resAll=getSolrRestaurant(WEB_URL.'wapi/search?page=1&rt=s&slrRestaurant=true');
if(!empty($resAll)){
$totalResCount=$resAll->count;    
}
$totalResCount=ceil($totalResCount/200);
$dom = new \DOMDocument('1.0','utf-8');
$data = $dom->createElement("data");
$counter=0;
for ($i = 1; $i <= $totalResCount; $i++) {
    echo WEB_URL.'wapi/search?pageSlrRes='.$i.'&rt=s&slrRestaurant=true'.'++++++++++++++++++++++++++++++'.PHP_EOL;
   $res=getSolrRestaurant(WEB_URL.'wapi/search?pageSlrRes='.$i.'&rt=s&slrRestaurant=true');
   if(!empty($res)){
           foreach($res->data as $key=>$r){
                    $counter++;
                    //create element node
                    $published = $dom->CreateElement("element");
                    //create nodes
                    $restaurant[$counter]['ID'] = $dom->CreateElement("ID");
                    $restaurant[$counter]['Name'] = $dom->CreateElement("Name");
                    $restaurant[$counter]['Code'] = $dom->CreateElement("Code");
                    $restaurant[$counter]['Description'] = $dom->CreateElement("Description");
                    $restaurant[$counter]['RestaurantUrl'] = $dom->CreateElement("RestaurantUrl");
                    $restaurant[$counter]['Cuisine'] = $dom->CreateElement("Cuisine");
                    $restaurant[$counter]['Features'] = $dom->CreateElement("Features");
                    $restaurant[$counter]['Zipcode'] = $dom->CreateElement("Zipcode");
                    $restaurant[$counter]['Latitude'] = $dom->CreateElement("Latitude");
                    $restaurant[$counter]['Longitude'] = $dom->CreateElement("Longitude");
                    $restaurant[$counter]['Brand'] = $dom->CreateElement("Brand");
                    $restaurant[$counter]['Reservation'] = $dom->CreateElement("Reservation");
                    $restaurant[$counter]['PrePaidReservation'] = $dom->CreateElement("PrePaidReservation");
                    $restaurant[$counter]['Takeout'] = $dom->CreateElement("Takeout");
                    $restaurant[$counter]['Delivery'] = $dom->CreateElement("Delivery");
                    $restaurant[$counter]['Address'] = $dom->CreateElement("Address");
                    $restaurant[$counter]['Neighborhood'] = $dom->CreateElement("Neighborhood");
                    $restaurant[$counter]['OrderPassThrough'] = $dom->CreateElement("OrderPassThrough");
                    $restaurant[$counter]['MinDeliveryValue'] = $dom->CreateElement("MinDeliveryValue");
                    $restaurant[$counter]['Price'] = $dom->CreateElement("Price");
                    $restaurant[$counter]['AcceptCC'] = $dom->CreateElement("AcceptCC");
                    $restaurant[$counter]['Closed'] = $dom->CreateElement("Closed");
                    $restaurant[$counter]['Inactive'] = $dom->CreateElement("Inactive");
                    $restaurant[$counter]['Borough'] = $dom->CreateElement("Borough");
                    $restaurant[$counter]['Tags_fct'] = $dom->CreateElement("Tags_fct");
                    $restaurant[$counter]['Category'] = $dom->CreateElement("Category");
                    $restaurant[$counter]['RestaurantImage'] = $dom->CreateElement("RestaurantImage");
                    $restaurant[$counter]['Is_registered'] = $dom->CreateElement("Is_registered");
                    $restaurant[$counter]['RestaurantLogo'] = $dom->CreateElement("RestaurantLogo");
                    
                    
                    
                    //Assign value to nodes
                    $is_registered=$dom->createTextNode($r->is_registered);
                    $rPImage=$res->image_base_path.strtolower($r->res_code).'/'.$r->res_primary_image;
                    $restaurantImage = $dom->createTextNode($rPImage);
                    $restaurantLogo = "https://s3-us-west-2.amazonaws.com/dine-more/email/logo/".$r->res_id.".png";
                    $restaurantLogo = $dom->createTextNode($restaurantLogo);
                    //https://s3-us-west-2.amazonaws.com/dine-more/email/logo/$dynamicRestaurantID$.png
                    
                    $category = $dom->createTextNode('restaurant');
                    $res_id = $dom->createTextNode(strL($r->res_id,32));
                    $name = $dom->createTextNode(strL($r->res_name,100));
                    $code = $dom->createTextNode(strL($r->res_code,25));
                    $description = $dom->createTextNode(strL($r->res_description,1024));
                    $r_url=WEB_HOST_URL."restaurants/".$r->res_name."/".$r->res_id;
                    $res_url = $dom->createTextNode($r_url);
                    //$cuisine = $dom->createTextNode(strL($r->res_cuisine,1024));
                    
                    $cuiName=explode(",",$r->res_cuisine);
                    if(count($cuiName)>0){
                        foreach($cuiName as $key=>$cuisineName){
                             $restaurant[$counter][$key]['cuisine_name'] = $dom->CreateElement("cuisine_name");
                             $cuisines = $dom->createTextNode(strL($cuisineName,1024));
                             $restaurant[$counter][$key]['cuisine_name']->appendChild($cuisines);
                             $restaurant[$counter]['Cuisine']->appendChild($restaurant[$counter][$key]['cuisine_name']);
                        }
                    }
                    
                    $fName=explode(",",$r->feature_name);
                    //pr($fName,1);
                    if(count($fName)>0){
                        foreach($fName as $key=>$featureName){
                             $restaurant[$counter][$key]['feature_name'] = $dom->CreateElement("feature_name");
                             $features = $dom->createTextNode(strL($featureName,1024));
                             $restaurant[$counter][$key]['feature_name']->appendChild($features);
                             $restaurant[$counter]['Features']->appendChild($restaurant[$counter][$key]['feature_name']);
                        }
                    }
                    
                    $zipcode = $dom->createTextNode(strL($r->res_zipcode,9));
                    $latitude = $dom->createTextNode(strL($r->location_lat,512));
                    $longitude = $dom->createTextNode(strL($r->location_long,512));
                    $brand = $dom->createTextNode('MunchAdo');
                    
                    $resv='No';
                    $pResv='No';
                    if ($r->res_reservations && ($r->accept_cc_phone == 1)) {
                    $resv='Yes';    
                    if ($r->is_registered && ($r->r_menu_without_price==0)) {
                    $pResv='Yes';
                     }
                    }
                    $reservation = $dom->createTextNode($resv);
                    $prePayReservation = $dom->createTextNode($pResv);
                    
                    $t=($r->res_takeout==1)?'Yes':'No';
                    $takeout = $dom->createTextNode($t);
                    
                    $d=($r->res_delivery==1)?'Yes':'No';
                    $delivery = $dom->createTextNode($d);
                    $address= $dom->createTextNode($r->res_address);
                    $neighborhood= $dom->createTextNode($r->res_neighborhood);
                    $op=isset($r->order_pass_through)?$r->order_pass_through:'No';
                    $opth=($op==1)?'Yes':'No';
                    $orderPassThrough= $dom->createTextNode($opth);
                    $minDeliveryValue= $dom->createTextNode($r->res_minimum_delivery);
                    $price= $dom->createTextNode($r->res_price);
                    
                    $acc=($r->accept_cc_phone==1)?'Yes':'No';
                    $acceptcc= $dom->createTextNode($acc);
                    
                    $closed= $dom->createTextNode('No');
                    $inactive= $dom->createTextNode('No');
                    $bo=isset($r->borough)?strL($r->borough,20):'';
                    $borough= $dom->createTextNode($bo);
                    if(count($r->tags_fct)>0){
                        foreach($r->tags_fct as $key=>$fects){
                             
                              $restaurant[$counter][$key]['tags_name']=$dom->CreateElement("tags_name");
                              $tags_fct= $dom->createTextNode(strL($fects,512));
                              $restaurant[$counter][$key]['tags_name']->appendChild($tags_fct);
                              $restaurant[$counter]['Tags_fct']->appendChild($restaurant[$counter][$key]['tags_name']);
                        }
                    }
                    
                     $restaurant[$counter]['ID']->appendChild($res_id);
                     $restaurant[$counter]['Name']->appendChild($name);
                     $restaurant[$counter]['Code']->appendChild($code);
                     $restaurant[$counter]['Description']->appendChild($description);
                     $restaurant[$counter]['RestaurantUrl']->appendChild($res_url);
                     //$restaurant[$counter]['Cuisine']->appendChild($cuisine);
                     
                     $restaurant[$counter]['Zipcode']->appendChild($zipcode);
                     $restaurant[$counter]['Latitude']->appendChild($latitude);
                     $restaurant[$counter]['Longitude']->appendChild($longitude);
                     $restaurant[$counter]['Brand']->appendChild($brand);
                     $restaurant[$counter]['Reservation']->appendChild($reservation);
                     $restaurant[$counter]['PrePaidReservation']->appendChild($prePayReservation);
                     $restaurant[$counter]['Takeout']->appendChild($takeout);
                     $restaurant[$counter]['Delivery']->appendChild($delivery);
                     $restaurant[$counter]['Address']->appendChild($address);
                     $restaurant[$counter]['Neighborhood']->appendChild($neighborhood);
                     $restaurant[$counter]['OrderPassThrough']->appendChild($orderPassThrough);
                     $restaurant[$counter]['MinDeliveryValue']->appendChild($minDeliveryValue);
                     $restaurant[$counter]['Price']->appendChild($price);
                     $restaurant[$counter]['AcceptCC']->appendChild($acceptcc);
                     $restaurant[$counter]['Closed']->appendChild($closed);
                     $restaurant[$counter]['Inactive']->appendChild($inactive);
                     $restaurant[$counter]['Borough']->appendChild($borough);
                     $restaurant[$counter]['Category']->appendChild($category);
                     $restaurant[$counter]['RestaurantImage']->appendChild($restaurantImage);
                     $restaurant[$counter]['Is_registered']->appendChild($is_registered);
                     $restaurant[$counter]['RestaurantLogo']->appendChild($restaurantLogo);
                     
                     
                     //publish rows
                     $published->appendChild($restaurant[$counter]['ID']);
                     $published->appendChild($restaurant[$counter]['Name']);
                     $published->appendChild($restaurant[$counter]['Code']);
                     $published->appendChild($restaurant[$counter]['Description']);
                     $published->appendChild($restaurant[$counter]['RestaurantUrl']);
                     $published->appendChild($restaurant[$counter]['Cuisine']);
                     $published->appendChild($restaurant[$counter]['Features']);
                     $published->appendChild($restaurant[$counter]['Zipcode']);
                     $published->appendChild($restaurant[$counter]['Latitude']);
                     $published->appendChild($restaurant[$counter]['Longitude']);
                     $published->appendChild($restaurant[$counter]['Brand']);
                     $published->appendChild($restaurant[$counter]['Reservation']);
                     $published->appendChild($restaurant[$counter]['PrePaidReservation']);
                     $published->appendChild($restaurant[$counter]['Takeout']);
                     $published->appendChild($restaurant[$counter]['Delivery']);
                     $published->appendChild($restaurant[$counter]['Address']);
                     $published->appendChild($restaurant[$counter]['Neighborhood']);
                     $published->appendChild($restaurant[$counter]['OrderPassThrough']);
                     $published->appendChild($restaurant[$counter]['MinDeliveryValue']);
                     $published->appendChild($restaurant[$counter]['Price']);
                     $published->appendChild($restaurant[$counter]['AcceptCC']);
                     $published->appendChild($restaurant[$counter]['Closed']);
                     $published->appendChild($restaurant[$counter]['Inactive']);
                     $published->appendChild($restaurant[$counter]['Borough']);
                     $published->appendChild($restaurant[$counter]['Tags_fct']);
                     $published->appendChild($restaurant[$counter]['Category']);
                     $published->appendChild($restaurant[$counter]['RestaurantImage']);
                     $published->appendChild($restaurant[$counter]['RestaurantLogo']);
                     $published->appendChild($restaurant[$counter]['Is_registered']);
                     $data->appendChild($published);
       }
   }
}

$dom->appendChild($data);

if(!is_dir(dirname(__DIR__).'/public/assets/download')){
   mkdir(dirname(__DIR__).'/public/assets/download', 0777); 
}
if(!is_dir(dirname(__DIR__).'/public/assets/download/xml')){
        mkdir(dirname(__DIR__).'/public/assets/download/xml', 0777); 
}
$dom->save(dirname(__DIR__).'/public/assets/download/xml/allrestaurant.xml');

function getSolrRestaurant($url) {
         $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_a = json_decode($response);
        return $response_a;
}
function strL($string,$len) {
    $string = strip_tags($string);
    if (strlen($string) > $len) {
    $stringCut = substr($string, 0, $len);
        $string = substr($stringCut, 0, strrpos($stringCut, ' ')) . '..';
    }
    return $string;
}