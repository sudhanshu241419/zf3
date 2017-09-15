<?php

use MCommons\StaticOptions;
use Restaurant\DashboardReportFunctions;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
//$date = '2017-01-01';
$date = '2017-02-20';
$end_date = '2017-04-24';
$restStartDate = "2015-01-01 00:00:00";
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$restObject = new DashboardReportFunctions();
$restaurants = $restObject->getRestaurantsIdsForReports();
//$tempArray = [60462];
//$tempArray = [61245, 62792, 64645,63831,59139, 60462];
$dataArray = [];
$dateObject = new DateTime();
if ($config['mongo']['enabled']) {
    $mongoClient = new \MongoClient($config['mongo']['host']);
    $mongoDb = new \MongoDB($mongoClient, $config['mongo']['database']);
    $collectionName = 'reports';
    if (!$mongoDb->$collectionName) {
        $collection = $mongoDb->createCollection($collectionName);
    } else {
        $collection = $collectionName;
    }
    $collection = $mongoDb->createCollection($collectionName);
    while (strtotime($date) <= strtotime($end_date)) {
        $date = date("Y-m-d", strtotime("+1 day", strtotime($date)));
        $restEndDate = $date . " 23:59:59";
        $startDate = $date . " 00:00:00";
        $endDate = $date . " 23:59:59";
        $restAccountModel = new Restaurant\Model\RestaurantAccounts();
        foreach ($restaurants as $key => $value) {
            $condition = array('date' => $date);
            $restData = $collection->find($condition, array('restaurant_id' => 1));
            $reportsData = iterator_to_array($restData, FALSE);
            $restIds = [];
            if (count($reportsData) > 0) {
                foreach ($reportsData as $key => $val) {
                    $restIds[] = $val['restaurant_id'];
                }
            }
            $restId = $value['id'];
            $restAccountDetail = $restAccountModel->getAccountDetail($restId);
            if (!in_array($restId, $restIds)) {
             //if (in_array($restId, $tempArray)) {
                $restName = preg_replace('/\s+/', '-', html_entity_decode($value['restaurant_name']));
                $dataArray['_id'] = new MongoId();
                $dataArray['restaurant_id'] = (int) $restId;
                $dataArray['restaurant_name'] = html_entity_decode($value['restaurant_name']);
                $dataArray['restaurant_address'] = $value['address'];
                $dataArray['restaurant_logo'] = $value['restaurant_logo_name'];
                $dataArray['restaurant_register_date'] = ($restAccountDetail) ? $restAccountDetail['created_on'] : '';
                $dataArray['date'] = $date;
                $dataArray['time'] = "23:59:59";
                $dataArray['reports']['social']['facebook'] = $restObject->getFacebookData($restId);
                $dataArray['reports']['social']['twitter'] = [];//$restObject->getTwitterData($restId);
                $dataArray['reports']['social']['fourquare'] = [];//$restObject->getFourSquareData($restId);
                $dataArray['reports']['social']['instagram'] = $restObject->getInstagramData($restId);
                $dataArray['reports']['social']['instagram']['instagram_feeds'] = array();
                $dataArray['reports']['social']['ga'] = $restObject->getAnalyticsData($restId, $restName, $startDate, $endDate, $restStartDate, $restEndDate);
                $dataArray['reports']['munchado']['emails'] = $restObject->getEmailsData($restId, $startDate, $endDate, $restStartDate, $restEndDate);
                $dataArray['reports']['munchado']['orders'] = $restObject->getOrdersData($restId, $startDate, $endDate, $restStartDate, $restEndDate);
                $dataArray['reports']['munchado']['reservations'] = [];//$restObject->getReservationsData($restId, $startDate, $endDate, $restStartDate, $restEndDate);
                $dataArray['reports']['munchado']['dineandmore'] = [];//$restObject->getDineAndMoreData($restId, $startDate, $endDate, $restStartDate, $restEndDate);
                $dataArray['reports']['munchado']['review'] = $restObject->getReviewsRatings($restId, $restStartDate, $restEndDate);
                $responce = $collection->insert($dataArray);    
                //pr($dataArray,1);
                echo "Record of restaurant id " . $restId . " and date " . $date . " inserted\n";
              }
           }
        //}
    }
}