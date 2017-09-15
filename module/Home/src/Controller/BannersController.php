<?php

namespace Home\Controller;

use MCommons\Controller\AbstractRestfulController;
use Restaurant\Model\RestaurantServer;

class BannersController extends AbstractRestfulController {

    public function getList() {
        $banners = [];
        $session = $this->getUserSession();
        $isLoggedIn = $session->isLoggedIn();
        $userId = $session->getUserId();
        $serverModel = $this->getServiceLocator(RestaurantServer::class);
        $userDineAndMoreRestaurant = $serverModel->userDineAndMoreRestaurant($userId);
        $location = $session->getUserDetail('selected_location');
        if ($location['city_id'] == 18848) {
            if ($isLoggedIn && !empty($userDineAndMoreRestaurant) && $userId > 0) {
                foreach ($userDineAndMoreRestaurant as $key => $val) {
                    $banners[$key]['restaurantId'] = $val['restaurant_id'];
                    $banners[$key]['rest_name'] = $val['restaurant_name'];
                    $banners[$key]['rest_tagline'] = $val['restaurant_name'];
                    $banners[$key]['banner_for'] = "";
                    $banners[$key]['banner_img'] = WEB_IMG_URL . "munch_images/" . strtolower($val['rest_code']) . "/" . $val['restaurant_image_name'];
                    $banners[$key]['banner_img_6p'] = WEB_IMG_URL . "munch_images/" . strtolower($val['rest_code']) . "/" . $val['restaurant_image_name'];
                    $banners[$key]['type'] = 1;
                }
            } else {
                $banners = $this->getStaticBanners();
            }
        }
        return $banners;
    }

    public function getStaticBanners() {
        $banners = [
            ['restaurantId' => '61086',
                'rest_name' => 'Aria',
                'rest_tagline' => 'Place an Order',
                'banner_for' => 'order',
                'banner_img' => WEB_URL . 'img/banner2@2x.png',
                'banner_img_6p' => WEB_URL . 'img/banner2@3x.png',
                'type' => 0
            ],
            ['restaurantId' => '57914',
                'rest_name' => 'Bareburger',
                'rest_tagline' => 'Taste the Difference',
                'banner_for' => 'order',
                'banner_img' => WEB_URL . 'img/banner@2x.png',
                'banner_img_6p' => WEB_URL . 'img/banner@3x.png',
                'type' => 0
            ],
            ['restaurantId' => '58252',
                'rest_name' => 'IL Melograno',
                'rest_tagline' => 'Place an Order',
                'banner_for' => 'order',
                'banner_img' => WEB_URL . 'img/banner3@2x.png',
                'banner_img_6p' => WEB_URL . 'img/banne3@3x.png',
                'type' => 0
            ],
        ];
        return $banners;
    }

}
