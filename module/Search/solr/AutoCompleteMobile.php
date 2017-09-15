<?php

namespace Search\Solr;

use \Search\Solr\SearchUrlsMobile;

class AutoCompleteMobile 
{

    private $debug = 0;
    private $req = array();
    public $searchUrls;
    private $ovt = 'restaurant';
    public static $res_facet_map = array(
        'cuisine_fct' => 'cuisine',
        'res_fct' => 'restaurant',
        'menu_fct' => 'food',
        'feature_fct' => 'feature'
    );
    public static $food_facet_map = array(
        'menu_cuisine_fct' => 'cuisine',
        'menu_fct' => 'food',
        'feature_fct' => 'feature'
    );

    /*
     * facet fields for various cases
     * deliver = {cuisine, popular food, popular trends}
     * takeout = {cuisine, popular food, popular trends}
     * dinein = {Cuisines,Popular trends,Restaurants,Type of place}
     * reservation = {Cuisines,Popular trends,Restaurants,Type of place}
     * discover = {cuisine, popular food, popular trends}
     */
    public static $res_facet_fields = array(
        'discover_ff' => '&facet.field=cuisine_fct',
        'deliver_ff' => '&facet.field=cuisine_fct',
        'takeout_ff' => '&facet.field=cuisine_fct',
        'dinein_ff' => '&facet.field=cuisine_fct',
        'reservation_ff' => '&facet.field=cuisine_fct'
    );
    public static $food_facet_fields = array(
        'discover_ff' => '&facet.field=menu_cuisine_fct&facet.field=menu_fct',
        'deliver_ff' => '&facet.field=menu_cuisine_fct&facet.field=menu_fct',
        'takeout_ff' => '&facet.field=menu_cuisine_fct&facet.field=menu_fct',
        'dinein_ff' => '&facet.field=menu_cuisine_fct&facet.field=feature_fct&facet.field=menu_fct',
        'reservation_ff' => '&facet.field=menu_cuisine_fct&facet.field=feature_fct&facet.field=menu_fct'
    );

    public function getAutocomplete($req) 
    {
        if ($req['view_type'] == 'food') {
            $this->ovt = 'food';
        }
        if ($req['DeBuG'] == 404) {
            $this->debug = 1;
            $this->req = $req;
        }
                    
        $this->searchUrls   =   \MCommons\StaticFunctions::getServiceLocator()->get(SearchUrlsMobile::class);
        //print_r($this->searchUrls);die;
        $this->searchUrls->setClassVariables($req);
        
        $retData = array();
        switch ($req['tab']) {
            case 'all':
                $retData = $this->getDiscoverAc($req);
                break;
            case 'delivery':
                $retData = $this->getDeliverAc($req);
                break;
            case 'takeout':
                $retData = $this->getTakeoutAc($req);
                break;
            case 'dinein':
                $retData = $this->getDineinAc($req);
                break;
            case 'reservation':
                $retData = $this->getReservationAc($req);
                break;
        }

        return $retData;
    }

    private function getDeliverAc($req) 
    {
        //print_r($req);die;
        $baseUrls = $this->searchUrls->getAcDeliverUrls($req);
        if ($this->ovt == 'food') {
            $cui_url = $baseUrls[0] . self::$food_facet_fields['deliver_ff'] . '&facet.prefix=' . $req ['term'];
            $top_url = ''; //no top for delivery
            $name_url = $baseUrls[2];
            return $this->prepareFoodFacetData($cui_url, $top_url, $name_url,$req['sec']);
        } else {
            $cui_url = $baseUrls[0] . self::$res_facet_fields['deliver_ff'] . '&facet.prefix=' . $req ['term'];
            $top_url = ''; //no top for delivery
            $name_url = $baseUrls[2];
            return $this->prepareResFacetData($cui_url, $top_url, $name_url,$req['sec']);
        }
    }

    private function getTakeoutAc($req) {
        //print_r($req);die;
        $baseUrls = $this->searchUrls->getAcTakeoutUrls($req);
        if ($this->ovt == 'food') {
            $cui_url = $baseUrls[0] . self::$food_facet_fields['takeout_ff'] . '&facet.prefix=' . $req ['term'];
            $top_url = ''; //no top for takeout
            $name_url = $baseUrls[2];
            return $this->prepareFoodFacetData($cui_url, $top_url, $name_url,$req['sec']);
        } else {
            $cui_url = $baseUrls[0] . self::$res_facet_fields['takeout_ff'] . '&facet.prefix=' . $req ['term'];
            $top_url = ''; //no top for takeout
            $name_url = $baseUrls[2];
            return $this->prepareResFacetData($cui_url, $top_url, $name_url,$req['sec']);
        }
    }

    private function getDineinAc($req) {
        //print_r($req);die;
        $baseUrls = $this->searchUrls->getAcDineinUrls($req);
        if ($this->ovt == 'food') {
            $cui_url = $baseUrls[0] . self::$food_facet_fields['dinein_ff'] . '&facet.prefix=' . $req ['term'];
            $top_url = $baseUrls[1] . '&facet.field=feature_fct&facet.prefix=' . $req ['term'];
            $name_url = $baseUrls[2];
            return $this->prepareFoodFacetData($cui_url, $top_url, $name_url,$req['sec']);
        } else {
            $cui_url = $baseUrls[0] . self::$res_facet_fields['dinein_ff'] . '&facet.prefix=' . $req ['term'];
            $top_url = $baseUrls[1] . '&facet.field=feature_fct&facet.prefix=' . $req ['term'];
            $name_url = $baseUrls[2];
            return $this->prepareResFacetData($cui_url, $top_url, $name_url,$req['sec']);
        }
    }

    private function getReservationAc($req) {
        //print_r($req);die;
        $baseUrls = $this->searchUrls->getAcReservationUrls($req);
        if ($this->ovt == 'food') {
            $cui_url = $baseUrls[0] . self::$food_facet_fields['reservation_ff'] . '&facet.prefix=' . $req ['term'];
            $top_url = $baseUrls[1] . '&facet.field=feature_fct&facet.prefix=' . $req ['term'];
            $name_url = $baseUrls[2];
            return $this->prepareFoodFacetData($cui_url, $top_url, $name_url,$req['sec']);
        } else {
            $cui_url = $baseUrls[0] . self::$res_facet_fields['reservation_ff'] . '&facet.prefix=' . $req ['term'];
            $top_url = $baseUrls[1] . '&facet.field=feature_fct&facet.prefix=' . $req ['term'];
            $name_url = $baseUrls[2];
            return $this->prepareResFacetData($cui_url, $top_url, $name_url,$req['sec']);
        }
    }

    private function getDiscoverAc($req) {
        //print_r($req);die;
        $baseUrls = $this->searchUrls->getAcDiscoverUrls($req);
        if ($this->ovt == 'food') {
            $cui_url = $baseUrls[0] . self::$food_facet_fields['discover_ff'] . '&facet.prefix=' . $req ['term'];
            $top_url = $baseUrls[1] . '&facet.field=feature_fct&facet.prefix=' . $req ['term'];
            $name_url = $baseUrls[2];
            return $this->prepareFoodFacetData($cui_url, $top_url, $name_url,$req['sec']);
        } else {
            $cui_url = $baseUrls[0] . self::$res_facet_fields['discover_ff'] . '&facet.prefix=' . $req ['term'];
            $top_url = $baseUrls[1] . '&facet.field=feature_fct&facet.prefix=' . $req ['term'];
            $name_url = $baseUrls[2];
            return $this->prepareResFacetData($cui_url, $top_url, $name_url,$req['sec']);
        }
    }

    private function prepareResFacetData($cui_url, $top_url = '', $name_url = '',$sec='') {
        /*
         * 4th param has been added to identify that this request is from "checkins". sec=chkin. so in this case we 
         * donot need to process cuisine and top search urls. [Athar]
         */
        //echo $sec_url;die;
        $retData = array();
        
        if (empty($sec))
        {
            $cui_url = preg_replace('/\s+/', '%20', $cui_url);
            
            $urlResponse = $this->searchUrls->getCurlUrlData($cui_url);
            if ($urlResponse['status_code'] == 200) 
            {
                $responseArr    =   json_decode($urlResponse['data'], true);
                $usableData     =   $responseArr['facet_counts']['facet_fields'];
                foreach ($usableData as $facetFieldName => $data) 
                {
                    $count      =   min(array(10, count($data) - 1)); //atmost 3 suggestions per field
                    for ($i = 0; $i < $count; $i+=2) 
                    {
                        $retData['data'][] = array(
                                'item' => $data[$i],
                                'type' => self::$res_facet_map[$facetFieldName]
                                //'count' => $data[$i + 1]
                        );
                    }
                }
                if ($this->debug == 1) 
                {
                    $retData['data'][]  =   array('item' => $this->req, 'type' => 'req');
                    $retData['data'][]  =   array('item' => $this->searchUrls->getDebugUrl($cui_url), 'type' => 'cui_url');
                }
            } 
            else 
            {
                $retData['status_code']['error'] = $urlResponse['status_code'];
            }

            //top autosuggestion
            if (count($retData) < 5 && $top_url != '') 
            {
                $top_url        =   preg_replace('/\s+/', '%20', $top_url);
                $urlResponse    =   $this->searchUrls->getCurlUrlData($top_url);
                if ($urlResponse['status_code'] == 200) 
                {
                    $responseArr    =   json_decode($urlResponse['data'], true);
                    $usableData     =   $responseArr['facet_counts']['facet_fields'];
                    foreach ($usableData as $facetFieldName => $data) 
                    {
                        $count      = min(array(10, count($data) - 1)); //atmost 3 suggestions per field
                        for ($i = 0; $i < $count; $i+=2) 
                        {
                            $retData['data'][] = array(
                                    'item' => $data[$i],
                                    'type' => self::$res_facet_map[$facetFieldName]
                            );
                        }
                    }
                    if ($this->debug == 1) //debug
                    {
                        $retData['data'][] = array('item' => $this->searchUrls->getDebugUrl($top_url), 'type' => 'top_url');
                    }
                }
            }
        }
        //res_name arbit auto suggest if count is less than 5
        if (count($retData) < 5 && $name_url != '') 
        {
            //show atmost 5 restaurant name in autosuggestion
            //$name_url = $name_url . '&start=0&rows=5&fl=res_name&facet=off&qf=res_eng';
            $fl = "res_name";
            if (!empty($sec))
            {
                $fl = "res_name,res_street,res_address,res_code,latlong,res_primary_image,distance:mul(geodist(),0.621371),res_id";
            }
            
            $name_url       =   $name_url . '&start=0&rows=5&fl='.$fl.'&facet=off&qf=res_eng';
            $name_url       =   preg_replace('/\s+/', '%20', $name_url);
            
            $urlResponse    =   $this->searchUrls->getCurlUrlData($name_url);
            if ($urlResponse['status_code'] == 200) 
            {
                $responseArr    =   json_decode($urlResponse['data'], true);
                $usableData     =   $responseArr['response']['docs'];
                if (empty($sec))
                {
                    $d = array();
                    foreach ($usableData as $data) 
                    {
                        $d[strtolower($data['res_name'])]   =   'restaurant';
                    }
                    foreach ($d as $k => $v) 
                    {
                        $retData['data'][]  =   array('item' => $k, 'type' => $v);
                    }
                }
                else
                {
                    foreach ($usableData as $data)
                    {
                        $tmp = array();
                        $tmp = array('item' => ucfirst($data['res_name']), 
                                     'type' => 'restaurant',
                                     'res_code' => $data['res_code'],
                                     'res_street' => $data['res_address'],
                                     'primary_image' => $data['res_primary_image'],
                                     'latlong' => $data['latlong'],
                                     'res_id' => $data['res_id'],
                                     'distance' => number_format($data['distance'],2),
                                    );
                        $retData['data'][] = $tmp;
                    }
                }
                
            }
            //debugurl
            if ($this->debug == 1) {//debug
                $retData['data'][] = array('item' => $this->searchUrls->getDebugUrl($name_url), 'type' => 'name_url');
            }
        }
        if ($this->debug == 1) {//for debugging
            $retData['data'][] = array('item' => $this->req, 'type' => 'req');
        }
        return $retData;
    }

    private function prepareFoodFacetData($cui_url, $top_url = '', $name_url = '') 
    {

        //cuisines and menu autosuggest
        $cui_url = preg_replace('/\s+/', '%20', $cui_url);
        $urlResponse = $this->searchUrls->sgetCurlUrlData($cui_url);
        if ($urlResponse['status_code'] == 200) {
            $responseArr = json_decode($urlResponse['data'], true);
            $usableData = $responseArr['facet_counts']['facet_fields'];
            $retData = array();
            foreach ($usableData as $facetFieldName => $group) {
                $count = min(array(6, count($group) - 1)); //atmost 5 suggestions per field
                for ($i = 0; $i < $count; $i+=2) {
                    $retData['data'][] = array(
                        'item' => $group[$i],
                        'type' => self::$food_facet_map[$facetFieldName]
                            //'count' => $data[$i + 1]
                    );
                }
            }
            //debug
            if ($this->debug == 1) {
                $retData['data'][] = array('item' => $this->searchUrls->getDebugUrl($cui_url), 'type' => 'cui_url');
            }
        } else {
            $retData['status_code']['error'] = $urlResponse['status_code'];
        }

        //top autosuggestion
        if (count($retData) < 5 && $top_url != '') {
            $top_url = preg_replace('/\s+/', '%20', $top_url);
            $urlResponse = $this->searchUrls->getCurlUrlData($top_url);
            if ($urlResponse['status_code'] == 200) {
                $responseArr = json_decode($urlResponse['data'], true);
                $usableData = $responseArr['facet_counts']['facet_fields'];
                foreach ($usableData as $facetFieldName => $data) {
                    $count = min(array(10, count($data) - 1)); //atmost 2 suggestions per field
                    for ($i = 0; $i < $count; $i+=2) {
                        $retData['data'][] = array(
                            'item' => $data[$i],
                            'type' => self::$food_facet_map[$facetFieldName]
                        );
                    }
                }
                if ($this->debug == 1) {
                    $retData['data'][] = array('item' => $this->searchUrls->getDebugUrl($cui_url), 'type' => 'cui_url');
                }
            }
        }

        //res_name autosuggestion
        if (count($retData) < 1 && $name_url != '') {
            //atmost one restaurant name in autosuggestion
            $name_url = $name_url . '&start=0&rows=1&fl=score&group=true&group.field=menu_fct&facet=off&qf=menu_eng';
            $name_url = preg_replace('/\s+/', '%20', $name_url);
            //echo $sec_url;die;
            $urlResponse = $this->searchUrls->getCurlUrlData($name_url);
            if ($urlResponse['status_code'] == 200) {
                $responseArr = json_decode($urlResponse['data'], true);
                $usableData = $responseArr['grouped']['menu_fct']['groups'];
                foreach ($usableData as $group) {
                    $retData['data'][] = array('item' => $group['groupValue'], 'type' => 'food');
                }
            }
            if ($this->debug == 1) {
                $retData['data'][] = array('item' => $this->searchUrls->getDebugUrl($cui_url), 'type' => '');
            }
        }

        if ($this->debug == 1) {
            $retData['data'][] = array('item' => $this->req, 'type' => 'req');
        }
        return $retData;
    }

}

?>