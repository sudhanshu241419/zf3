<?php
use MCommons\StaticOptions;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');

$city = new \Home\Model\City();
$currentDateTime = $city->getCityCurrentDateTime(18848);
$currentDate = date('Y-m-d', strtotime($currentDateTime));

$varRestaurantModel=new Restaurant\Model\Restaurant();
$select = new Select();
$where = New Where();
$select->columns(array('id','restaurant_name'));
$select->join ( array (
				'dm' => 'restaurant_servers' 
		), 'dm.restaurant_id =  restaurants.id', array (), $select::JOIN_INNER);
$select->from($varRestaurantModel->getDbTable()->getTableName());
$where->like('updated_on', '%' . $currentDate . '%');
$select->where($where);
$select->group('restaurants.id');
$allUpdatedRestaurant=$varRestaurantModel->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
if(count($allUpdatedRestaurant)> 0 && !empty($allUpdatedRestaurant)){
    $tags = new \Restaurant\Model\Tags();
    $tagsDetails = $tags->getTagDetailByName("dine-more");
    $restaurantTags=new \Home\Model\RestaurantTag();
    $restaurantServer=new \Restaurant\Model\RestaurantServer();
    $varUserModel=new \User\Model\User();
    $salesManago = new Salesmanago();
    foreach($allUpdatedRestaurant as $key=>$res){
        $isResTags=$restaurantTags->hasTags($res['id']);
        if($isResTags){
            $select = new Select();
            $where = New Where();
            $select->columns(array('user_id'));
            $select->from($restaurantServer->getDbTable()->getTableName());
            $where->equalTo('restaurant_id', $res['id']);
            $select->where($where);
            $allJoinedUser = $restaurantServer->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
            if(count($allJoinedUser) && !empty($allJoinedUser)){
                foreach($allJoinedUser as $key=>$serverUser){
                    $serverUserId=$serverUser['user_id'];
                    $getUserEmail=$varUserModel->getUserEmail($serverUserId);
                    $webUrl = PROTOCOL . $config['constants']['web_url'];
                    $urlRestName = str_replace(" ", "-", strtolower(trim($res['restaurant_name'])));
                    $restaurantUrl =$webUrl . "/loginRedirect?url=restaurants/" . $urlRestName . "/" . $res['id'];
                    $salesManagoData = array('web_url'=>$webUrl."/",'restaurant_url'=>$restaurantUrl,'restaurant_name'=>$res['restaurant_name'],'email' => $getUserEmail['email'],'changeRestaurantName'=>true);
                    $salesManago->earnPointOnSalesmanago($salesManagoData);
                    
                }
            }
        }
    }
   
}