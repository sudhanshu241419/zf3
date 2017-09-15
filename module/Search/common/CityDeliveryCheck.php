<?php

namespace Search\Common;

use MCommons\StaticFunctions;
use Search\Solr\SearchHelpers;

class CityDeliveryCheck {
    
    private static $map_city_func = array(
        '18848' => 'polygonCheck',
        'default' => 'defaultCheck',
    );

    /**
     * Checks if the restaurant can delivery to address specified by $lat,$lng
     * @param int $res_id
     * @param string $lat
     * @param string $lng
     */
    public static function canDeliver($res_id, $lat,$lng) 
    {
        
        $city = StaticFunctions::getServiceLocator()->get(\Restaurant\Model\Restaurant::class);
        $data = $city->getRestaurantDeliveryData($res_id);
        
        //$data['city_id']    =   18848 ; // this has been hardcoded becoz Restaurant Module has not been created. As soon as the restaurant module is created we will uncomment the above two line and will remove the this line. This completely a temporary work.[ athar ]
        
        if(!empty(self::$map_city_func[$data['city_id']])){
            $func_name = self::$map_city_func[$data['city_id']];
        } else {
            $func_name = self::$map_city_func['default'];
        }
        //pr($func_name,true);
        return self::$func_name($data, $lat, $lng);
    }

    // called for city_id 18848
    private static function polygonCheck($data, $lat, $lng) 
    {
        $res_id         =   $data['id'];
        $solr_host      =   StaticOptions::getSolrUrl();
        $solr_select    =   $solr_host . 'hbr/select?rows=0&fq=res_id:"'.$res_id.'"';
        $solr_select   .=   '&fq=delivery_geo:"Intersects('.trim($lng) . '+'. trim($lat).')"';
        
        $output = SearchHelpers::getCurlUrlData($solr_select);
        if($output['status_code'] == 200)
        {
            $dataArr    =   json_decode($output ['data'], true);
            if($dataArr ['response'] ['numFound'] == 1){
                return true;
            }
        }
        return false;
    }
    
    private static function defaultCheck($data, $lat, $lng)
    {
        return false;
    }

}
