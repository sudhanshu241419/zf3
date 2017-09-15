<?php

namespace Restaurant\Controller;

use MCommons\Controller\AbstractRestfulController;
use Restaurant\Model\RestaurantReview;
use User\Model\UserReview;
use MCommons\CommonFunctions;
use MCommons\StaticFunctions;
use User\Model\UserTip;
use City\Model\City;
use User\Model\UserReviewImage;

class RestaurantReviewController extends AbstractRestfulController {

    public $formatCount = [];
    public $formatRestaurantReview = [];
    private static $orderMapping = [
        'date' => 'desc',
        'rating' => 'desc',
        'type' => 'desc'
    ];

    public function get($id) {
        $config = $this->getServiceLocator('Config');
        $city = $this->getServiceLocator(City::class);
        $userId = $this->getUserSession()->getUserId();
        $commonFucntions = new CommonFunctions ();
        $queryParams = $this->getRequest()->getQuery()->toArray();
        $order = isset($queryParams ['sort']) ? $queryParams ['sort'] : '';
        if (!preg_match('/(date|rating|type)$/', $order)) {
            $order = false;
        }
        $restaurantReviewModel = $this->getServiceLocator(RestaurantReview::class);
        $options = array(
            'columns' => array(
                'date',
                'reviewer',
                'reviews',
                'review_type',
                'sentiments',
                'source',
                'source_url',
                'sort_date' => 'date',
                'review_date' => new \Zend\Db\Sql\Expression('DATE_FORMAT(date, "%d %b,%Y")')
            ),
            'where' => array(
                'restaurant_id' => $id,
            )
        );
        $restaurantReview = $restaurantReviewModel->find($options)->toArray();
        $normalReviews = array_filter($restaurantReview, function (&$restaurantReview) {
            return $restaurantReview ['review_type'] == "N";
        });

        array_walk($restaurantReview, array(
            $this,
            'formatDate'
        ));
        $userReviewModel = $this->getServiceLocator(UserReview::class);
        $joins = array();
        $joins [] = array(
            'name' => array(
                'umr' => 'user_menu_reviews'
            ),
            'on' => 'umr.user_review_id = user_reviews.id',
            'columns' => array(
                'menu_id',
                'image_name',
                'liked'
            ),
            'type' => 'left'
        );
        $joins [] = array(
            'name' => array(
                'm' => 'menus'
            ),
            'on' => 'm.id = umr.menu_id',
            'columns' => array(
                'menu_name' => 'item_name'
            ),
            'type' => 'left'
        );
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
        $joins [] = array(
            'name' => array(
                'oresponse' => 'owner_response'
            ),
            'on' => 'user_reviews.id = oresponse.review_id',
            'columns' => array(
                'owner_response_id' => 'id',
                'response',
                'response_date'
            ),
            'type' => 'left'
        );
        if ($userId) {
            $joins [] = array(
                'name' => array(
                    'uf' => 'user_feedback'
                ),
                'on' => new \Zend\Db\Sql\Expression('user_reviews.id = uf.review_id AND uf.user_id =' . $userId),
                'columns' => array(
                    'feedback'
                ),
                'type' => 'left'
            );
        }
        $joins[] = array(
            'name' => array(
                'r' => 'restaurants'
            ),
            'on' => 'r.id=user_reviews.restaurant_id',
            'columns' => array('rest_code'),
            'type' => 'left'
        );
        $options = array(
            'columns' => array(
                'review_id' => 'id',
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
                'restaurant_responded_on' => 'created_on',
                'sentiment',
                'owner_response' => 'restaurant_response',
                'date' => 'created_on', 'approved_date',
                'order_id'
            ),
            'where' => array(
                'user_reviews.restaurant_id' => $id,
                'user_reviews.status' => 1
            ),
            'joins' => $joins
        );
        $userReviewDetails = $userReviewModel->find($options)->toArray();
        $finalReviewDetails = [];
        foreach ($userReviewDetails as $key => $value) {
            if (!isset($finalReviewDetails [$userReviewDetails [$key] ['review_id']])) {
                $finalReviewDetails [$userReviewDetails [$key] ['review_id']] = $value;
                $finalReviewDetails [$userReviewDetails [$key] ['review_id']] ['type'] = 'user';
                $finalReviewDetails [$userReviewDetails [$key] ['review_id']] ['owner_response'] = [];
                $finalReviewDetails [$userReviewDetails [$key] ['review_id']] ['food_details'] = [];
                $finalReviewDetails [$userReviewDetails [$key] ['review_id']] ['feedback_count'] = $commonFucntions->getFeedbackCount($userReviewDetails [$key] ['review_id']);
            }
            $finalReviewDetails [$userReviewDetails [$key] ['review_id']]['review_for'] = $config['constants']['review_for'][$value['review_for']];
            $finalReviewDetails [$userReviewDetails [$key] ['review_id']]['on_time'] = $value['on_time'];
            $finalReviewDetails [$userReviewDetails [$key] ['review_id']]['fresh_prepared'] = $value['fresh_prepared'];
            $finalReviewDetails [$userReviewDetails [$key] ['review_id']]['as_specifications'] = $value['as_specifications'];
            $finalReviewDetails [$userReviewDetails [$key] ['review_id']]['temp_food'] = $value['temp_food'];
            $finalReviewDetails [$userReviewDetails [$key] ['review_id']]['taste_test'] = $value['taste_test'];
            $finalReviewDetails [$userReviewDetails [$key] ['review_id']]['services'] = $value['services'];
            $finalReviewDetails [$userReviewDetails [$key] ['review_id']]['noise_level'] = $value['noise_level'];
            $finalReviewDetails [$userReviewDetails [$key] ['review_id']]['order_again'] = $value['order_again'];
            $finalReviewDetails [$userReviewDetails [$key] ['review_id']]['come_back'] = $value['come_back'];
            if ($value['owner_response_id'] != null) {
                $finalReviewDetails [$userReviewDetails [$key] ['review_id']] ['owner_response'][$value['owner_response_id']] = array(
                    'response' => $value ['response'],
                    'restaurant_responded_on' => $value ['response_date']
                );
            }

            if ($value ['menu_id'] != null && !isset($finalReviewDetails [$userReviewDetails [$key] ['review_id']] ['food_details'][$value ['menu_id']])) {
                $finalReviewDetails [$userReviewDetails [$key] ['review_id']] ['menu_review_images'] [] = array(
                    'item_id' => $value ['menu_id'],
                    'item' => $value ['menu_name'],
                    'image' => ($value ['image_name']) ? WEB_URL . USER_IMAGE_UPLOAD . strtolower($value['rest_code']) . DS . 'reviews' . DS . $value ['image_name'] : ""
                );
            } else {
                $finalReviewDetails [$userReviewDetails [$key] ['review_id']] ['menu_review_images'] = array();
            }
        }
        $finalReview = $userIds = [];

        foreach ($finalReviewDetails as $key => $value) {
            $finalReviewDetails[$key]['food_details'] = array_values($finalReviewDetails[$key]['food_details']);
            $finalReviewDetails[$key]['owner_response'] = array_values($finalReviewDetails[$key]['owner_response']);
            if (isset($value ['user_id'])) {
                $userIds [] = $value ['user_id'];
            }
            if (isset($finalReviewDetails [$key] ['image_path'])) {
                $finalReviewDetails [$key] ['image_path'] = WEB_URL . 'restaurant_images' . DS . $finalReviewDetails [$key] ['image_path'];
            }
            $finalReviewDetails [$key] ['stats'] ['joined_on'] = ($value ['created_at'] != null) ? $commonFucntions->datetostring($value ['created_at'], $id) : null;
            $finalReviewDetails [$key] ['stats'] ['first_name'] = $value ['first_name'];
            if (isset($value['city_id']) && !empty($value['city_id'])) {
                $cityData = $city->cityDetails($value['city_id']);
                $finalReviewDetails[$key]['stats']['city'] = $cityData[0]['city_name'];
            } else {
                $finalReviewDetails[$key]['stats']['city'] = '';
            }
            $data = $commonFucntions->checkProfileImageUrl([
                'display_pic_url' => $value ['display_pic_url'],
                'id' => $value ['user_id']
            ]);
            $finalReviewDetails [$key] ['stats'] ['display_pic_url'] = $data ['display_pic_url'];
            $finalReviewDetails [$key] ['stats'] ['shipping_address'] = $value ['shipping_address'];
            $finalReviewDetails[$key]['stats']['badge'] = 'Food Pandit';
            $keysToRemove = [
                'food_reviewed',
                'food_ordered',
                'item',
                'menu_id',
                'liked',
                'image_name',
                'menu_name',
                'created_at',
                'shipping_address',
                'display_pic_url',
                'first_name',
                'owner_response_id',
                'response',
                'response_date',
                'restaurant_responded_on'
            ];
            $finalReview [] = array_diff_key($finalReviewDetails [$key], array_flip($keysToRemove));
        }
        $userIds = array_unique($userIds);
        $data = $commonFucntions->getUserHistoryForMob($userIds);

        unset($data ['joined_on']);
        $review = [];
        foreach ($userIds as $userId) {
            if (!isset($review [$userId])) {
                $review [$userId] = [
                    'total_reviews' => '',
                    'total_orders' => '',
                    'total_reserve' => ''
                ];
            }
        }

        if (is_array($data)) {
            array_walk($data, [
                $this,
                'mapper'
            ]);
        }
        foreach ($finalReview as $key => $value) {
            $bookmarks = $commonFucntions->getUserHistoryForMob(array($value['user_id']));
            $finalReview [$key] ['dashboard_url'] = $config['image_base_urls']['local-cms'] . DS . 'review' . DS . $finalReview [$key] ['review_id'];
            $finalReview [$key] ['date'] = $value ['date'];
            $finalReview [$key] ['sort_date'] = \MCommons\StaticFunctions::getFormattedDateTime($value ['approved_date'], 'Y-m-d H:i:s', 'Y-m-d H:i:s');
            $finalReview [$key] ['stats'] ['total_beenthere'] = isset($bookmarks['beenthere'][0]['total_beenthere']) ? $bookmarks['beenthere'][0]['total_beenthere'] : 0;
            $finalReview [$key] ['stats'] ['total_tryit'] = isset($bookmarks['totalOrder'][0]['total_order']) ? $bookmarks['totalOrder'][0]['total_order'] : 0;
            $finalReview [$key] ['stats'] ['total_reservations'] = isset($bookmarks['reserve'][0]['total_reservation']) ? $bookmarks['reserve'][0]['total_reservation'] : 0;
            $finalReview [$key] ['stats'] ['total_reviews'] = isset($bookmarks['review'][0]['total_reviews']) ? $bookmarks['review'][0]['total_reviews'] : 0;

            if (($value['review_for'] == 1 || $value['review_for'] == 2)) {
                $finalReview [$key] ['order_id'] = $value['order_id'];
            } elseif ($value['review_for'] == 3) {
                $finalReview [$key] ['reservation_id'] = $value['order_id'];
                unset($finalReview [$key] ['order_id']);
            }


            ###################
            $options = ['columns' => ['image', 'image_url'], 'where' => ['user_review_id' => $value['review_id']]];
            $userReviewImage = $this->getServiceLocator(UserReviewImage::class);
            $reviewImages = $userReviewImage->find($options)->toArray();
            if ($reviewImages) {
                $finalReview[$key]['review_images'] = $reviewImages;
            } else {
                $finalReview[$key]['review_images'] = [];
            }
            ###################
        }
        //pr($finalReview,true);
        $limit = $this->getQueryParams('limit', SHOW_PER_PAGE);
        $page = $this->getQueryParams('page', 1);
        $offset = 0;
        if ($page > 0) {
            $page = ($page < 1) ? 1 : $page;
            $offset = ($page - 1) * ($limit);
        }

        $userTip = $this->getServiceLocator(UserTip::class);
        $joins = [];
        $joins [] = array(
            'name' => array(
                'u' => 'users'
            ),
            'on' => 'u.id = user_tips.user_id',
            'columns' => array(
                'user_id' => new \Zend\Db\Sql\Expression('u.id'),
                'first_name',
                'display_pic_url',
                'shipping_address',
                'joined_on' => new \Zend\Db\Sql\Expression('u.created_at'),
            ),
            'type' => 'left'
        );
        $options = array(
            'columns' => array(
                'tip_id' => 'id',
                'tip',
                'created_at', 'approved_date'
            ),
            'where' => array(
                'restaurant_id' => $id,
                'user_tips.status' => '1',
            ),
            'joins' => $joins
        );

        if ($userTip->find($options)->toArray()) {
            $tips = $userTip->find($options)->toArray();
        } else {
            $tips = [];
        }
        $restaurantTotalTips = $userTip->restaurantTotalTips($id);
        if (!empty($tips) && count($tips) > 0) {
            foreach ($tips as $key => $tip) {
                $data = $commonFucntions->checkProfileImageUrl(array(
                    'display_pic_url' => $tips[$key]['display_pic_url'],
                    'id' => $tips[$key]['user_id']
                ));
                $tips[$key]['display_pic_url'] = $data['display_pic_url'];
                $bookmarks = $commonFucntions->getUserHistoryForMob(array($tip['user_id']));
                $tips[$key]['joined_on'] = ($tips[$key]['joined_on'] != null) ? $commonFucntions->datetostring($tip['joined_on'], $id) : null;
                $tips[$key]['badge'] = 'Food Pandit';
                $tips[$key]['total_beenthere'] = isset($bookmarks['beenthere'][0]['total_beenthere']) ? $bookmarks['beenthere'][0]['total_beenthere'] : 0;
                $tips[$key]['total_tryit'] = isset($bookmarks['totalOrder'][0]['total_order']) ? $bookmarks['totalOrder'][0]['total_order'] : 0;
                $tips[$key]['total_reservations'] = isset($bookmarks['reserve'][0]['total_reservation']) ? $bookmarks['reserve'][0]['total_reservation'] : 0;
                $tips[$key]['total_reviews'] = isset($bookmarks['review'][0]['total_reviews']) ? $bookmarks['review'][0]['total_reviews'] : 0;
                $tips[$key]['sort_date'] = date('Y-m-d H:i:s', strtotime($tip ['approved_date']));
                $tips[$key]['type'] = 'tip';
                $tips[$key]['date'] = ($tips[$key]['created_at'] != null) ? $commonFucntions->datetostring($tip['created_at'], $id) : null;
                unset($tips[$key]['created_at']);
            }
        }
        $final1 = array_merge($this->formatRestaurantReview, $finalReview, $tips);
        $final2 = array_merge($this->formatRestaurantReview, $finalReview);
        $totalReviews = count($final2) + $restaurantTotalTips['total_count'];
        foreach ($final1 as $key => $val) {
            $sortDate[$key] = strtotime($val['sort_date']);
        }
        if (!$order || $order == 'date' || $order == 'type') {
            if ($final1) {
                array_multisort($sortDate, SORT_DESC, $final1);
            }
        } elseif ($order == 'rating') {
            uasort($final1, array(
                $this,
                'rating_compare'
            ));
        }

        $final = array_slice($final1, $offset, $limit);

        $percentPositive = $this->positiveSentimentPercentage($id);
        //pr($totalReviews,true);   
        return array(
            'reviews' => $final,
            'positive_sentiment_percent' => $percentPositive,
            'total_review_count' => $totalReviews,
        );
    }

    public function mapper($value, $key) {
        foreach ($value as $single) {
            if (isset($single ['user_id'])) {
                $this->formatCount [$key] [$single ['user_id']] = $single;
            }
        }
    }

    public function formatDate($value) {
        $commonFucntions = new CommonFunctions ();
        $value['type'] = 'restaurant';
        if (isset($value ['date']) && strtotime($value ['date']) < strtotime("1980-01-01")) {
            $value ['review_date'] = \MCommons\StaticFunctions::getFormattedDateTime('2014-05-11', 'Y-m-d', 'd M Y');
        }
        $value ['date'] = ($value ['date'] == "0000-00-00" || $value ['date'] == '' || $value ['date'] == NULL) ? "2014-05-11" : $value ['date'];
        $value ['date'] = $commonFucntions->datetostring($value ['date']);

        $dateArr = explode(" ", $value ['date']);

        if ($dateArr[1] == "decades") {
            $value ['date'] = "";
        }

        if (isset($value ['sort_date']) && strtotime($value ['sort_date']) > strtotime("1980-01-01")) {
            $value ['sort_date'] = \MCommons\StaticFunctions::getFormattedDateTime($value ['sort_date'], 'Y-m-d', 'Y-m-d');
        } else {
            $value ['sort_date'] = \MCommons\StaticFunctions::getFormattedDateTime('2014-05-11', 'Y-m-d', 'Y-m-d');
        }
        $this->formatRestaurantReview [] = $value;
    }

    function date_compare($a, $b) {
        $t1 = strtotime($a ['date']);
        $t2 = strtotime($b ['date']);
        $t3 = ($t1 > $t2) ? - 1 : 1;
        return $t3;
    }

    function rating_compare($a, $b) {
        $t1 = isset($a ['rating']) ? $a ['rating'] : 0;
        $t2 = isset($b ['rating']) ? $b ['rating'] : 0;
        if ($t1 == $t2) {
            $t1 = strtotime($a ['date']);
            $t2 = strtotime($b ['date']);
        }
        $t3 = ($t1 > $t2) ? - 1 : 1;
        return $t3;
    }

    function positiveSentimentPercentage($restaurant_id) {
        $reviewModel = $this->getServiceLocator(RestaurantReview::class);
        $userReviewModel = $this->getServiceLocator(UserReview::class);

        $consolidatedReviews = $reviewModel->getReviews(array(
            'columns' => array(
                'consolidated_review' => 'reviews',
            ),
            'where' => array(
                'restaurant_id' => $restaurant_id,
                'review_type' => 'C'
            )
                )
        );
        $NormalReviews = $reviewModel->getReviews(array(
            'columns' => array(
                'consolidated_review' => 'reviews',
            ),
            'where' => array(
                'restaurant_id' => $restaurant_id,
                'review_type' => 'N'
            )
                )
        );

        $NormalReviewCount = count($NormalReviews);

        $positiveSentiments = $reviewModel->getReviews(array(
            'columns' => array(
                'positive_review' => 'reviews',
            ),
            'where' => array(
                'restaurant_id' => $restaurant_id,
                'sentiments' => 'Positive',
                'review_type' => 'N'
            )
                )
        );
        $positiveUserSentiments = $userReviewModel->getAllUserReview(array(
            'columns' => array(
                'sentiment',
            ),
            'where' => array(
                'restaurant_id' => $restaurant_id,
                'sentiment' => 1,
                'status' => 1
            )
        ));
        $positiveSentimentsCount = count($positiveSentiments) + count($positiveUserSentiments);
        if ($positiveSentiments) {
            $postiveReview = array_pop($positiveSentiments);
        } else {
            $postiveReview = '';
        }

        //count user reviews also
        $userReviewCountOptions = array(
            'columns' => array(
                'total' => new \Zend\Db\Sql\Expression('COUNT(*)')
            ),
            'where' => array(
                'restaurant_id' => $restaurant_id,
                'status' => 1
            )
        );
        $userReviewCount = $userReviewModel->find($userReviewCountOptions)->current()->getArrayCopy();
        $total_review_count = $NormalReviewCount + $userReviewCount['total'];
        $positive_sentiment_count = $total_review_count != 0 ? ceil(($positiveSentimentsCount * 100) / $total_review_count) : '0';
        return $positive_sentiment_count;
    }

    public static function compare($ord1, $ord2) {
        if ($ord1['date'] == $ord2['date']) {
            return 0;
        }
        return ($ord1['date'] < $ord2['date']) ? 1 : -1;
    }

}
