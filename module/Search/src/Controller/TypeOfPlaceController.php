<?php

namespace Search\Controller;


use MCommons\Controller\AbstractRestfulController;
use Zend\EventManager\EventManagerInterface;

use Search\Solr\SearchUrlsMobile;
use Search\Common\Utility;


class TypeOfPlaceController extends AbstractRestfulController 
{
    private $__response =   array();
    private $__obj_search_url_mobile = '';
    
    public function __construct() 
    {
        //$this->__obj_search_url_mobile   =   $this->getServiceLocator(SearchUrlsMobile::class); 
        //$this->__obj_search_url_mobile   = \MCommons\StaticFunctions::getServiceLocator()->get(SearchUrlsMobile::class);
    }
    
    public function getList() 
    {  
        $this->__obj_search_url_mobile   =   $this->getServiceLocator(SearchUrlsMobile::class); 
        $rawInput               =   $this->getRequest()->getQuery()->toArray();
        $session                =   $this->getUserSession();
        $user_loc               =   $session->getUserDetail('selected_location', array());
        $rawInput['city_id']    =   isset($user_loc['city_id']) ? intval($user_loc ['city_id']) : 18848;
        $rawInput['city_name']  =   isset($user_loc['city_name']) ? $user_loc['city_name'] : 'New York';
        
        $rawInput['reqtype']    =   isset($rawInput['reqtype']) ? $rawInput['reqtype'] : 'def';
        
        $cleanInput =   Utility::cleanMobileSearchParams($rawInput);
        $debug      =   $cleanInput['DeBuG'] ;
        if($debug == '404')
        {
            $this->debug            = true;
            $this->starttime_milli  = microtime(true);
        }
        
        if (empty($cleanInput['reqtype'])) 
        {
            throw new \Exception("Parameter reqtype is invalid or missing.", 400);
        }
        
        switch ($cleanInput['reqtype']) {
            case 'search' :
                $this->getSearchData($cleanInput);
                break;
            default :
                $this->getDefaultCaseTOP($cleanInput);
                break;
        }
        
        if ($this->debug) {
            $this->__response['time_in_millis']         =  microtime(true) - $this->starttime_milli;
            $this->__response['original_request_params']=  $this->getRequest()->getQuery()->toArray();
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

    public function getConfig() {
        $event = $this->getEvent();
        $config = $event->getApplication()->getServiceManager()->get('config');
        return $config;
    }
    
    private function getDefaultCaseTOP($input)
    {
        $facetFields        =   '&facet=true&facet.field={!key=feature}feature_fct&facet.limit=300&facet.mincount=1';
        
        $facetHelperData    =   $this->__obj_search_url_mobile->getFacetData($input); //contains vt, url, cui, top
        $unescapedurl       =   $facetHelperData['url'] . $facetHelperData['cui'] . $facetHelperData['features'].$facetFields ;
        $url                =   preg_replace('/\s+/', '%20', $unescapedurl);
        $this->getMeResult($url);
    }
    
    private function getSearchData($input)
    {
        $facetFields        =   '&facet=true&facet.field={!key=feature}feature_fct&facet.limit=300&facet.mincount=1';
        $facetHelperData    =   $this->__obj_search_url_mobile->getFacetData($input); //contains vt, url, cui, top
        $unescapedurl       =   $facetHelperData['url'] . $facetHelperData['cui'] . $facetHelperData['features'].$facetFields ;
        $url                =   preg_replace('/\s+/', '%20', $unescapedurl);
        $this->getMeResult($url);
    }
   
    private function getFeaturesFromDB()
    {
        $objFeatureModel    =   $this->getServiceLocator(\Restaurant\Model\Feature::class);
        $Data               =   $objFeatureModel->getFeatures();
        $feature_db = array() ; //'Features','Ambience','Type of Place'
        if ( count( $Data ) )
        {
            foreach( $Data as $key => $DataValue)
            {
                $x  =   strtolower($DataValue['features']);
                $id =   $DataValue['id'];
                if( $DataValue['feature_type'] == 'Restaurant Features' )
                {
                    $feature_db['features'][$id] =  $x;
                }
                else if( $DataValue['feature_type'] == 'Ambience' )
                {
                    $feature_db['ambience'][$id] = $x ;
                }
                else if( $DataValue['feature_type'] == 'Type of Place' )
                {
                    $feature_db['type_of_place'][$id] = $x ;
                }
                else
                {
                    $feature_db['others'][$id] = $x ; 
                }
            }
        }
        //pr($feature_db,1);
        return $feature_db;
    }
     
    private function getMeResult($url)
    {
        $output = $this->__obj_search_url_mobile->getCurlUrlData($url);
        if ($output ['status_code'] == 200) 
        {
            $feature_db         =   $this->getFeaturesFromDB();
            //pr($feature_db,1);
            $curatedData        =   array();
            $responseArr        =   json_decode($output ['data'], true);
            $totalCount         =   $responseArr['response']['numFound'];
            $tenPercentofTotal  =   ceil($totalCount / 10);
            $facetFieldsArr     =   $responseArr['facet_counts']['facet_fields'];
            
            $featuresArr        =   $facetFieldsArr['feature'];
            $featureCounts      =   count($featuresArr);
            for ($i = 0; $i < $featureCounts; $i += 2) 
            {   
                if( in_array( $featuresArr[$i],$feature_db['features'],true ) )
                {
                    $tmp_id = array_search($featuresArr[$i],$feature_db['features']);
                    $curatedData['features'][]  =   array('id'=> "$tmp_id", 'features' => $featuresArr[$i],'features_key' => $featuresArr[$i],'count' => (int)$featuresArr[$i + 1]);
                }
                elseif( in_array( $featuresArr[$i],$feature_db['ambience'],true ) )
                {
                    $tmp_id = array_search($featuresArr[$i],$feature_db['ambience']);
                    $curatedData['ambience'][]  =   array('id'=> "$tmp_id", 'features' => $featuresArr[$i],'features_key' => $featuresArr[$i],'count' =>(int) $featuresArr[$i + 1]);
                }
                elseif( in_array( $featuresArr[$i],$feature_db['type_of_place'],true ) )
                {
                    $tmp_id = array_search($featuresArr[$i],$feature_db['type_of_place']);
                    $curatedData['type_of_place'][]  =   array('id'=> "$tmp_id", 'features' => $featuresArr[$i],'features_key' => $featuresArr[$i],'count' => (int)$featuresArr[$i + 1]);
                }   
            }
            //pr($curatedData,1);
            $this->__response = $curatedData;
        }
    }
       
}