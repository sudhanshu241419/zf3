<?php
/**
 * Description of MobileSearch
 *
 * @author bravvura
 */
namespace Search\Controller;

use Search\Common\Utility;
use MCommons\Controller\AbstractRestfulController;
use Search\Solr\MainSearchMobile;
use Search\Solr\FacetsMobile;
use Search\Solr\FriendsMobile;

class MobSearchController extends AbstractRestfulController {
    private $debug      =   false;
    const FOOD          =   'food';
    const SEO_PAGE_SIZE =   100;  
    const RESTAURANT    =   'restaurant';
    
    private $__response =   array();
    
    public function __construct() {
        
    }

    public function getList() { 
        
        $rawInput   = $this->getRequest()->getQuery()->toArray();
        
        $cleanInput = Utility::cleanMobileSearchParams($rawInput);
        
        $serverData = $this->getRequest()->getServer()->toArray();
        $routhPath  = $serverData['REDIRECT_URL'];
        $tmp = explode("/", $routhPath);
        
        if( count($tmp) == 4)
        {
            if( $tmp[3] == 'userdeals')
            {
                $cleanInput['reqtype'] = 'userdeals';
            }
        }
        
        $debug  =   $cleanInput['DeBuG'] ;
        if($debug == '404')
        {
            $this->debug            = true;
            $this->starttime_milli  = microtime(true);
        }
        
        if (empty($cleanInput['reqtype'])) 
        {
            throw new \Exception("Parameter reqtype is invalid or missing.", 400);
        }
        
        $this->searchByRequestType($cleanInput);
        
        if ($this->debug) 
        {    
            $this->__response['time_in_millis']         =  microtime(true) - $this->starttime_milli;
            $this->__response['original_request_params']=  $this->getRequest()->getQuery()->toArray();
            $this->__response['params_used']            =  $cleanInput;   
        }
        
        return $this->__response;
    }

    public function get($id) {
        
    }

    public function create($data) {
        
    }

    public function update($id, $data) {
        
    }

    public function delete($id) {
        
    }

    /*public function setEventManager(EventManagerInterface $events) {
        parent::setEventManager($events);
        $events->attach('dispatch', array($this, 'getConfig'), 10);
    }*/

    public function getConfig() 
    {
        $event  = $this->getEvent();
        $config = $event->getApplication()->getServiceManager()->get('config');
        return $config;
    }
    
    public function getCityRelatedResponseInformation($clearInput)
    {
        $this->__response['image_base_path'] =   IMAGE_PATH;
        $city                                        =   $this->getServiceLocator(\City\Model\City::class);
        $this->__response['curr_time']       =   $city->getCityCurrentDateTime($clearInput['city_id']);
        $this->__response['search_time']     =   !empty($clearInput['stime'])?$clearInput['stime']:$response ['curr_time'];
    }
    
    private function searchByRequestType($clearInput) 
    {
        $session    =   $this->getUserSession();
        $user_loc   =   $session->getUserDetail('selected_location', array());
        $clearInput['city_id']    = isset($user_loc['city_id']) ? intval($user_loc ['city_id']) : 18848;
        $clearInput['city_name']  = isset($user_loc['city_name']) ? $user_loc['city_name'] : 'New York';
        
        $response = array();
        switch ($clearInput['reqtype']) {
            case 'search' :
                $this->getMainSearchData($clearInput);
                break;
            case 'curated' :
                $this->getCuratedCounters($clearInput);
                break;
            case 'suggest' :
                $this->getAutoCompleteData($clearInput);
                break;
            case 'landmarks' :
                $this->getLandmarksData($clearInput);
                break;
            case 'totalrescount' :
                $this->getRestaurantCountByCity($clearInput);
                break;
            case 'counters' :
                $this->getCuisineAndFeatureCounters($clearInput);
                break;
            case 'friendsearch' :
                $clearInput['user_id']  = $session->getUserId();
                $clearInput['rows']     = 8;
                $this->getFriendsListAsSuggestion($clearInput);
                break;
            case 'userdeals' :
                $this->getUserDealsData($clearInput);
                break;
            default :
                throw new \Exception("Invalid Request", 400);
        }
        
        $this->getCityRelatedResponseInformation($clearInput);
        
        try
        {
            $objMongo = $this->getServiceLocator(\MUtility\MongoLogger::class);
            $objMongo->setCollection('search_inputs');
            $objMongo->insert($clearInput);
        }
        catch (Exception $e)
        {

        }
    }
    
    private function getMainSearchData($clearInput) 
    {
        $search     =   new MainSearchMobile();
        $response   =   $search->returnMobileSearchData($clearInput);
        
        $objUtility =   $this->getServiceLocator(Utility::class);
        
        if ($clearInput ['view_type'] == self::RESTAURANT) 
        {
            $objUtility->updateRestaurantDataMobile($response, $clearInput);
        } 
        elseif($clearInput ['view_type'] == self::FOOD) 
        {
            $objUtility->updateFoodDataMobile($response, $clearInput);
        }
        $this->__response = $response ;        
    }
    
    private function getCuratedCounters($clearInput) 
    {
        $search   = new FacetsMobile();
        $response = $search->returnCuratedData($clearInput);
        $this->__response = $response ;   
    }
    
    private function getAutoCompleteData($clearInput)
    {
        $ac                 =   new \Search\Solr\AutoCompleteMobile();
        $autocompleteArr    =   $ac->getAutocomplete($clearInput);
        $this->__response   =   isset($autocompleteArr ['data']) ? $autocompleteArr ['data'] : array();
    }
    
    private function getLandmarksData($clearInput)
    {
        $pick_an_area       = $this->getServiceLocator(\Search\Solr\PickAnAreaMobile::class);
        $data               = $pick_an_area->getLandmarksData($clearInput);
        $this->__response   = isset($data ['landmarks']) ? $data ['landmarks'] : array();
    }
    
    private function getRestaurantCountByCity($clearInput)
    {
        $pick_an_area       = $this->getServiceLocator(\Search\Solr\PickAnAreaMobile::class);
        $data               = $pick_an_area->getRestaurantCounts($clearInput);
        $this->__response   = isset($data) ? $data : array();
    }
    
    private function getCuisineAndFeatureCounters($clearInput) 
    {
        $search = new FacetsMobile();
        $this->__response = $search->returnFacetData($clearInput);
    }
    
    private function getFriendsListAsSuggestion($clearInput)
    {
        $fm = new FriendsMobile();
        $this->__response = $fm->getFriendSuggestions($clearInput);
    }
    
    private function getUserDealsData($clearInput) 
    {
        $response   =   array();
        $userid     =   $this->getUserSession()->getUserId(); //11
        if ($userid > 0)
        {
            $clearInput['uid']  =   $userid;
            $objUDM             =   new \Search\Solr\UserDealsMobile();
            $response           =   $objUDM->getUserDeals($clearInput);
        }
        $this->__response       =   $response;
    }
}
