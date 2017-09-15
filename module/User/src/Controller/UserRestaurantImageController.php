<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use Zend\Http\PhpEnvironment\Request;
use Restaurant\Model\Restaurant;
use User\Model\UserCheckin;
use User\Model\UserReview;
use User\Model\UserRestaurantImage;
use User\Functions\UserFunctions;

class UserRestaurantImageController extends AbstractRestfulController {

    public function create($data) {
        try { 
            $userrestaurantimagemodel = $this->getServiceLocator(UserRestaurantImage::class);
            $session = $this->getUserSession();
            $isLoggedIn = $session->isLoggedIn();
            $addImageResponse = false;
            $allImages = [];
            
            if (!$isLoggedIn) {
                throw new \Exception("Your are not valid user", 400);
            }
            if (!isset($data ['restaurant_id']) || empty($data ['restaurant_id'])) {
                throw new \Exception("Restaurant detail is not valid", 400);
            }
            $data['image_type'] = (isset($data['image_type']) && !empty($data['image_type'])) ? $data['image_type'] : "g";

            $userFunctions = $this->getServiceLocator(UserFunctions::class);
            $locationData = $session->getUserDetail('selected_location', []);
            $currentDate = $userFunctions->userCityTimeZone($locationData);

            $userrestaurantimagemodel->restaurant_id = $data ['restaurant_id'];
            $userrestaurantimagemodel->user_id = $session->getUserId();
            $userrestaurantimagemodel->caption = $data['caption'] ? $data['caption'] : '';
            $request = new Request ();
            $files = $request->getFiles();

            $restaurantModel = $this->getServiceLocator(Restaurant::class);
            $restaurantDetailOption = ['columns' => ['rest_code', 'restaurant_name'], 'where' => ['id' => $data ['restaurant_id']]];
            $restDetail = $restaurantModel->findRestaurant($restaurantDetailOption)[0];
            $data1['image_url'] = '';
            $gotPointsResPerImage = 0;
            $imageForFeed = [];
            if (!empty($files)) {
                $response = \MCommons\StaticFunctions::uploadUserImages($files, APP_PUBLIC_PATH, USER_IMAGE_UPLOAD . strtolower($restDetail['rest_code']) . DS . 'gallery' . DS);
                if (!empty($response)) {
                    $userrestaurantimagemodel->created_on = $currentDate;
                    $userrestaurantimagemodel->updated_on = $currentDate;
                    $userrestaurantimagemodel->status = 0;
                    $userrestaurantimagemodel->image_type = isset($data['image_type']) ? $data['image_type'] : "g";
                    $userrestaurantimagemodel->image_status = 0;
                    $imageCounter = 0;
                    foreach ($response as $key => $val) {
                        $data1['user_id'] = $session->getUserId();
                        $data1['restaurant_id'] = $data['restaurant_id'];
                        $data1['created_on'] = $currentDate;
                        $data1['updated_on'] = $currentDate;
                        $data1['status'] = 2;
                        $data1['image_type'] = isset($data['image_type']) ? $data['image_type'] : "g";
                        $data1['image_status'] = 0;
                        $data1['image_url'] = $val['path'];
                        $allImages [$imageCounter] = $val['path'];
                        $data1['caption'] = $data['caption'] ? $data['caption'] : '';
                        $arr_img_path = explode('/', $val['path']);
                        $length = count($arr_img_path);
                        $data1['image'] = $arr_img_path [$length - 1]; // 
                        $data1['source'] = '1';
                        $userFunctions->userId = $session->getUserId();
                        $userFunctions->sweepstakesDuplicatImage($data['restaurant_id'], $currentDate, 'restaurant');
                        $data1['sweepstakes_status_winner'] = 0;
                        $imageForFeed[] = "user_images" . DS . strtolower($restDetail['rest_code']) . DS . 'gallery' . DS . $data1['image'];
                        $addImageResponse = $userrestaurantimagemodel->createRestaurantImage($data1);
                        $imageCounter += 1;
                    }
                    $gotPointsResPerImage = 10 * (int) $imageCounter;
                }
            }

            #Add activity feed data# 
            if ($data['image_type'] == "g") {
                $this->addFeed($userrestaurantimagemodel->user_id, $restDetail, $imageForFeed, $data['restaurant_id'], $data['caption']);
            }
        } catch (\Exception $excp) {
            return $this->sendError(array(
                        'error' => $excp->getMessage()
                            ), $excp->getCode());
        }
        ######### Dine and more give point to upload photo ##############
        $userFunctions->imageUploadCount = $imageCounter;
        $userFunctions->userId = $session->getUserId();
        $userFunctions->restaurantId = $data ['restaurant_id'];
        $userFunctions->restaurant_name = $restDetail['restaurant_name'];
        $userFunctions->typeKey = "image_id";
        $userFunctions->typeValue = "";
        $totalPoints = 0;
        $earnPoint = 0;
        $dinemore = 0;
        $balancePoints = 0;
        if ($data['image_type'] == "g") {
            $awardsPoints = $userFunctions->dineAndMoreAwards('awardsuploadpic');
            $gotPointsResPerImage = 0;
            $earnPoint = 0;
            if (isset($awardsPoints['points'])) {
                $gotPointsResPerImage = (int) $awardsPoints['points'];
                $earnPoint = (int) $awardsPoints['points'];
                $dinemore = 1;
            } else {
                $gotPointsResPerImage = 10;
                $earnPoint = 10;
                $dinemore = 0;
            }
            #################################################################     
            $userPoint = $this->getServiceLocator(\User\Model\UserPoint::class);
            $userTotalPoint = $userPoint->countUserPoints($session->getUserId());
            $redeemPoints = $userTotalPoint[0]['redeemed_points'];
            $balancePoints = $userTotalPoint[0]['points'] - $redeemPoints;
            $totalPoints = intval($balancePoints) + (int) $gotPointsResPerImage;
        }
        if ($addImageResponse) {
            $userModel = $this->getServiceLocator(\User\Model\User::class);
            $userDetailOption = ['columns' => ['first_name', 'last_name', 'email'], 'where' => ['id' => $session->getUserId()]];
            $userDetail = $userModel->getUser($userDetailOption);
            $cleverTap = [
                "gallery_id" => $userrestaurantimagemodel->id,
                "user_id" => $session->getUserId(),
                "name" => $userDetail['first_name'],
                "email" => $userDetail['email'],
                "identity" => $userDetail['email'],
                "restaurant_name" => $restDetail['restaurant_name'],
                "restaurant_id" => $data ['restaurant_id'],
                "eventname" => "upload_pic",
                "earned_points" => $earnPoint,
                "is_register" => "yes",
                "gallery_date" => $currentDate,
                'image_count' => $gotPointsResPerImage,
                "event" => 1
            ];

            $userFunctions->createQueue($cleverTap, 'clevertap');
            return ['result' => true, 'user_points' => $balancePoints, 'total_points' => $totalPoints, 'point' => $earnPoint, 'dinemore' => $dinemore, 'image_path' => $data1['image_url'], 'image' => $allImages];
        } else {
            return ['result' => false, 'user_points' => $balancePoints, 'total_points' => $totalPoints, 'point' => $earnPoint, 'dinemore' => $dinemore, 'image_path' => $data1['image_url'], 'image' => $allImages];
        }
    }

    public function getlist() {
        $session = $this->getUserSession();
        if ($session) {
            $login = $session->isLoggedIn();
            if (!$login) {
                throw new \Exception('No Active Login Found.');
            }
        } else {
            throw new \Exception('No Active Login Found.');
        }

        $user_id = $session->getUserId();
        $friendId = $this->getQueryParams('friendid', false);
        if ($friendId) {
            $user_id = $friendId;
        }
        //restaurant images gallery
        $restaurantImage = $this->getServiceLocator(UserRestaurantImage::class);
        $options = array('columns' => array('id', 'restaurant_id', 'image_url', 'image_type', 'message' => 'caption', 'created_at' => 'created_on'), 'where' => array('user_id' => $user_id,));
        $urimages = $restaurantImage->find($options);

        if ($urimages) {
            $restaurantImage = $urimages->toArray();
            foreach ($restaurantImage as $key => $val) {
                $restaurantImage[$key]['image_type'] = ($val['image_type'] == 'g') ? 'gallery' : 'bill';
                $restaurantImage[$key]['message'] = (isset($val['message']) && $val['message'] != null) ? $val['message'] : '';
            }
        } else {
            $restaurantImage = [];
        }

        //checkin images
        $joins = [];
        $userCheckin = $this->getServiceLocator(UserCheckin::class);
        $joins[] = array(
            'name' => array(
                'ci' => 'checkin_images'
            ),
            'on' => 'ci.checkin_id=user_checkin.id',
            'columns' => array('id' => new \Zend\Db\Sql\Expression('ci.id'), 'image_url' => 'image_path'),
            'type' => 'inner'
        );
        $options1 = array(
            'columns' => array('restaurant_id', 'message', 'created_at'),
            'where' => array('user_id' => $user_id),
            'joins' => $joins
        );
        $ucimages = $userCheckin->find($options1);
        if ($ucimages) {
            $userCheckinImages = $ucimages->toArray();
            foreach ($userCheckinImages as $key => $val) {
                $userCheckinImages[$key]['image_type'] = 'checkin';
                $userCheckinImages[$key]['message'] = (isset($val['message']) && $val['message'] != null) ? $val['message'] : '';
            }
        } else {
            $userCheckinImages = [];
        }

        //Review menu images
        $joins = [];
        $userReview = $this->getServiceLocator(UserReview::class);

        $joins[] = array(
            'name' => array(
                'umi' => 'user_menu_reviews'
            ),
            'on' => 'umi.user_review_id=user_reviews.id',
            'columns' => array('id' => new \Zend\Db\Sql\Expression('umi.id'), 'image_name'),
            'type' => 'inner'
        );
        $joins[] = array(
            'name' => array(
                'r' => 'restaurants'
            ),
            'on' => 'r.id=user_reviews.restaurant_id',
            'columns' => array('rest_code'),
            'type' => 'inner'
        );
        $options2 = array(
            'columns' => array('restaurant_id', 'message' => 'review_desc', 'created_at' => 'created_on'),
            'where' => array('user_id' => $user_id),
            'joins' => $joins
        );
        $urimages = $userReview->find($options2);
        if ($urimages) {
            $userReviewImage = $urimages->toArray();
            foreach ($userReviewImage as $key => $val) {
                if (isset($val['image_name']) && !empty($val['image_name'])) {
                    $userReviewImage[$key]['image_url'] = WEB_URL . USER_IMAGE_UPLOAD . strtolower($val['rest_code']) . DS . 'reviews' . DS . $val ['image_name'];
                    $userReviewImage[$key]['image_type'] = 'menu_review';
                    $userReviewImage[$key]['message'] = (isset($val['message']) && $val['message'] != null) ? $val['message'] : '';
                } else {
                    unset($userReviewImage[$key]);
                }
                if (isset($userReviewImage[$key]))
                    unset($userReviewImage[$key]['rest_code'], $userReviewImage[$key]['image_name']);
            }
        }else {
            $userReviewImage = [];
        }

        //Review Restaurant image
        $joins = [];
        $joins[] = array(
            'name' => array(
                'uri' => 'user_review_images'
            ),
            'on' => 'uri.user_review_id=user_reviews.id',
            'columns' => array('id' => new \Zend\Db\Sql\Expression('uri.id'), 'image_url'),
            'type' => 'inner'
        );

        $options3 = array(
            'columns' => array('restaurant_id', 'message' => 'review_desc', 'created_at' => 'created_on'),
            'where' => array('user_id' => $user_id),
            'joins' => $joins
        );
        $urrImages = $userReview->find($options3);
        if ($urrImages) {
            $userRestaurantReviewImage = $urrImages->toArray();
            foreach ($userRestaurantReviewImage as $key => $val) {
                $userRestaurantReviewImage[$key]['image_type'] = 'user_review';
                $userRestaurantReviewImage[$key]['message'] = (isset($val['message']) && $val['message'] != null) ? $val['message'] : '';
            }
        } else {
            $userRestaurantReviewImage = [];
        }

        $allImages = array_merge($restaurantImage, $userCheckinImages, $userReviewImage, $userRestaurantReviewImage);
        $commonFunctions = new \MCommons\CommonFunctions();
        $response = $commonFunctions->array_sort($allImages, 'created_at', SORT_DESC);
        $totalImage = count($response);
        $limit = $this->getQueryParams('limit', SHOW_PER_PAGE);
        $page = $this->getQueryParams('page', 1);
        $offset = 0;
        if ($page > 0) {
            $page = ($page < 1) ? 1 : $page;
            $offset = ($page - 1) * ($limit);
        }
        $photoResponses = array_slice($response, $offset, $limit);
        $finalResponse['user_photos'] = $photoResponses;
        $finalResponse['total_image'] = $totalImage;
        $userModel = $this->getServiceLocator(\User\Model\User::class);
        $userDetailOption = ['columns' => ['points'], 'where' => ['id' => $user_id]];
        $userDetail = $userModel->getUser($userDetailOption);
        $finalResponse['total_points'] = intval($userDetail['points']);
        return $finalResponse;
    }

    public function addFeed($userId, $restDetail, $imageForFeed, $restaurantId, $caption) {
        $userModel = $this->getServiceLocator(\User\Model\User::class);
        $userDetailOption = ['columns' => ['first_name', 'last_name', 'points'], 'where' => ['id' => $userId]];
        $userDetail = $userModel->getUser($userDetailOption);
        $userName = (isset($userDetail['last_name']) && !empty($userDetail['last_name'])) ? $userDetail['first_name'] . " " . $userDetail['last_name'] : $userDetail['first_name'];
        $commonFunctiion = $this->getServiceLocator(\MCommons\CommonFunctions::class);
        $replacementData = ['restaurant_name' => $restDetail['restaurant_name']];
        $otherReplacementData = [];
        $feed = [
            'restaurant_id' => $restaurantId,
            'restaurant_name' => $restDetail['restaurant_name'],
            'user_name' => ucfirst($userName),
            'img' => $imageForFeed,
            'caption' => $caption
        ];
        //bug id 38763
        //$commonFunctiion->addActivityFeed($feed, 11, $replacementData, $otherReplacementData);
    }

}
