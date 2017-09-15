<?php

namespace Restaurant\Controller;

use MCommons\Controller\AbstractRestfulController;
use Restaurant\Model\Menu;
use Restaurant\RestaurantDetailsFunctions;
use Bookmark\Model\FoodBookmark;
use Restaurant\Model\Restaurant;

class RestaurantMenuController extends AbstractRestfulController {

    public function get($restaurant_id = 0) {
        try {
            $memCached =  $this->getServiceLocator("memCachedObject");
            $config = $this->getServiceLocator('Config');
            $menuRes = '';
            if ($config['constants']['memcache'] && $memCached->getItem('menu_mob_' . $restaurant_id)) {
                $menuRes = $memCached->getItem('menu_mob_' . $restaurant_id);
            } else {
                $menuModel = $this->getServiceLocator(Menu::class);
                $restaurant = $this->getServiceLocator(Restaurant::class);
                $restDetails = $restaurant->findRestaurant(array('columns' => array('menu_sort_order'), 'where' => array('id' => $restaurant_id)));
                RestaurantDetailsFunctions::$_bookmark_types = $menuModel->bookmark_types;
                RestaurantDetailsFunctions::$_isMobile = $this->isMobile();
                $response = $menuModel->restaurantMenues(array(
                            'columns' => array(
                                'restaurant_id' => $restaurant_id,
                                'user_deals' => 0
                            )
                                ), $restDetails->menu_sort_order)->toArray();

                if (!empty($response)) {
                    $response = RestaurantDetailsFunctions::createNestedMenu($response, $restaurant_id);
                    $response = RestaurantDetailsFunctions::knowLastLeaf($response);
                    $response = RestaurantDetailsFunctions::formatResponseApi($response);
                } else {
                    throw new \Exception("Restaurant records not found", 405);
                }
                $menuRes = $response;
                $memCached->setItem('menu_mob_' . $restaurant_id, $response, 0);
            }

            $specialMenu = $this->menuSpecificDeal($restaurant_id);
            $userDealSpecialMenu = $this->particularUserDealOnMenu($restaurant_id);
            if (!empty($specialMenu)) {
                $specialMenu2[0] = $specialMenu;
                $menuRes = array_merge($specialMenu2, $menuRes);
            }

            if (!empty($userDealSpecialMenu)) {
                $specialUserDeal[0] = $userDealSpecialMenu;
                $menuRes = array_merge($specialUserDeal, $menuRes);
            }
            return $menuRes;
        } catch (\Exception $e) {
            \MUtility\MunchLogger::writeLog($e, 1, 'Something Went Wrong On Menu Api');
            throw new \Exception($e->getMessage(), 400);
        }
    }

    public function menuSpecificDeal($restaurant_id) {
        $menuModel = $this->getServiceLocator(Menu::class);
        $foodbookmark = new FoodBookmark ();
        $response = $menuModel->restaurantMenuesSpecific(array(
                    'columns' => array(
                        'restaurant_id' => $restaurant_id,
                        'user_deals' => 1
                    )
                ))->toArray();

        if (!empty($response)) {
            foreach ($response as $key => $val) {
                $response[$key]['item_id'] = $val['category_id'];
                $response[$key]['item_name'] = $val['category_name'];
                $response[$key]['item_desc'] = $val['category_desc'];
                unset($response[$key]['category_id']);
                unset($response[$key]['category_name']);
                unset($response[$key]['category_desc']);
                unset($response[$key]['pid']);
                $response[$key]['prices'] = $menuModel->restaurantMenuesSpecificPrice($val['category_id']);
                $bookmarkcount = $foodbookmark->getMenuBookmarkCount($restaurant_id, $val['category_id']);

                if ($bookmarkcount) {
                    foreach ($bookmarkcount as $bdata) {
                        $k = $bdata ['type'];
                        $bmdata [$k] = $bdata ['total_count'];
                    }
                    $response[$key] ['total_love_count'] = isset($bmdata ['lo']) ? (string) $bmdata ['lo'] : '0';
                    $response[$key] ['total_tryit_count'] = isset($bmdata ['ti']) ? (string) $bmdata ['ti'] : '0';
                    $response[$key] ['friend_loveit'] = '';
                } else {
                    $response[$key] ['total_love_count'] = '0';
                    $response[$key] ['total_tryit_count'] = '0';
                    $response[$key] ['friend_loveit'] = '';
                }
                $response[$key] ['dine-more'] = true;
            }
            $res['category_name'] = 'dine-more';
            $res['category_id'] = '9999999999';
            $res['prices'] = [];
            $res['sub_categories'] = [];
            $res['friend_loveit'] = '';
            $res['category_desc'] = '';
            $res['category_items'] = $response;
            return $res;
        }
        return [];
    }

    public function particularUserDealOnMenu($restaurant_id) {
        $userId = $this->getUserSession()->getUserId();
        if ($userId > 0) {
            $menuModel = $this->getServiceLocator(Menu::class);
            $foodbookmark = new FoodBookmark ();
            $response = $menuModel->particularUserDealOnMenu(array(
                        'columns' => array(
                            'restaurant_id' => $restaurant_id,
                            'user_deals' => 1
                        )
                    ))->toArray();

            if (!empty($response)) {
                foreach ($response as $key => $val) {
                    $response[$key]['item_id'] = $val['category_id'];
                    $response[$key]['item_name'] = $val['category_name'];
                    $response[$key]['item_desc'] = $val['category_desc'];
                    unset($response[$key]['category_id']);
                    unset($response[$key]['category_name']);
                    unset($response[$key]['category_desc']);
                    unset($response[$key]['pid']);
                    $response[$key]['prices'] = $menuModel->restaurantMenuesSpecificPrice($val['category_id']);
                    $bookmarkcount = $foodbookmark->getMenuBookmarkCount($restaurant_id, $val['category_id']);

                    if ($bookmarkcount) {
                        foreach ($bookmarkcount as $bdata) {
                            $k = $bdata ['type'];
                            $bmdata [$k] = $bdata ['total_count'];
                        }
                        $response[$key] ['total_love_count'] = isset($bmdata ['lo']) ? (string) $bmdata ['lo'] : '0';
                        $response[$key] ['total_tryit_count'] = isset($bmdata ['ti']) ? (string) $bmdata ['ti'] : '0';
                        $response[$key] ['friend_loveit'] = '';
                    } else {
                        $response[$key] ['total_love_count'] = '0';
                        $response[$key] ['total_tryit_count'] = '0';
                        $response[$key] ['friend_loveit'] = '';
                    }
                    $response[$key] ['dine-more'] = true;
                }
                $res['category_name'] = 'dine-more';
                $res['category_id'] = '9999999999';
                $res['prices'] = [];
                $res['sub_categories'] = [];
                $res['friend_loveit'] = '';
                $res['category_desc'] = '';
                $res['category_items'] = $response;
                return $res;
            }
            return [];
        } else {
            return [];
        }
    }

}
