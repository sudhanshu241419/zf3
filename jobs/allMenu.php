<?php

use MCommons\StaticOptions;
use Restaurant\Model\Menu;
use Restaurant\RestaurantDetailsFunctions;
use Cuisine\Model\Cuisine;
use Restaurant\Model\Restaurant;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');

$imageBaseUrl = $config['constants']['protocol'] . '://' . $config['constants']['imagehost'] . 'munch_images/';
$menuModel = new Menu ();
$restaurantModel = new Restaurant();
$totelCout=$restaurantModel->getRestaurantCounts();
$offset=0;
$limit=500;
$compare=500;
$dom = new \DOMDocument('1.0','utf-8');
$menu = $dom->createElement("Menu");
$forLoofCout=floor($totelCout/500);

//write menu data in menu.xml
for ($i = 0; $i <= $forLoofCout+1; $i++) {
    
        $restaurant = $restaurantModel->getAllRestaurant($offset, $limit);
        if (count($restaurant) > 0 && !empty($restaurant)) {

            foreach ($restaurant as $resKey => $resVal) {

                RestaurantDetailsFunctions::$_bookmark_types = $menuModel->bookmark_types;
                //RestaurantDetailsFunctions::$_isMobile = $this->isMobile();
                $response = $menuModel->restaurantMenuesNew(array(
                        'columns' => array(
                            'restaurant_id' => $resVal['id']
                        )
                    ))->toArray();
                if (!empty($response)) {
                    $response = RestaurantDetailsFunctions::createWebNestedMenu($response, $resVal['id']);
                    $response = RestaurantDetailsFunctions::knowLastLeaf($response);
                    $response = RestaurantDetailsFunctions::formatResponse($response);
                }
                if (!empty($response) && count($response) > 0) {
                    menuSpecificDeal($response, $resVal['id'], $imageBaseUrl, $dom, $menu);
                }
                
            }
        }
        echo $offset.'+++++++';
        $offset = $offset + 500;
}
$dom->appendChild($menu);

$dom->save("/tmp/menu.xml");

function menuSpecificDeal($response, $restaurant_id, $imageBaseUrl, $dom, $menu) { //pr($response,1);
    if (!empty($response) && count($response) > 0) {
        $cou = 0;
        foreach ($response as $main => $m) {
            if (!empty($m['sub_categories']) && count($m['sub_categories']) > 0) {
                foreach ($m['sub_categories'] as $sub => $subM) {
                    if (!empty($subM['category_items']) && count($subM['category_items']) > 0) {
                        foreach ($subM['category_items'] as $itemKey => $item) {
                            $cou++;
                            $cuis = getMenuCuisines($item['cuisines_id']);
                            $resUrl = WEB_HOST_URL . 'restaurants/' . $item['restaurant_name'] . '/' . $restaurant_id . '/menu';
                            $itemImage = ($item['item_image_url'] != '' && $item['item_image_url'] != NULL) ? $imageBaseUrl . strtolower($item['rest_code']) . '/' . $item['item_image_url'] : '';

                            $published = $dom->CreateElement("item");

                            $res[$cou]['ID'] = $dom->CreateElement("ID");
                            $res[$cou]['Name'] = $dom->CreateElement("Name");
                            $res[$cou]['Description'] = $dom->CreateElement("Description");
                            $res[$cou]['Brand'] = $dom->CreateElement("Brand");
                            $res[$cou]['RestaurantName'] = $dom->CreateElement("RestaurantName");
                            $res[$cou]['Category'] = $dom->CreateElement("Category");
                            $res[$cou]['Subcategory1'] = $dom->CreateElement("Subcategory1");
                            $res[$cou]['RestaurantUrl'] = $dom->CreateElement("RestaurantUrl");
                            $res[$cou]['Price'] = $dom->CreateElement("Price");
                            $res[$cou]['GraphicsUrl'] = $dom->CreateElement("GraphicsUrl");
                            $res[$cou]['Cuisine'] = $dom->CreateElement("Cuisine");
                            $res[$cou]['ProductUrl'] = $dom->CreateElement("ProductUrl");

                            $item_id = $dom->createTextNode(strL($item['item_id'],32));
                            $item_name = $dom->createTextNode(strL($item['item_name'],100));
                            $item_desc = $dom->createTextNode(strL($item['item_desc'],1024));
                            $brand = $dom->createTextNode('MunchAdo');
                            $restaurant_name = $dom->createTextNode(strL($item['restaurant_name'],512));
                            $category_name = $dom->createTextNode(strL($m['category_name'],255));
                            $sub_category_name = $dom->createTextNode(strL($subM['category_name'],512));
                            $restaurantUrl = $dom->createTextNode($resUrl);
                            $prc = (isset($item['prices'][0]['value']) && $item['prices'][0]['value'] > 0) ? $item['prices'][0]['value'] : 0;
                            $price = $dom->createTextNode($prc);
                            $image = $dom->createTextNode($itemImage);
                            $cuisine = $dom->createTextNode($cuis);
                            $productUrl = $dom->createTextNode('');


                            $res[$cou]['ID']->appendChild($item_id);
                            $res[$cou]['Name']->appendChild($item_name);
                            $res[$cou]['Description']->appendChild($item_desc);
                            $res[$cou]['Brand']->appendChild($brand);
                            $res[$cou]['RestaurantName']->appendChild($restaurant_name);
                            $res[$cou]['Category']->appendChild($category_name);
                            $res[$cou]['Subcategory1']->appendChild($sub_category_name);
                            $res[$cou]['RestaurantUrl']->appendChild($restaurantUrl);
                            $res[$cou]['Price']->appendChild($price);
                            $res[$cou]['GraphicsUrl']->appendChild($image);
                            $res[$cou]['Cuisine']->appendChild($cuisine);
                            $res[$cou]['ProductUrl']->appendChild($productUrl);

                            $published->appendChild($res[$cou]['ID']);
                            $published->appendChild($res[$cou]['Name']);
                            $published->appendChild($res[$cou]['Description']);
                            $published->appendChild($res[$cou]['Brand']);
                            $published->appendChild($res[$cou]['RestaurantName']);
                            $published->appendChild($res[$cou]['Category']);
                            $published->appendChild($res[$cou]['Subcategory1']);
                            $published->appendChild($res[$cou]['RestaurantUrl']);
                            $published->appendChild($res[$cou]['Price']);
                            $published->appendChild($res[$cou]['GraphicsUrl']);
                            $published->appendChild($res[$cou]['Cuisine']);
                            $published->appendChild($res[$cou]['ProductUrl']);

                            $menu->appendChild($published);
                        }
                    }
                }
            }
            if (!empty($m['category_items']) && count($m['category_items']) > 0) {
                foreach ($m['category_items'] as $itemKeyM => $itemM) {
                    $cou++;
                    $cuis = getMenuCuisines($itemM['cuisines_id']);
                    $resUrl = WEB_HOST_URL . 'restaurants/' . $itemM['restaurant_name'] . '/' . $restaurant_id . '/menu';
                    $itemImage = ($itemM['item_image_url'] != '' && $itemM['item_image_url'] != NULL) ? $imageBaseUrl . strtolower($itemM['rest_code']) . '/' . $itemM['item_image_url'] : '';

                    $published = $dom->CreateElement("item");

                    $res[$cou]['ID'] = $dom->CreateElement("ID");
                    $res[$cou]['Name'] = $dom->CreateElement("Name");
                    $res[$cou]['Description'] = $dom->CreateElement("Description");
                    $res[$cou]['Brand'] = $dom->CreateElement("Brand");
                    $res[$cou]['RestaurantName'] = $dom->CreateElement("RestaurantName");
                    $res[$cou]['Category'] = $dom->CreateElement("Category");
                    $res[$cou]['Subcategory1'] = $dom->CreateElement("Subcategory1");
                    $res[$cou]['RestaurantUrl'] = $dom->CreateElement("RestaurantUrl");
                    $res[$cou]['Price'] = $dom->CreateElement("Price");
                    $res[$cou]['GraphicsUrl'] = $dom->CreateElement("GraphicsUrl");
                    $res[$cou]['Cuisine'] = $dom->CreateElement("Cuisine");
                    $res[$cou]['ProductUrl'] = $dom->CreateElement("ProductUrl");

                    $item_id = $dom->createTextNode(strL($itemM['item_id'],32));
                    $item_name = $dom->createTextNode(strL($itemM['item_name'],100));
                    $item_desc = $dom->createTextNode(strL($itemM['item_desc'],1024));
                    $brand = $dom->createTextNode('MunchAdo');
                    $restaurant_name = $dom->createTextNode(strL($itemM['restaurant_name'],512));
                    $category_name = $dom->createTextNode(strL($m['category_name'],255));
                    $sub_category_name = $dom->createTextNode('');
                    $restaurantUrl = $dom->createTextNode($resUrl);
                    $prc = (isset($itemM['prices'][0]['value']) && $itemM['prices'][0]['value'] > 0) ? $itemM['prices'][0]['value'] : 0;
                    $price = $dom->createTextNode($prc);
                    $image = $dom->createTextNode($itemImage);
                    $cuisine = $dom->createTextNode($cuis);
                    $productUrl = $dom->createTextNode('');


                    $res[$cou]['ID']->appendChild($item_id);
                    $res[$cou]['Name']->appendChild($item_name);
                    $res[$cou]['Description']->appendChild($item_desc);
                    $res[$cou]['Brand']->appendChild($brand);
                    $res[$cou]['RestaurantName']->appendChild($restaurant_name);
                    $res[$cou]['Category']->appendChild($category_name);
                    $res[$cou]['Subcategory1']->appendChild($sub_category_name);
                    $res[$cou]['RestaurantUrl']->appendChild($restaurantUrl);
                    $res[$cou]['Price']->appendChild($price);
                    $res[$cou]['GraphicsUrl']->appendChild($image);
                    $res[$cou]['Cuisine']->appendChild($cuisine);
                    $res[$cou]['ProductUrl']->appendChild($productUrl);

                    $published->appendChild($res[$cou]['ID']);
                    $published->appendChild($res[$cou]['Name']);
                    $published->appendChild($res[$cou]['Description']);
                    $published->appendChild($res[$cou]['Brand']);
                    $published->appendChild($res[$cou]['RestaurantName']);
                    $published->appendChild($res[$cou]['Category']);
                    $published->appendChild($res[$cou]['Subcategory1']);
                    $published->appendChild($res[$cou]['RestaurantUrl']);
                    $published->appendChild($res[$cou]['Price']);
                    $published->appendChild($res[$cou]['GraphicsUrl']);
                    $published->appendChild($res[$cou]['Cuisine']);
                    $published->appendChild($res[$cou]['ProductUrl']);

                    $menu->appendChild($published);

                }
            }
        }
    }
}

function getMenuCuisines($cuisinesId = false) {
    $cuisines = '';

    if (isset($cuisinesId) && !empty($cuisinesId) && $cuisinesId != '') {
        $CuisineModel = new Cuisine();
        $expCuis = explode(',', $cuisinesId);
        if (count($expCuis) > 0 && $expCuis != '') {
            $count = 0;
            foreach ($expCuis as $key => $valCuis) {
                if ($valCuis != '') {
                    $count++;
                    if ($count > 1) {
                        $cuisines.=',' . $CuisineModel->getCuisine($valCuis);
                    } else {
                        $cuisines.=$CuisineModel->getCuisine($valCuis);
                    }
                }
            }
        }
        return $cuisines;
    } else {
        return $cuisines;
    }
}

function strL($string,$len) {
    $string = strip_tags($string);

    if (strlen($string) > $len) {

        // truncate string
        $stringCut = substr($string, 0, $len);

        // make sure it ends in a word so assassinate doesn't become ass...
        $string = substr($stringCut, 0, strrpos($stringCut, ' ')) . '..';
    }
    return $string;
}
