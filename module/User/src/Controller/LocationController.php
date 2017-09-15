<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use City\Model\City;
use User\Model\User;
use MCommons\CommonFunctions;
use User\Model\UserSetting;
use Zend\Json\Json;

class LocationController extends AbstractRestfulController {

    public static $config = array(
        'adapter' => 'Zend\Http\Client\Adapter\Curl',
        'curloptions' => array(
            CURLOPT_FOLLOWLOCATION => true
        )
    );

    private function cityValid($city_id, $status = true) {
        $cityModel = new City ();
        $cityData = $cityModel->getCity(array(
                    'columns' => array(
                        'id',
                        'latitude',
                        'longitude',
                        'city_name',
                        'state_id'
                    ),
                    'where' => array(
                        'id' => $city_id,
                        'status' => $status
                    )
                ))->current();

        return !empty($cityData) ? $cityData : false;
    }

    public function create($data) {   
       
        $city_id = empty($data ['city_id']) ? '' : $data ['city_id'];
        
        if (!$city_id) {
            throw new \Exception("Invalid city id", 400);
        }
        
        $city = $this->getServiceLocator(City::class);
        $citydetails = $city->cityAndStateDetails($city_id);
       
        if ($citydetails) {
            if (empty($citydetails['locality'])) {
                throw "Invalid Locality for city_id " . $data ['city_id'];
            }           
            
            $session = $this->getUserSession();
            $session->setUserDetail(array(
                'selected_location' => $citydetails
            ));
            $saved = $session->Save();
            if (!$saved) {
                throw new \Exception("Not Saved");
            }
            $response ['selected_location'] = $citydetails;
            return $response;
        } else {
            throw new \Exception("Something went wrong.", 400);
        }
    }

    public function getList() {
        try {
            $response = array();
            $data = array();
            $data ['notification_setting'] = array();
            $session = $this->getUserSession();
            $user_id = $session->getUserId();

            $data ['show_location_popup'] = false;
            $data ['is_active_session'] = false;
            $data ['user_loc_data'] = '';
            $data ['login_popup'] = 0;
            $userloginModel = new User ();

            if ($user_id) {
                $userDetail = $userloginModel->getUserDetail(array(
                    'where' => array(
                        'id' => $user_id
                    )
                        ));
                $data ['user_id'] = $userDetail ['id'];
                $data ['user_name'] = $userDetail ['user_name'];
                $data ['first_name'] = $userDetail ['first_name'];
                $data ['last_name'] = $userDetail ['last_name'];
                $data ['email'] = $userDetail ['email'];
                $data ['phone'] = $userDetail ['phone'];
                $data ['accept_toc'] = $userDetail ['accept_toc'];
                $data ['newsletter_subscribtion'] = $userDetail ['newsletter_subscribtion'];
                $data ['display_pic_url'] = $this->checkImage($userDetail ['display_pic_url']);
                $data ['display_pic_url_large'] = $this->checkImage($userDetail ['display_pic_url_large']);
                $data ['display_pic_url_normal'] = $this->checkImage($userDetail ['display_pic_url_normal']);

                $registration_time = strtotime($userDetail ['created_at']);
                $current_time = time();
                $registration_duration = $current_time - $registration_time;
                $data ['reg_duration_in_sec'] = $registration_duration;
                $UserSettingModel = new UserSetting ();
                $getUserSettings = $UserSettingModel->findUserSettings($user_id);

                $data = $getUserSettings;
                $data ['is_active_session'] = true;
            }
            $userDetail = $session->getUserDetail();

            if (isset($userDetail ['city_id'])) {
                $data ['user_loc_data'] = array(
                    "city" => $userDetail ['city_name'],
                    "city_id" => $userDetail ['city_id'],
                    "state" => isset($userDetail ['state']) ? $userDetail ['state'] : '',
                    "state_code" => isset($userDetail ['state_code']) ? $userDetail ['state_code'] : "",
                    "time_zone" => isset($userDetail ['time_zone']) ? $userDetail ['time_zone'] : ""
                );
            } else {

                $geoLocation = CommonFunctions::getUserLocationData();
                if (empty($geoLocation ['city_present'])) {
                    $data ['show_location_popup'] = true;
                }

                $data ['user_loc_data'] = $geoLocation;
            }
            // print_r($data); exit;
            $data ['social_share'] = $this->getLikesFollowerData();
            return $data;
        } catch (\Exception $e) {
            \MUtility\MunchLogger::writeLog($e, 1, 'Something Went Wrong On Location Api');
            throw new \Exception($e->getMessage(), 400);
        }
    }

    private function getLikesFollowerData() {
        $response = array(
            'facebook' => 0,
            'google' => 0,
            'twitter' => 0
        );
        $response ['facebook'] = $this->facebookLikesData();
        $response ['twitter'] = $this->twitterLikesData();
        $response ['google'] = $this->googleLikesData();
        $memcache = \Zend\Cache\StorageFactory::adapterFactory('memcached', array(
                    'namespace' => 'munchados',
                    'servers' => array(
                        array(
                            'localhost',
                            11211
                        )
                    ),
                    'ttl' => (60 * 60)
                ));

        if ($memcache->hasItem('social_share')) {
            return $memcache->getItem('social_share');
        }
        // $data['social_share'] = $response;
        $memcache->setItem('social_share', $response);
        return $response;
    }

    private function facebookLikesData() {
        $uri = "http://graph.facebook.com/munchado";
        $client = new \Zend\Http\Client($uri, self::$config);
        $req = $client->getRequest();
        $data = Json::decode($client->send($req)->getBody(), Json::TYPE_ARRAY);
        if (!empty($data)) {
            $facebook = isset($data ['likes']) ? $data ['likes'] : 0;
        } else {
            $facebook = 0;
        }
        return $facebook;
    }

    private function twitterLikesData() {
        $constants = $this->getGlobalConfig();
        $notweets = 1;
        $connection = StaticOptions::getConnectionWithTwitterAccessToken($constants ['twitter'] ['key'], $constants ['twitter'] ['secret'], $accesstoken = "", $accesstokensecret = "");
        $data = $connection->get("https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=" . $constants ['twitter'] ['handle'] . "&count=" . $notweets);

        if (!empty($data [0]->user->followers_count)) {
            $twitter = $data [0]->user->followers_count;
        } else {
            $twitter = 0;
        }
        return $twitter;
    }

    private function googleLikesData() {
        $googleLikes = 0;
        $url = 'http://munchado.com';
        $uri = 'https://plusone.google.com/_/+1/fastbutton?url=' . urlencode($url);
        $client = new \Zend\Http\Client($uri, self::$config);
        $req = $client->getRequest();
        $data = $client->send($req)->getBody();
        preg_match('/window\.__SSR = {c: ([\d]+)/', $data, $matches);
        if (isset($matches [0]))
            $googleLikes = (int) str_replace('window.__SSR = {c: ', '', $matches [0]);

        return $googleLikes;
    }

    private function checkImage($image) {
        if ($image == '' || $image == NULL) {
            return WEB_IMG_URL . 'img/no_img.jpg';
        } else {

            if (preg_match('/http/', strtolower($image))) {
                return $image;
            } else {

                if (getimagesize(USER_UPLOADED_IMAGE_PROFILE . $image) !== false) {
                    return USER_UPLOADED_IMAGE_PROFILE . $image;
                } else {
                    return WEB_IMG_URL . 'img/no_img.jpg';
                }
            }
        }
    }

}
