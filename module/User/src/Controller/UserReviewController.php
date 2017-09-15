<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use User\Model\UserReview;
use User\Model\UserMenuReview;
use MCommons\CommonFunctions;
use User\Model\UserReviewImage;
use User\Functions\UserFunctions;

class UserReviewController extends AbstractRestfulController {

    public $formatCount = [];

    public function get($review_id = 0) {
        $config = $this->getServiceLocator('Config');
        $userId = $this->getUserSession()->getUserId();
        $friendId = $this->getQueryParams('friendid', false);
        if ($friendId) {
            $userId = $friendId;
        }
        if (!$review_id) {
            throw new \Exception("Invalid Parameters", 400);
        }
        $userReviewModel = $this->getServiceLocator(UserReview::class);
        $commonFucntions = new CommonFunctions();
        $userFunctions = $this->getServiceLocator(UserFunctions::class);
        $joins = [];
        $joins [] = array(
            'name' => array(
                'u' => 'users'
            ),
            'on' => 'u.id = user_reviews.user_id',
            'columns' => array(
                'first_name',
                'display_pic_url',
                'created_at',
                'shipping_address',
                'city_id'
            ),
            'type' => 'left'
        );

        $joins[] = array(
            'name' => array(
                'r' => 'restaurants'
            ),
            'on' => 'r.id=user_reviews.restaurant_id',
            'columns' => array('rest_code', 'restaurant_name', 'inactive', 'closed'),
            'type' => 'left'
        );
        $joins[] = array(
            'name' => array(
                'owner' => 'owner_response'
            ),
            'on' => 'owner.review_id=user_reviews.id',
            'columns' => array('owner_response_id' => 'id', 'response', 'response_date'),
            'type' => 'left'
        );

        $options = array(
            'columns' => array(
                'review_id' => 'id',
                'restaurant_id',
                'user_id',
                'review_for',
                'on_time',
                'fresh_prepared',
                'as_specifications',
                'temp_food',
                'taste_test',
                'services',
                'noise_level',
                'rating',
                'order_again',
                'come_back',
                'review_desc',
                'sentiment',
                'date' => 'created_on',
                'order_id',
                'status'
            ),
            'where' => array(
                'user_reviews.id' => $review_id,
                'user_reviews.status' => array(0, 1, 2)
            ),
            'joins' => $joins
        );
        $response = $userReviewModel->find($options)->toArray();
        if (!empty($response)) {
            $ownerResponse = [];
            $ownerResponseMessage = "";
            $responseDate = "";
            $i = 0;
            foreach ($response as $key => $or) {
                if (isset($or['owner_response_id'])) {
                    $ownerResponse[$i]['owner_response_id'] = $or['owner_response_id'];
                    $ownerResponse[$i]['owner_response'] = $or['response'];
                    $ownerResponse[$i]['restaurant_responded_on'] = $or['response_date'];
                    $ownerResponseMessage = $or['response'];
                    $responseDate = $or['response_date'];
                    $i++;
                }
            }
            $reviewDetail = $response[0];
            $data = $commonFucntions->getUserHistoryForMob([$reviewDetail['user_id']]);
        } else {
            throw new \Exception("Review not exist", 400);
        }
        unset($data ['joined_on']);

        if (is_array($data)) {
            array_walk($data, array(
                $this,
                'mapper'
            ));
        }
        if (!$reviewDetail) {
            throw new \Exception("Records not found", 400);
        }

        $reviewDetail['stats'] = [];
        $reviewDetail['stats']['first_name'] = $reviewDetail['first_name'];
        $reviewDetail['stats']['shipping_address'] = $reviewDetail['shipping_address'];
        unset($reviewDetail['first_name']);
        unset($reviewDetail['shipping_address']);
        $reviewDetail['stats']['total_beenthere'] = isset($this->formatCount ['beenthere'] [$reviewDetail ['user_id']] ['total_beenthere']) ? $this->formatCount ['beenthere'] [$reviewDetail ['user_id']] ['total_beenthere'] : 0;
        $reviewDetail['stats']['total_tryit'] = isset($this->formatCount ['totalOrder'] [$reviewDetail ['user_id']] ['total_order']) ? $this->formatCount ['totalOrder'] [$reviewDetail ['user_id']] ['total_order'] : 0;
        $reviewDetail['stats']['total_reservations'] = isset($this->formatCount ['reserve'] [$reviewDetail ['user_id']] ['total_reservation']) ? $this->formatCount ['reserve'] [$reviewDetail ['user_id']] ['total_reservation'] : 0;
        $reviewDetail['stats']['total_reviews'] = isset($this->formatCount ['review'] [$reviewDetail ['user_id']] ['total_reviews']) ? $this->formatCount ['review'] [$reviewDetail ['user_id']] ['total_reviews'] : 0;

        $reviewDetail['review_for'] = $config['constants']['review_for'][$reviewDetail['review_for']];
        $reviewDetail['come_back'] = ($reviewDetail['come_back'] == 0) ? "2" : $reviewDetail['come_back'];
        $reviewDetail['date'] = !$reviewDetail ['date'] ? '' : $reviewDetail ['date'];
        $reviewDetail['on_time'] = ($reviewDetail['on_time'] == 0) ? "2" : $reviewDetail['on_time'];
        $reviewDetail['fresh_prepared'] = ($reviewDetail['fresh_prepared'] == 0) ? "2" : $reviewDetail['fresh_prepared'];
        $reviewDetail['as_specifications'] = ($reviewDetail['as_specifications'] == 0) ? "2" : $reviewDetail['as_specifications'];
        $reviewDetail['temp_food'] = ($reviewDetail['temp_food'] == 0) ? "1" : $reviewDetail['temp_food'];
        $reviewDetail['taste_test'] = ($reviewDetail['taste_test'] == 0) ? "1" : $reviewDetail['taste_test'];
        $reviewDetail['services'] = ($reviewDetail['services'] == 0) ? "1" : $reviewDetail['services'];
        $reviewDetail['noise_level'] = ($reviewDetail['noise_level'] == 0) ? "1" : $reviewDetail['noise_level'];
        $reviewDetail['order_again'] = ($reviewDetail['order_again'] == 0) ? "2" : $reviewDetail['order_again'];
        $reviewDetail['order_id'] = ($reviewDetail['order_id'] == 0) ? NULL : $reviewDetail['order_id'];

        if ($reviewDetail['inactive'] == 1 || $reviewDetail['closed'] == 1) {
            $reviewDetail['is_restaurant_exist'] = 0;
        } else {
            $reviewDetail['is_restaurant_exist'] = 1;
        }
        $reviewDetail['stats']['joined_on'] = !$reviewDetail ['created_at'] ? '' : $commonFucntions->datetostring($reviewDetail ['created_at']);
        $myLastEarnedMuncher = $userFunctions->getMyLastEarnedMuncher();
        if ($myLastEarnedMuncher) {
            $reviewDetail['stats']['badge'] = $myLastEarnedMuncher['title'];
        } else {
            $reviewDetail['stats']['badge'] = null;
        }
        $cityModel = $this->getServiceLocator(\City\Model\City::class);
        $cityDetails = $cityModel->cityDetails($reviewDetail['city_id']);
        if ($cityDetails) {
            $reviewDetail['stats']['city'] = $cityDetails[0]['city_name'];
        } else {
            $reviewDetail['stats']['city'] = '';
        }
        $data = $commonFucntions->checkProfileImageUrl(array(
            'display_pic_url' => $reviewDetail ['display_pic_url'],
            'id' => $reviewDetail ['user_id']
        ));
        $reviewDetail['stats']['display_pic_url'] = (isset($data['display_pic_url']) && !empty($data['display_pic_url'])) ? $data['display_pic_url'] : WEB_URL . 'img' . DS . 'noimage.jpg';
        $reviewDetail['owner_response'] = $ownerResponseMessage;
        $reviewDetail['restaurant_responded_on'] = $responseDate;
        $reviewDetail['all_owner_response'] = $ownerResponse;
        unset($reviewDetail ['response'], $reviewDetail ['response_date'], $reviewDetail ['created_at'], $reviewDetail['rest_code'], $reviewDetail['display_pic_url']);
        unset($reviewDetail['inactive'], $reviewDetail['closed']);

        ###### Review Find UseFull ######        
        $findUsefullCount = $userFunctions->isReviewUsefullCount($review_id);
        if (!empty($findUsefullCount) && $findUsefullCount != null && $findUsefullCount['total_usefull_count'] != 0) {
            $reviewDetail['review_find_useful']['count'] = $findUsefullCount['total_usefull_count'];
        } else {
            $reviewDetail['review_find_useful']['count'] = "0";
        }
        $findUsefullForUser = $userFunctions->isReviewUsefullForUser($review_id, $userId);
        if (!empty($findUsefullForUser) && $findUsefullForUser != null && $findUsefullForUser['total_usefull_count'] != 0) {
            $reviewDetail['review_find_useful']['find_useful'] = $findUsefullForUser['feedback'];
        } else {
            $reviewDetail['review_find_useful']['find_useful'] = "2";
        }

        $options = ['columns' => ['image_url'], 'where' => ['user_review_id' => $review_id]];
        $userReviewImage = $this->getServiceLocator(UserReviewImage::class);
        $reviewImages = $userReviewImage->find($options)->toArray();
        if (count($reviewImages) > 0) {
            foreach ($reviewImages as $key => $val) {
                $reviewDetail['review_images'][] = $val['image_url'];
            }
        } else {
            $reviewDetail['review_images'] = [];
        }
        ########## Review Menu Detail ############
        $menuReview = $this->getServiceLocator(UserMenuReview::class);
        $menuReviewJoin = [];

        $menuReviewJoin [] = array(
            'name' => array(
                'm' => 'menus'
            ),
            'on' => 'm.id = user_menu_reviews.menu_id',
            'columns' => array(
                'item_name',
            ),
            'type' => 'left'
        );

        $menuReviewOption = array(
            'columns' => array(
                'is_liked' => 'liked',
                'menu_id'
            ),
            'where' => array(
                'user_review_id' => $review_id,
            ),
            'joins' => $menuReviewJoin
        );
        $reviewedMenu = $menuReview->find($menuReviewOption)->toArray();
        if ($reviewedMenu) {
            foreach ($reviewedMenu as $key => $val) {
                $reviewedMenu[$key]['is_liked'] = intval($val['is_liked']);
            }
            $items = array('items' => $reviewedMenu);
        } else {
            $items = array('items' => null);
        }
        ##########################################
        $reviewDetail = array_merge($reviewDetail, $items);

        return $reviewDetail;
    }

    public function mapper($value, $key) {
        foreach ($value as $single) {
            if (isset($single ['user_id'])) {
                $this->formatCount [$key] [$single ['user_id']] = $single;
            }
        }
    }

}
