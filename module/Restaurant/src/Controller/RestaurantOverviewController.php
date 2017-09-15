<?php

namespace Restaurant\Controller;

use MCommons\Controller\AbstractRestfulController;
use Restaurant\Model\Restaurant;
use Restaurant\RestaurantDetailsFunctions;
use Restaurant\Model\Calendar;
use Restaurant\Model\Menu;
use MCommons\StaticOptions;
use MCommons\CommonFunctions;
use Restaurant\OverviewFunctions;

class RestaurantOverviewController extends AbstractRestfulController {

    private $cityData = [];
    private $restaurantId;
    private $restCode;
    private $userId = '';

    public function get($restaurant_id = 0) {
        $overviewFunction = new OverviewFunctions;
        $overviewFunction->restaurantId = $restaurant_id;
        $overviewFunction->limit = (int) $this->getQueryParams('limit', 20);
        $overviewFunction->isMobile = $this->isMobile();
        $overviewFunction->queryParams = $this->getRequest()->getQuery()->toArray();
        $overviewFunction->page = $this->getQueryParams('page', 1);
        $this->restaurantId = $restaurant_id;
        $menuModel = new Menu ();
        $response = [];
        $session = $this->getUserSession();
        $isLoggedIn = $session->isLoggedIn();
        ########### Get Restaurant Deal ########
        $restaurantFunctions = new RestaurantDetailsFunctions();
        $response ['deals'] = [];
        $response['dine-more-register'] = false;

        $this->userId = $this->getUserSession()->user_id;
        $deals = array_values($restaurantFunctions->getDealsForRestaurant($this->restaurantId, $this->userId, $this->isMobile()));
        $response ['deals'] = $deals;
        $RestaurantServerModal = $this->getServiceLocator(\Restaurant\Model\RestaurantServer::class);
        $getExUsers = $RestaurantServerModal->findExistingUser($restaurant_id, $this->userId);
        $response['dine-more-register'] = (!empty($getExUsers)) ? true : false;

        ########## Get Restaurant Details #######
        $restaurantDetailModel = $this->getServiceLocator(Restaurant::class);
        $resDetails = $restaurantDetailModel->findRestaurant(array('where' => array('id' => $this->restaurantId)))->toArray();

        $overviewFunction->prepareRestaurantData($response, $resDetails);

        $currDateTime = StaticOptions::getRelativeCityDateTime([
                    'state_timezone' => $overviewFunction->cityData['time_zone']
        ]);

        $calendarModel = $this->getServiceLocator(\Restaurant\Model\RestaurantCalendar::class);
        $revMappedDay = array_flip(StaticOptions::$dayMapping);
        $d = $currDateTime->format('D');
        $day = $revMappedDay [$d];
        $time = $currDateTime->format('Hi');
        $isResOpen = $calendarModel->isRestaurantOpen($this->restaurantId);
        $response ['is_restaurant_open'] = $isResOpen;

        ########## Get Restaurant bookmarks #########
        $overviewFunction->getRestaurantBookmark($response);
        ############# Cuisine data ##############
        $overviewFunction->getCuisine($response);
        ########### Most Popular menu #########
        $overviewFunction->getMenu($response, $menuModel);
        ######### Get gallery for restaurant ##########        
        $overviewFunction->restCode = $resDetails['rest_code'];
        $imageGallery = $overviewFunction->getRestaurantGallery();
        ######### User Images ############
        $userUploadedImage = $overviewFunction->userRestaurantImage();
        if ($imageGallery) {
            $cover = ['image' => $response ['cover_image'], 'title' => '', 'type' => 'restaurant'];
            array_unshift($imageGallery, $cover);
            $response ['galleries'] = array_merge($imageGallery, $userUploadedImage);
        } else {
            $response ['galleries'][] = array_merge(['image' => $response ['cover_image'], 'title' => '', 'type' => 'restaurant'], $userUploadedImage);
        }
        $response ['user_image'] = WEB_URL . USER_IMAGE_UPLOAD;
        ######### Get Type Of Places ##########
        $overviewFunction->typeOfPlace($response);
        ##### Get opening and closing hours ########
        $overviewFunction->getOpeningAndClosingHours($response);

        $openingDays = [];
        foreach ($response['opening_hours'] as $key => $val) {
            $openingDays[] = $val['calendar_day'];
        }
        $openingDate = $this->getRestaurantWorkingDate($openingDays, $currDateTime);
        $response['res_opening_date'] = $openingDate->format('Y-m-d');
        ########### Story ###########
        $overviewFunction->getRestaurantStory($response);
        ########## Map Data ############
        $mapDetails ['latitude'] = $resDetails['latitude'];
        $mapDetails ['longitude'] = $resDetails['longitude'];
        $response ['map_data'] = $mapDetails;

        $response ['is_story'] = (!empty($response ['story'])) ? '1' : '0';
        $response ['is_deals'] = !empty($deals) ? '1' : '0';
        $response ['is_gallery'] = !empty($imageGallery) ? '1' : '0';
        $menucount = $menuModel->getTotalMenusCount($this->restaurantId)->toArray();
        $response['is_menu'] = ($menucount[0] > 0) ? '1' : '0';

        ########## Get Restaurant Bookmark ##########
        $foofbookmark = new \Bookmark\Model\FoodBookmark();
        $response ['tried_it'] = $foofbookmark->getRestaurantBookmarkCountOfType($this->restaurantId, 'ti')[0]['total_count'];
        $socialProofing = $this->getServiceLocator()->get("Restaurant\Controller\SocialProofingController");
        $response['activity'] = ['message' => '', 'image' => []];
        if ($isLoggedIn) {
            $socialProof = $socialProofing->get($this->restaurantId);
            $response['activity'] = [
                'message' => $socialProof['action'],
                'image' => $socialProof['image']
            ];
        }

        ############# Get User Reviews and Restaurant reviews ###########      
        $reviewDetails = $overviewFunction->getRestaurantReview();

        $resReviews = [];
        $reviews = $reviewDetails['reviews'];
        $response['total_reviews'] = $reviewDetails['total_review_count'];
        $response ['is_review'] = ($reviewDetails['total_review_count'] > 0) ? "1" : "0";
        $commonFunctions = new CommonFunctions();
        //$reviewsDetails = array_slice($commonFunctions->array_sort($reviews, 'sort_date', SORT_DESC), 0, 3);
        $reviewsDetails = $commonFunctions->array_sort($reviews, 'sort_date', SORT_DESC);
        $restaurantReview = array_splice($reviewsDetails, 0, 3);


        $isUserReview = (bool) false;
        $response['restaurant_reviews'] = $restaurantReview;
        $restaurantFunctions = new RestaurantDetailsFunctions ();
        $response ['is_user_craveit'] = ($restaurantFunctions->checkUserBookmarked($this->restaurantId, 'wl')) ? $restaurantFunctions->checkUserBookmarked($this->restaurantId, 'wl') : false;
        $response ['is_user_loveit'] = ($restaurantFunctions->checkUserBookmarked($this->restaurantId, 'lo')) ? $restaurantFunctions->checkUserBookmarked($this->restaurantId, 'lo') : false;
        $response ['is_user_beenthere'] = ($restaurantFunctions->checkUserBookmarked($this->restaurantId, 'bt')) ? $restaurantFunctions->checkUserBookmarked($this->restaurantId, 'bt') : false;
        $response['is_user_review'] = $isUserReview;
        $restaurantOhFt = $restaurantFunctions->getRestaurantDisplayTimings($this->restaurantId);
        $oh_ft = "";
        foreach ($restaurantOhFt as $key => $val) {
            if ($val['operation_hours']) {
                $oh_ft .= $val['calendar_day'] . "|" . $val['operation_hours'] . "$";
            }
        }
        $oh_ft = substr($oh_ft, 0, -1);
        $input['day'] = $day;
        $input['curr_time'] = $time;
        $input['curr_date'] = $currDateTime->format('Y-m-d H:i');
        $response['opens_at'] = \Search\SearchFunctions::getNextOpenTime($oh_ft, $input);
        if (count($response ['opening_hours']) == 1) {
            $openDate = date('M d', strtotime($response['res_opening_date']));
            $openExplode = explode('at', $response['opens_at']);
            $response['opens_at'] = $openDate . ' at' . $openExplode[1];
        }
        $this->addTags($response, $restaurant_id);
        //get polygon value and implement the logic
        $response['polygon'] = $restaurantFunctions->formatDeliveryGeo($resDetails['delivery_geo']);

        return $response;
    }

    private function getRestaurantWorkingDate($calenderDay, $date) {
        $day = $date->format('D');
        $day = substr(strtolower($day), 0, 2);
        if (!in_array($day, $calenderDay)) {
            $newDate = $date->setDate($date->format('Y'), $date->format('m'), $date->format('d') + 1);
            $this->getRestaurantWorkingDate($calenderDay, $newDate);
        }
        return $date;
    }

    private function addTags(&$response, $restaurant_id) {
        $tags = new \Home\Model\RestaurantTag();
        $response['tags_fct'] = $tags->getTags($restaurant_id);
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

}
