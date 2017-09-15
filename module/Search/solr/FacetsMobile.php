<?php

namespace Search\Solr;

use Search\Solr\SearchUrlsMobile;

class FacetsMobile 
{

    private $debug = 0;
    private static $resCuiFacetPart     = '&facet=true&facet.mincount=1&facet.field={!key=cid}cuisine_id';
    private static $resTopFacetPart     = '&facet=true&facet.mincount=1&facet.field={!key=fid}feature_id';
    private static $foodCuiFacetPart    = '&facet=true&facet.mincount=1&facet.field={!key=cid}menu_cuisines_id';
    private static $foodTopFacetPart    = '&facet=true&facet.mincount=1&facet.field={!key=fid}feature_id';
    
    private $__searchUrlMobile ;
    
    public function returnFacetData($request) 
    {
        //print_r($request);die;
        if (isset($request['DeBuG']) && $request['DeBuG'] == 404) 
        {
            $this->debug = 1;
        }
        $this->__searchUrlMobile =  \MCommons\StaticFunctions::getServiceLocator()->get(SearchUrlsMobile::class);
        $facetData       =  $this->__searchUrlMobile ->getFacetData($request); //contains vt, url, cui, top
        //print_r($facetData);die;
        $data            =  $this->prepareFacetData($facetData);
        return $data;
    }

    private function prepareFacetData($facetData) 
    {
        $resData = array();
        $this->setCuisineFacets($resData, $facetData);
        $this->setTopFacets($resData, $facetData);
        //print_r($resData);die;
        return $resData;
    }
    
    public function returnCuratedData($request) 
    {
        if (isset($request['DeBuG']) && $request['DeBuG'] == 404) 
        {
            $this->debug = 1;
        }
        $this->__searchUrlMobile =  \MCommons\StaticFunctions::getServiceLocator()->get(SearchUrlsMobile::class);
        $facetHelperData         =  $this->__searchUrlMobile->getFacetData($request); //contains vt, url, cui, top
        //pr($facetHelperData,1);
        $data                    =  $this->prepareCuratedData($facetHelperData);
        return $data;
    }
    
    private function prepareCuratedData($facetHelperData) 
    {
        $resData = array();
        $this->setCuratedData($resData, $facetHelperData);
        return $resData;
    }
    
    /* this method is already in SearchHelper class 
    private function getCurlUrlData($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data ['result'] = curl_exec($ch);
        $data ['status_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $data;
    }
    */

    private function setCuisineFacets(&$resData, $facetData) 
    {
        if ($facetData['view_type'] == 'food') 
        {
            $unescapedurl   =   $facetData['url'] . $facetData['features'] . self::$foodCuiFacetPart;
        } 
        else 
        {
            $unescapedurl   =   $facetData['url'] . $facetData['features'] . self::$resCuiFacetPart;
        }
        $url    =   preg_replace('/\s+/', '%20', $unescapedurl);
        if ($this->debug) 
        {
            $resData[] = array('id' => 'cuiurl', 'count' => 0 , 'type' => $this->__searchUrlMobile->getDebugUrl($url));
        }
        $output =   $this->__searchUrlMobile->getCurlUrlData($url);
        if ($output ['status_code'] == 200) 
        {
            $responseArr    =   json_decode($output ['data'], true);
            $tempfacetdata  =   $responseArr ['facet_counts'] ['facet_fields'];
            $rcif           =   $tempfacetdata ['cid'];
            $count          =   count($rcif);
            for ($i = 0; $i < $count; $i += 2) 
            {
                $resData[] = array('id' => $rcif [$i], 'count' => $rcif [$i + 1], 'type' => 'cuisine');
            }
        }
    }

    private function setTopFacets(&$resData, $facetData) 
    {
        if ($facetData['view_type'] == 'food') 
        {
            $unescapedurl = $facetData['url'] . $facetData['cui'] . self::$foodTopFacetPart;
        } 
        else 
        {
            $unescapedurl = $facetData['url'] . $facetData['cui'] . self::$resTopFacetPart;
        }
        $url = preg_replace('/\s+/', '%20', $unescapedurl);
        if ($this->debug) 
        {
            $resData[] = array('id' => 'featurl', 'count' => 0 , 'type' => $this->__searchUrlMobile->getDebugUrl($url));
        }
        $output = $this->__searchUrlMobile->getCurlUrlData($url);
        if ($output ['status_code'] == 200) 
        {
            $responseArr    =   json_decode($output ['data'], true);
            $tempfacetdata  =   $responseArr ['facet_counts'] ['facet_fields'];
            $fnif           =   $tempfacetdata ['fid'];
            $count          =   count($fnif);
            for ($i = 0; $i < $count; $i += 2) 
            {
                $resData[]  =   array('id'=> $fnif [$i], 'count' => $fnif [$i + 1], 'type' => 'feature');
            }
        }
    }

    private function setCuratedData(&$resData, $facetHelperData) 
    {
        //pr($facetData);
        $facetFields    =   '&facet=true&facet.field={!key=cuisine}cuisine_fct&facet.field={!key=feature}feature_fct&facet.field={!key=price}r_price_num&facet.limit=3';
        
        $facetQueries   =   '&facet.query={!key=takeout}res_takeout:1&facet.query={!key=delivery}res_delivery:1&facet.query={!key="Dine In"}res_dining:1&facet.query={!key="Special and Offers"}has_deals:1';
        
        $unescapedurl   =   $facetHelperData['url'] . $facetHelperData['cui'] . $facetHelperData['features'].$facetFields . $facetQueries;
        $url            =   preg_replace('/\s+/', '%20', $unescapedurl);
        if ($this->debug) 
        {
            $resData['url']         =   $this->__searchUrlMobile->getDebugUrl($url);
        }
        //pr($url,1);
        $output         =   $this->__searchUrlMobile->getCurlUrlData($url);
        
        if ($output ['status_code'] == 200) 
        {
            $responseArr        =   json_decode($output ['data'], true);
            $totalCount         =   $responseArr['response']['numFound'];
            $tenPercentofTotal  =   ceil($totalCount / 10);
            $tmp                =   array();
            
            foreach ($responseArr['facet_counts']['facet_queries'] as $key => $count)
            {
                if ( $key ==  'Special and Offers')
                {
                    $key_id     =   1;
                    $key_type   =   "deals";
                    $tmp        =   array('id'=>$key_id, 'value'=>ucwords($key), 'count'=>$count, 'type'=>$key_type);
                }
                else
                {
                    $key_id     =   $key;
                    $key_type   =   $key;
                    if( $key == 'Dine In')
                    {
                        $key_id     =   "dinein";
                        $key_type   =   "dinein";
                    }
                    $curatedData[]  =   array('id'=>$key_id, 'value'=>ucwords($key), 'count'=>$count, 'type'=>$key_type);
                }
            }
            
            $facetFieldsArr =   $responseArr['facet_counts']['facet_fields'];
            
            $cuisinesArr    =   $facetFieldsArr['cuisine'];
            $cuisineCounts  =   count($cuisinesArr);
            for ($i = 0; $i < $cuisineCounts; $i += 2) 
            {
                if( $cuisinesArr[$i + 1] >= $tenPercentofTotal){
                    $curatedData[] = array('id'=> $cuisinesArr[$i], 'value' => ucwords($cuisinesArr[$i]), 'count' => $cuisinesArr[$i + 1], 'type' => 'cuisine');
                }
            }
            
            $featuresArr    =   $facetFieldsArr['feature'];
            $featureCounts  =   count($featuresArr);
            for ($i = 0; $i < $featureCounts; $i += 2) 
            {
                if( $featuresArr[$i + 1] >= $tenPercentofTotal){
                    $curatedData[] = array('id'=> $featuresArr[$i], 'value' => ucwords($featuresArr[$i]), 'count' => $featuresArr[$i + 1], 'type' => 'feature');
                }
            }
            
            $priceArr   =   $facetFieldsArr['price'];
            if(!empty($priceArr))
            {
                $curatedData[]  =   array('id'=> $priceArr[0], 'value' => ($priceArr[0]==0 ? 'All': str_repeat('$', $priceArr[0])), 'count' => $priceArr[1], 'type' => 'price');
            }
            
            $curatedData[] = $tmp;
            $curatedData[] = array('id' => 1, 'value' => "Open Now", 'count' => 0, 'type' => "orn");
            
            $resData['total_count']     =   $totalCount;
            $resData['data']    =   $curatedData;
        }
    }
}

?>
