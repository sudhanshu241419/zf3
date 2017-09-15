<?php

namespace Search\Solr;

use Search\Solr\SearchUrlsMobile;
/**
 * For discover/find_by_mood, restaurant count use getRestaurantCounts()
 *
 * For pick an area use getLandmarksData()
 *
 * @author Dhirendra Singh Yadav
 */
class PickAnAreaMobile 
{

    private $debug = 0;
    private $_solr_url = '';
    private $solr_select_url = '';

    public function __construct() {
        
        $this->__searchUrlMobile    =  \MCommons\StaticFunctions::getServiceLocator()->get(SearchUrlsMobile::class);  
        $this->_solr_url            =   $this->__searchUrlMobile->solr_url . 'hbr/hbsearch?';
        $this->solr_select_url      =   $this->__searchUrlMobile->solr_url . 'hbr/select?';
    }

    // used for counting number of restaurants in a city. discover landing page
    public function getRestaurantCounts($params) 
    {
        if (isset($params['DeBuG']) && $params['DeBuG'] == 404) {
            $this->debug = 1;
        }
        $response       =   array();
        $global_fq      =   'fq=(r_closed:0+AND+r_inactive:0)&wt=json&rows=0';
        $city_fq        =   '&fq=city_id:' .$params['city_id'];
        $unescapedurl   =   $this->solr_select_url . $global_fq . $city_fq;
        $url            =   preg_replace('/\s+/', '%20', $unescapedurl);
        //pr($url,1);
        $output         =   $this->__searchUrlMobile->getCurlUrlData($url);
        if ($output ['status_code'] == 200) 
        {
            $dataArr                = json_decode($output ['data'], true);
            $response ['city_id']   = $params['city_id'];
            $response ['city_name'] = $params['city_name'];
            $response ['count']     = $dataArr ['response'] ['numFound'];
        } 
        else 
        {
            $response ['city_id']   = $params['city_id'];
            $response ['city_name'] = $params['city_name'];
            $response ['count']     = 0;
        }
        if ($this->debug) {
            $response ['url'] = SearchHelpers::getDebugUrl($url);
        }
        return $response;
    }

    //used for showing landmarks list for select a location dropdown
    public function getLandmarksData($req) 
    {
        if (isset($req['DeBuG']) && $req['DeBuG'] == 404) 
        {
            $this->debug = 1;
        }
        $response       =   array();
        $baseUrl        =   $this->_solr_url;
        $groupPart      =   'rows=1000&fl=res_neighborhood,borough,nbd_lat,nbd_long&group=true&group.field=res_neighborhood&sort=borough+asc,res_neighborhood+asc&';
        $fqPart         =   $this->__searchUrlMobile->getPickAnAreaFq($req);
        $unescapedurl   =   $baseUrl . $groupPart . $fqPart;
        $url            =   preg_replace('/\s+/', '%20', $unescapedurl);  //echo $url; die;
        $output         =   $this->__searchUrlMobile->getCurlUrlData($url);
        
        if ($output ['status_code'] == 200) 
        {
            $dataArr        =   json_decode($output ['data'], true);
            $numCount       =   $dataArr ['grouped'] ['res_neighborhood'] ['matches'];
            if ($numCount > 0) 
            {
                $landmarks  =   $dataArr ['grouped'] ['res_neighborhood'] ['groups'];
                foreach ($landmarks as $landmark) 
                {
                    if ($landmark ['groupValue'] != '') 
                    {
                        $response ['landmarks'] [] = array(
                            'landmark' => $landmark ['doclist'] ['docs'] [0] ['res_neighborhood'],
                            'borough'  => $landmark ['doclist'] ['docs'] [0] ['borough'],
                            'latitude' => $landmark ['doclist'] ['docs'] [0] ['nbd_lat'],
                            'longitude'=> $landmark ['doclist'] ['docs'] [0] ['nbd_long']
                        );
                    }
                }
            }
        }

        if ($this->debug) 
        {
            $response ['params']        = $req;
            $response ['landmarks'] []  = array('url' => $this->__searchUrlMobile->getDebugUrl($url), 'landmark' => '', 'latitude' => '', 'longitude' => '');
        }

        return $response;
    }

    public function getTopNeighborhoods($req) 
    {
        if (isset($req['DeBuG']) && $req['DeBuG'] == 404) 
        {
            $this->debug = 1;
        }
        $response           = array();
        $response['params'] = $req;
        $baseUrl            = $this->_solr_url;
        $facetPart          = 'rows=0&facet=true&facet.field=res_neighborhood&facet.limit=100&facet.mincount=5';
        $fqPart             = '&fq=city_id:' . $req['city_id'];
        $unescapedurl       = $baseUrl . $facetPart . $fqPart;
        $url                = preg_replace('/\s+/', '%20', $unescapedurl);
        $output             = $this->__searchUrlMobile->getCurlUrlData($url);
        if ($output ['status_code'] == 200) 
        {
            $dataArr        =   json_decode($output ['data'], true);
            $landmarks      =   $dataArr ['facet_counts'] ['facet_fields'] ['res_neighborhood'];
            $count          =   count($landmarks);
            $nbds           =   array();
            for ($i = 0; $i < $count; $i += 2) 
            {
                $nbds []    =   $landmarks[$i];
            }
            asort($nbds);
            $response ['landmarks'] = array_values($nbds);
        }
        if ($this->debug) 
        {
            $response['landmarks'] [] = array('url' => $this->__searchUrlMobile->getDebugUrl($url));
        }
        return $response;
    }

}
