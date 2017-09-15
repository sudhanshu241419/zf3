<?php

namespace MCommons;

use Auth\Model\UserSession;
use Zend\Db\Sql\Predicate\Expression;
use User\Model\UserOrder;
use User\Model\UserReservation;
use User\Model\UserReview;
use Restaurant\Model\UserFeedback;
use Restaurant\Model\RestaurantBookmark;
use Restaurant\Model\MenuBookmark;
use User\Model\ActivityFeed;
use User\Model\ActivityFeedType;
use Zend\Json\Json;

class CommonFunctions {

    public static $config = array(
        'adapter' => 'Zend\Http\Client\Adapter\Curl',
        'curloptions' => array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 6000
        )
    );

    public static function getUserLocationData() {
        $ip_address = self::getRealIp();
        if ($ip_address == '127.0.0.1') {
            // $ip_address = '199.27.74.134'; //US IP
            $ip_address = '14.141.65.34'; // INDIA IP
        }
        $userSession = new UserSession ();
        $userDetail = $userSession->getUserDetail();
        if (!isset($userDetail ['user_loc_data'])) {
            $user_loc_data = self::getUserLocationByIp($ip_address);
            if (empty($user_loc_data)) {
                return false;
            }

            if ($user_loc_data ['state_code'] == 'Limit Exceeded') {
                $user_loc_data ['city'] = 'San Francisco';
                $user_loc_data ['city_id'] = 23637;
                $user_loc_data ['state_code'] = 'CA';
                $user_loc_data ['time_zone'] = DEFAULT_TIME_ZONE;
                $session = $userSession->setUserDetail('city_id', $user_loc_data ['city_id']);
                $session = $userSession->setUserDetail('city_name', $user_loc_data ['city']);
                $session = $userSession->setUserDetail('state_code', $user_loc_data ['state_code']);

                $session->save();
            }
            $cityModel = StaticFunctions::getServiceLocator()->get(\City\Model\City::class);
            $cityData = $cityModel->getCity(array(
                        'columns' => array(
                            'id',
                            'city_name',
                            'state_code',
                            'time_zone'
                        ),
                        'where' => array(
                            'state_code' => $user_loc_data ['state_code'],
                            'city_name' => $user_loc_data ['city'],
                            'status' => 1
                        )
                    ))->current();

            if (!empty($cityData)) {

                $user_loc_data ['city_id'] = $cityData->id;

                if (!empty($cityData->time_zone))
                    $user_loc_data ['time_zone'] = $cityData->time_zone;
                else
                    $user_loc_data ['time_zone'] = DEFAULT_TIME_ZONE;

                $user_loc_data ['city_present'] = true;
                date_default_timezone_set($user_loc_data ['time_zone']);
            } else {
                $user_loc_data ['city_present'] = false;
            }
            $session = $userSession->setUserDetail('user_loc_data', serialize($user_loc_data));
        } else {
            $user_loc_data = unserialize($userSession->getUserDetail('user_loc_data'));
            if (isset($user_loc_data ['time_zone']))
                date_default_timezone_set($user_loc_data ['time_zone']);
        }

        return $user_loc_data;
    }

    public static function getRealIp() {
        if (isset($_SERVER ["HTTP_CLIENT_IP"])) {
            return $_SERVER ["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER ["HTTP_X_FORWARDED_FOR"])) {
            return $_SERVER ["HTTP_X_FORWARDED_FOR"];
        } elseif (isset($_SERVER ["HTTP_X_FORWARDED"])) {
            return $_SERVER ["HTTP_X_FORWARDED"];
        } elseif (isset($_SERVER ["HTTP_FORWARDED_FOR"])) {
            return $_SERVER ["HTTP_FORWARDED_FOR"];
        } elseif (isset($_SERVER ["HTTP_FORWARDED"])) {
            return $_SERVER ["HTTP_FORWARDED"];
        } else {
            return $_SERVER ["REMOTE_ADDR"];
        }
    }

    public static function getUserLocationByIp($ip_address) {
        // api url to get user location based on IP Address.
        $userLocationData = array();
        $locationData = @geoip_record_by_name($ip_address);
        if (!empty($locationData) && !empty($locationData ['city']) && !empty($locationData ['state_code'])) {
            $userLocationData ['latitude'] = $locationData ['latitude'];
            $userLocationData ['longitude'] = $locationData ['longitude'];
            $userLocationData ['country'] = $locationData ['country_name'];
            $userLocationData ['state'] = $locationData ['region'];
            $userLocationData ['state_code'] = $locationData ['region'];
            $userLocationData ['city'] = $locationData ['city'];
        } else {
            $query_string = "?GetLocation&template=php3.txt&IpAddress=" . $ip_address;
            $file_path = GOOGLE_MAP_API . $query_string;
            $geo_data = get_meta_tags($file_path);
            $geo_data = (object) $geo_data;
            $userLocationData ['latitude'] = isset($geo_data->latitude) ? $geo_data->latitude : '';
            $userLocationData ['longitude'] = isset($geo_data->longitude) ? $geo_data->longitude : '';
            $userLocationData ['country'] = isset($geo_data->country) ? $geo_data->country : '';
            $userLocationData ['state'] = isset($geo_data->region) ? $geo_data->region : '';
            $userLocationData ['state_code'] = isset($geo_data->regioncode) ? $geo_data->regioncode : '';
            $userLocationData ['city'] = isset($geo_data->city) ? $geo_data->city : '';
        }
        return $userLocationData;
    }

    public function datetostring($date, $resrId = false) {
        if ($resrId) {
            $currentDateTime = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $resrId
                    ))->format('Y-m-d H:i');
            $time = strtotime($currentDateTime);
            $difference = $time - strtotime($date);
        } else {
            $difference = time() - strtotime($date);
        }
        $periods = array(
            "second",
            "minute",
            "hour",
            "day",
            "week",
            "month",
            "year",
            "decade"
        );
        $lengths = array(
            "60",
            "60",
            "24",
            "7",
            "4.35",
            "12",
            "10",
            "10"
        );
        $lengthCount = count($lengths);

        for ($j = 0; $difference >= $lengths[$j]; $j++) {
            $difference /= $lengths [$j];

            if ($j == $lengthCount - 1) {
                break;
            }
//            pr($lengths[$j]);
//            pr($difference);
        }

        $difference = round($difference);
        if ($difference != 1) {
            $periods [$j] .= "s";
        }
        $text = "$difference $periods[$j] ago";
        return $text;
    }

    public function getUserHistory($userId = null) {
        $dynamicUserId = true;
        $userFunctions = new UserFunctions();
        $userModel = StaticFunctions::getServiceLocator()->get(\User\Model\User::class);
        $session = StaticFunctions::getUserSession();
        $locationData = $session->getUserDetail('selected_location');
        $currentDate = $userFunctions->userCityTimeZone($locationData);
        if (!$userId) {
            $dynamicUserId = false;
            $userId = StaticFunctions::getUserSession()->getUserId();
            if (empty($userId)) {
                return;
            } else {
                $userId = array($userId);
            }
        }
        $reviewModel = StaticFunctions::getServiceLocator()->get(UserReview::class);
        $options = array(
            'columns' => array(
                new Expression("COUNT(*) AS total_reviews"),
                'user_id',
                'status'
            ),
            'where' => array(
                'user_id' => $userId,
                'status' => 1
            ),
            'group' => array(
                'user_id'
            )
        );

        $count = array();
        $count ['review'] = $reviewModel->find($options)->toArray();

        $userOrderModel = StaticFunctions::getServiceLocator()->get(UserOrder::class);
        $options = array(
            'userId' => $userId,
            'offset' => 1,
            'orderby' => 'date',
            'currentDate' => $currentDate,
            'limit' => 1,
            'flag' => 'count',
            'group' => 'groupby'
        );
        $archiveOrder = $userOrderModel->getUserArchiveOrderTotalForReview($options);
        $count ['order'] = $archiveOrder;
        $reserveModel = StaticFunctions::getServiceLocator()->get(UserReservation::class);
        $conditions = array(
            'userId' => $userId,
            'group' => 'groupby',
            'currentDate' => $currentDate
        );
        $totalReservation = $reserveModel->getTotalReservationForReview($conditions);
        $count ['reserve'] = $totalReservation;
        //$count ['reserve'][] = array('total_reservations'=>$totalReservation['total_reservation'],'user_id'=>$userId);
        $userOption = array('columns' => array('created_at'), 'where' => array('id' => $userId));
        $userJoinDate = $userModel->getUserDetail($userOption);

        if (!isset($userJoinDate['created_at']) && $userJoinDate['created_at'] == "" && $dynamicUserId) {
            $count ['joined_on'] = null;
            return $count;
        }

        $currentDateObj = new \DateTime($currentDate);
        $userJoinDateObj = new \DateTime($userJoinDate['created_at']);
        $diffrence = $currentDateObj->diff($userJoinDateObj);
        if ($diffrence->y > 0) {
            $dateString = ($diffrence->y > 1) ? $diffrence->y . " Years ago" : $diffrence->y . " Year ago";
        } elseif ($diffrence->m > 0) {
            $dateString = ($diffrence->m > 1) ? $diffrence->m . " Months ago" : $diffrence->m . " Month ago";
        } elseif ($diffrence->d > 0) {
            $dateString = ($diffrence->d > 1) ? $diffrence->d . " Days ago" : $diffrence->d . " Day ago";
        } else {
            $dateString = "Today";
        }

        $count ['joined_on'] = $dateString;
        return $count;
    }

    public function getUserHistoryBackup($userId = null) {
        $dynamicUserId = true;
        if (!$userId) {
            $dynamicUserId = false;
            $userId = StaticFunctions::getUserSession()->getUserId();
            if (empty($userId)) {
                return;
            }
        }
        $reviewModel = StaticFunctions::getServiceLocator()->get(UserReview::class);
        $options = array(
            'columns' => array(
                new Expression("COUNT(*) AS total_reviews"),
                'user_id'
            ),
            'where' => array(
                'user_id' => $userId
            ),
            'group' => array(
                'user_id'
            )
        );

        $count = array();
        $count ['review'] = $reviewModel->find($options)->toArray();
        ;
        $orderModel = StaticFunctions::getServiceLocator()->get(UserOrder::class);
        $options = array(
            'columns' => array(
                new Expression("COUNT(*) AS total_orders"),
                'user_id'
            ),
            'where' => array(
                'user_id' => $userId
            ),
            'group' => array(
                'user_id'
            )
        );
        $count ['order'] = $orderModel->find($options)->toArray();
        $reserveModel = StaticFunctions::getServiceLocator()->get(UserReservation::class);

        $options = array(
            'columns' => array(
                new Expression("COUNT(*) AS total_reservations"),
                'user_id'
            ),
            'where' => array(
                'user_id' => $userId
            ),
            'group' => array(
                'user_id'
            )
        );
        $count ['reserve'] = $reserveModel->find($options)->toArray();
        $created_at = StaticFunctions::getUserSession()->getUserDetail('created_at');
        if ($created_at == null || $dynamicUserId) {
            $count ['joined_on'] = null;
            return $count;
        }
        $count ['joined_on'] = $this->datetostring($created_at);
        return $count;
    }

    public function checkProfileImageUrl($data) {
        if (isset($data ['display_pic_url'])) {
            if ($data ['display_pic_url'] == 'noimage.jpg' || $data ['display_pic_url'] == null) {
                $data ['display_pic_url'] = 'noimage.jpg';
                $data ['display_pic_url'] = WEB_URL . 'img' . DS . $data ['display_pic_url'];
            } elseif (count(explode('/', $data ['display_pic_url'])) == 1) {
                if (!strpos($data ['display_pic_url'], 'http')) {
                    $data ['display_pic_url'] = WEB_URL . 'assets/user_images' . DS . 'profile' . DS . $data ['id'] . DS . $data ['display_pic_url'];
                }
            }
        }
        if (isset($data ['display_pic_url_normal'])) {
            if ($data ['display_pic_url_normal'] == 'noimage.jpg' || $data ['display_pic_url'] == null) {
                $data ['display_pic_url_normal'] = 'noimage.jpg';
                $data ['display_pic_url_normal'] = WEB_URL . 'img' . DS . $data ['display_pic_url_normal'];
            } elseif (count(explode('/', $data ['display_pic_url_normal'])) == 1) {
                if (!strpos($data ['display_pic_url_normal'], 'http')) {
                    $data ['display_pic_url_normal'] = WEB_URL . 'assets/user_images' . DS . 'profile' . DS . $data ['id'] . DS . $data ['display_pic_url_normal'];
                }
            }
        }
        if (isset($data ['display_pic_url_large'])) {
            if ($data ['display_pic_url_large'] == 'noimage.jpg' || $data ['display_pic_url'] == null) {
                $data ['display_pic_url_large'] = 'noimage.jpg';
                $data ['display_pic_url_large'] = WEB_URL . 'img' . DS . $data ['display_pic_url_large'];
            } elseif (count(explode('/', $data ['display_pic_url_large'])) == 1) {
                if (!strpos($data ['display_pic_url_large'], 'http')) {
                    $data ['display_pic_url_large'] = WEB_URL . 'assets/user_images' . DS . 'profile' . DS . $data ['id'] . DS . $data ['display_pic_url_large'];
                }
            }
        }
        return $data;
    }

    public function getFeedbackCount($review_id) {
        $userFeedbackModel = new UserFeedback ();
        $options = array(
            'columns' => array(
                'feedback_count' => new \Zend\Db\Sql\Expression('COUNT(*)')
            ),
            'where' => array(
                'review_id' => $review_id
            )
        );
        $count = $userFeedbackModel->find($options)->current()->getArrayCopy();
        return $count ['feedback_count'];
    }

    public function getUserHistoryForMob($userId = null) {
        $dynamicUserId = true;
        $userFunctions = StaticFunctions::getServiceLocator()->get(\User\Functions\UserFunctions::class);
        $userOrder = StaticFunctions::getServiceLocator()->get(UserOrder::class);
        $userModel = StaticFunctions::getServiceLocator()->get(\User\Model\User::class);
        $userTips = StaticFunctions::getServiceLocator()->get(\User\Model\UserTip::class);
        $session = StaticFunctions::getUserSession();
        $locationData = $session->getUserDetail('selected_location');
        $currentDate = $userFunctions->userCityTimeZone($locationData);
        if (!$userId) {
            $dynamicUserId = false;
            $userId = StaticFunctions::getUserSession()->getUserId();
            if (empty($userId) && $userId == 0) {
                return;
            } else {
                $userId = [$userId];
            }
        }
        $reviewModel = StaticFunctions::getServiceLocator()->get(UserReview::class);
        $options = array(
            'columns' => array(
                new \Zend\Db\Sql\Predicate\Expression("COUNT(*) AS total_reviews"),
                'user_id',
                'status'
            ),
            'where' => array(
                'user_id' => $userId,
                'status' => 1
            ),
            'group' => array(
                'user_id'
            )
        );
        $count = [];
        $count ['review'] = $reviewModel->find($options)->toArray();
        $options = array(
            'columns' => array(
                new \Zend\Db\Sql\Predicate\Expression("COUNT(*) AS total_tip"),
                'user_id',
                'status'
            ),
            'where' => array(
                'user_id' => $userId,
                'status' => 1
            ),
            'group' => array(
                'user_id'
            )
        );
        $count ['tip'] = $userTips->find($options)->toArray();
        if ($count ['review'] && $count ['tip']) {
            $count ['review'][0]['total_reviews'] = $count ['review'][0]['total_reviews'] + $count ['tip'][0]['total_tip'];
        } elseif ($count ['review']) {
            $count ['review'][0]['total_reviews'] = $count ['review'][0]['total_reviews'];
        } elseif ($count ['tip']) {
            $count ['review'][0]['total_reviews'] = $count ['tip'][0]['total_tip'];
        } else {
            $count ['review'][0]['total_reviews'] = 0;
        }
        $reserveModel = StaticFunctions::getServiceLocator()->get(UserReservation::class);
        $conditions = [
            'userId' => $userId,
            'group' => 'groupby',
            'currentDate' => $currentDate
        ];
        $totalReservation = $reserveModel->getTotalUserReservations($userId);
        $count ['reserve'] = $totalReservation;

        $menuBookmarkModel = StaticFunctions::getServiceLocator()->get(MenuBookmark::class);
        $options = array(
            'columns' => array(
                new \Zend\Db\Sql\Predicate\Expression("COUNT(*) AS total_tryit"),
                'user_id'
            ),
            'where' => array(
                'user_id' => $userId,
                'type' => 'ti'
            ),
            'group' => array(
                'user_id'
            )
        );
        $count ['tryit'] = $menuBookmarkModel->find($options)->toArray();
        $count ['totalOrder'] = $userOrder->getCountUserOrders($userId, 'I');

        $restaurantBookmarkModel = StaticFunctions::getServiceLocator()->get(RestaurantBookmark::class);
        $options = array(
            'columns' => array(
                new \Zend\Db\Sql\Predicate\Expression("COUNT(DISTINCT restaurant_id) AS total_beenthere"),
                'user_id'
            ),
            'where' => array(
                'user_id' => $userId,
                'type' => 'bt'
            ),
            'group' => array(
                'user_id'
            )
        );
        $count ['beenthere'] = $restaurantBookmarkModel->find($options)->toArray();


        $created_at = StaticFunctions::getUserSession()->getUserDetail('created_at');
        if ($created_at == null || $dynamicUserId) {
            $count ['joined_on'] = null;
            return $count;
        }
        $userOption = array('columns' => array('created_at'), 'where' => array('id' => $userId));
        $userJoinDate = $userModel->getUserDetail($userOption);

        $currentDateObj = new \DateTime($currentDate);
        $userJoinDateObj = new \DateTime($userJoinDate['created_at']);
        $diffrence = $currentDateObj->diff($userJoinDateObj);
        if ($diffrence->y > 0) {
            $dateString = ($diffrence->y > 1) ? $diffrence->y . " Years ago" : $diffrence->y . " Year ago";
        } elseif ($diffrence->m > 0) {
            $dateString = ($diffrence->m > 1) ? $diffrence->m . " Months ago" : $diffrence->m . " Month ago";
        } elseif ($diffrence->d > 0) {
            $dateString = ($diffrence->d > 1) ? $diffrence->d . " Days ago" : $diffrence->d . " Day ago";
        } elseif ($diffrence->h > 0) {
            $dateString = ($diffrence->h > 1) ? $diffrence->h . " Hours ago" : $diffrence->h . " Hour ago";
        } elseif ($diffrence->i > 0) {
            $dateString = $diffrence->i . " Min ago";
        } elseif ($diffrence->s > 0) {
            $dateString = $diffrence->s . " Sec ago";
        }

        $count ['joined_on'] = $dateString;
        return $count;
    }

    /*
     * Sort array as per key specification.
     * Args:
     * $array:It will contain a array value.
     * $on:This contain keyname on which sorting will be performed.
     * $order:sorting order (ASC/DESC)
     */

    function array_sort($array, $on, $order = SORT_ASC) {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }

    public function getActivityFeedType($activityTypeId = false) {
        $allActivityFeedType = false;
        if ($activityTypeId) {
            $activityFeedTypeModel = StaticFunctions::getServiceLocator()->get(ActivityFeedType::class);

            $options = array('where' => array('status' => '1', 'id' => $activityTypeId));
            $allActivityFeedType = $activityFeedTypeModel->activityFeedType($options);
            if ($allActivityFeedType) {
                return $allActivityFeedType;
            }
        }
        return $allActivityFeedType;
    }

    public function getActivityFeedTypeByName($feedType = false) {
        $allActivityFeedType = false;
        if ($feedType) {
            $activityFeedTypeModel = StaticFunctions::getServiceLocator()->get(ActivityFeedType::class);

            $options = array('where' => array('status' => '1', 'feed_type' => $feedType));
            $allActivityFeedType = $activityFeedTypeModel->activityFeedType($options);
            if ($allActivityFeedType) {
                return $allActivityFeedType;
            }
        }
        return $allActivityFeedType;
    }

    public function addActivityFeed($feed = array(), $feedType, $replacementData = array(), $otherReplacementData = array()) {
        $activityFeedType = $this->getActivityFeedType($feedType);
        $feedMessage = '';
        $otherFeedMessage = '';
        //$currentDate = StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT);
        $userSession = StaticFunctions::getUserSession();
        if (isset($feed['friend_id']) && !empty($feed['friend_id'])) {
            $user_id = $feed['friend_id'];
        } else if (isset($feed['user_id']) && !empty($feed['user_id'])) {
            $user_id = $feed['user_id'];
        } else {
            $user_id = $userSession->getUserId();
        }

        #######################
        $selectedLocation = $userSession->getUserDetail('selected_location', array());
        $cityModel = StaticFunctions::getServiceLocator()->get(\City\Model\City::class);
        $cityId = isset($selectedLocation ['city_id']) ? $selectedLocation ['city_id'] : 18848;
        $cityDetails = $cityModel->cityDetails($cityId);
        $cityDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'state_code' => $cityDetails [0] ['state_code']
        ));

        $currentDate = $cityDateTime->format('Y-m-d H:i:s');
        #######################


        if ($activityFeedType) {
            ############ Get privacy setting ############
            $userActionSettings = StaticFunctions::getServiceLocator()->get(\User\Model\UserActionSettings::class);
            $response = $userActionSettings->userActionSettings(array('where' => array('user_id' => $user_id)));
            if ($response) {
                $feedPrivacy = array(
                    '1' => 'order',
                    '4' => 'reservation',
                    '6' => 'reservation',
                    '7' => 'reservation',
                    '9' => 'reviews',
                    '10' => 'tips',
                    '11' => 'upload_photo',
                    '12' => 'bookmarks',
                    '13' => 'reviews',
                    '16' => 'bookmarks',
                    '17' => 'bookmarks',
                    '20' => 'reservation',
                    '21' => 'muncher_unlocked',
                    '22' => 'checkin',
                    '24' => 'checkin',
                    '25' => 'checkin',
                    '26' => 'checkin',
                    '34' => 'checkin',
                    '35' => 'checkin',
                    '36' => 'checkin',
                    '37' => 'muncher_unlocked',
                    '38' => 'muncher_unlocked',
                    '39' => 'muncher_unlocked',
                    '40' => 'muncher_unlocked',
                    '41' => 'muncher_unlocked',
                    '42' => 'muncher_unlocked',
                    '43' => 'muncher_unlocked',
                    '44' => 'muncher_unlocked',
                    '45' => 'muncher_unlocked',
                    '51' => 'bookmarks',
                    '52' => 'checkin',
                    '53' => 'new_register',
                    '54' => 'accept_friendship',
                    '55' => 'bookmarks',
                    '56' => 'bookmarks',
                    '57' => 'reservation',
                    '58' => 'reservation',
                    '59' => 'reservation',
                    '60' => 'reservation',
                    '61' => 'referal',
                    '62' => 'referal',
                    '63' => 'referal',
                    '64' => 'referal',
                    '65' => 'order',
                    '66' => 'referal',
                    '67' => 'referal',
                    '68' => 'new_register',
                    '69' => 'tips',
                    '70' => 'tips',
                    '71' => 'upload_photo',
                    '72' => 'upload_photo',
                    '73' => 'reservation',
                    '92' => 'snagspotconfirm'
                );
                $privacyType = $feedPrivacy[$activityFeedType[0]['id']];
                $data['privacy_status'] = isset($response[0][$privacyType]) && $response[0][$privacyType] != '' ? $response[0][$privacyType] : 1;
            } else {
                $data['privacy_status'] = 1;
            }
            #############################################
            $feedMessage = $this->replaceDefineString($replacementData, $activityFeedType[0]['feed_message']);
            $otherFeedMessage = $this->replaceDefineString($otherReplacementData, $activityFeedType[0]['feed_message_others']);

            $feed['feed_for_other'] = $otherFeedMessage;
            $feed['text'] = $feedMessage;
            if (!isset($feed['event_date_time']) || empty($feed['event_date_time'])) {
                $feed['event_date_time'] = $currentDate;
            }
            $data['feed'] = json_encode($feed);
            $data['feed_type_id'] = $activityFeedType[0]['id'];
            $data['feed_for_others'] = $otherFeedMessage;
            $data['added_date_time'] = $currentDate;
            $data['event_date_time'] = $feed['event_date_time'];
            $data['user_id'] = $user_id;
            $data['status'] = 1;
            $activityFeedModel = StaticFunctions::getServiceLocator()->get(ActivityFeed::class);
            if ($activityFeedModel->insert($data)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function replaceDefineString($replacementValue = array(), $string = '') {
        $message = '';
        if (!empty($replacementValue) && $string != "") {
            foreach ($replacementValue as $key => $value) {
                $string = str_replace('{{#' . $key . '#}}', $value, $string);
            }
            return $string;
        }
        return $message;
    }

    public function addActivityFeedForMuncher($feed = array(), $feedType, $replacementData = array(), $otherReplacementData = array(), $avtar_Message = false) {
        $activityFeedType = $this->getActivityFeedType($feedType);
        $feedMessage = '';
        $otherFeedMessage = '';
        //$currentDate = StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT);
        $userSession = StaticFunctions::getUserSession();
        if (isset($feed['friend_id']) && !empty($feed['friend_id'])) {
            $user_id = $feed['friend_id'];
        } else {
            $user_id = $userSession->getUserId();
        }

        #######################
        $selectedLocation = $userSession->getUserDetail('selected_location', array());
        $cityModel = new \Home\Model\City(); //18848
        $cityId = isset($selectedLocation ['city_id']) ? $selectedLocation ['city_id'] : 18848;
        $cityDetails = $cityModel->cityDetails($cityId);
        $cityDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'state_code' => $cityDetails [0] ['state_code']
        ));

        $currentDate = $cityDateTime->format('Y-m-d H:i:s');
        #######################


        if ($activityFeedType != '') {
            $feedMessage = $this->replaceDefineString($replacementData, $activityFeedType[0]['feed_message']);
            $otherFeedMessage = $this->replaceDefineString($otherReplacementData, $activityFeedType[0]['feed_message_others']);

            $feed['feed_for_other'] = $otherFeedMessage;
            $feed['text'] = $feedMessage . ' ' . $avtar_Message;
            if (!isset($feed['event_date_time']) || empty($feed['event_date_time'])) {
                $feed['event_date_time'] = $currentDate;
            }
            $data['feed'] = json_encode($feed);
            $data['feed_type_id'] = $activityFeedType[0]['id'];
            $data['feed_for_others'] = $otherFeedMessage;
            $data['added_date_time'] = $currentDate;
            $data['event_date_time'] = $feed['event_date_time'];
            $data['user_id'] = $user_id;
            $data['status'] = 1;
            $activityFeedModel = new ActivityFeed();
            if ($activityFeedModel->insert($data)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function getLnt($zip) {
        $result3 = [];
        $url = "http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($zip) . "&sensor=false";
        $result_string = file_get_contents($url);
        $result = json_decode($result_string, true);
        //pr($result,1);
        if ($result['status'] === 'OK' && isset($result['results'][0]['address_components'][2]['short_name']) && $result['results'][0]['address_components'][2]['short_name'] === 'New York') {
            $result1[] = $result['results'][0];
            $result2[] = $result1[0]['geometry'];
            $result3[] = $result2[0]['location'];
        }
        return $result3;
    }

    /* Global function to use curl request
     */

    public static function curlRequest($url = false, $method = false) {
        $client = new \Zend\Http\Client($url, self::$config);
        $req = $client->getRequest();
        $response = $client->send($req)->getBody();
        if (empty($response)) {
            return array();
        }
        $data = Json::decode($response, Json::TYPE_ARRAY);
        return $data;
    }

    public function validateEmail($email) {
        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
            return true;
        }

        return false;
    }

    public function isUserRegisterWithRestServer() {
        $userId = StaticFunctions::getUserSession()->getUserId();
        $restaurantServer = new \User\Model\RestaurantServer();
        $restaurantServer->user_id = $userId;
        return $restaurantServer->isUserRegister();
    }

    public function findPostOfficeS($string = false) {
        if ($string) {
            $str = substr($string, -2);
            if (preg_match("/'s/", $str)) {
                return true;
            }
        }
        return false;
    }

    public function modifyRestaurantName($restName) {
        if (!$this->findPostOfficeS($restName)) {
            return $restName . "'s";
        }
        return $restName;
    }

    public function replaceParticulerKeyValueInArray(&$userDineRestaurant) {
        if (!empty($userDineRestaurant)) {
            array_walk($userDineRestaurant, function (&$key) {
                $key["code"] = substr($key["code"], 0, 1) . $key["restaurant_id"] . "00";
            });
        }
    }

    public function writeFile($filePath, $data = NULL) {
        $fileDetails = array();

        if ($data && $filePath) {
            $newFile = fopen($filePath, 'w+');
            fwrite($newFile, $data);
            fclose($newFile);
            chmod($filePath, 0777);
            $fileDetails = array('filepath' => $filePath,);
        }
        return $fileDetails;
    }

}
