<?php

use MCommons\StaticOptions;
use Restaurant\Model\DealsCoupons;
use Restaurant\Model\SolrIndexing;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$solrIndexingModel = new SolrIndexing();
$dealModel = new DealsCoupons ();
$dealModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$options = array(
    'columns' => array(
        'id',
        'restaurant_id',
        'end_date'
    ),
    'where' => array(
        'status != ?' => 0
    )
);
$deals = $dealModel->find($options)->toArray();
if ($deals) {
    $ids = array();
    $restuarntIds = array();    
    foreach ($deals as $deal) {
        if ($deal ['end_date'] != null && $deal ['restaurant_id']) {
            $cityDateTime = StaticOptions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $deal ['restaurant_id']
                            ), StaticOptions::getDateTime()->format(StaticOptions::MYSQL_DATE_FORMAT), 'Y-m-d H:i:s');
            $endDate = StaticOptions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $deal ['restaurant_id']
                            ), $deal['end_date'], 'Y-m-d H:i:s');
            if ($cityDateTime > $endDate) {
                $ids [] = $deal ['id'];
                $restuarntIds[] = $deal ['restaurant_id'];
            }
        }
    }
    if (count($ids)) {
        $data = array(
            'status' => 0
        );
         $dealModel->updateDealsCoupons($data, $ids);
         $restaurentIds = array_unique($restuarntIds);
         
        foreach ($restaurentIds as $resId) {           
            $opt = array('columns' => array('id'),'where' => array('restaurant_id' => $resId));
            $solrIndexingModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
            $restaurantIndexDetail = $solrIndexingModel->find($opt)->toArray();
            if(isset($restaurantIndexDetail[0]['id'])){
                $d = array('restaurant_id'=>$resId,'is_indexed' => 0);
                $solrIndexingModel->id=$restaurantIndexDetail[0]['id'];
                $solrIndexingModel->updateSolrIndexing($d);
            }else{
                $d = array('restaurant_id'=>$resId,'is_indexed' => 0);
                $solrIndexingModel->updateSolrIndexing($d);
            }
        
        }
    }
}
