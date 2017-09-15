<?php

namespace Search\Solr;

use Search\Solr\SearchUrlsMobile;



class MainSearchMobile {

    private $debug = 0;
    public  $searchUrlsMobile; //SearchUrls class instance
    
    /**
     * Get search results for given query parameters for mobile api.
     * This assumes that $params is already cleaned and free from any errors.
     * @param array $params search parameters
     * @return array data for food or restaurant view
     */
    public function returnMobileSearchData($clearInput) {
        if ($clearInput['DeBuG'] == 404) {
            $this->debug = 1;
        }
        $this->searchUrlsMobile = \MCommons\StaticFunctions::getServiceLocator()->get(SearchUrlsMobile::class);
        try {
            $unescapedurl   =   $this->getSearchUrl($clearInput);
            $resData        =   $this->prepareReturnData($unescapedurl, $clearInput);
            return $resData;
        } catch (\Exception $e) {
            return array('code'=>$e->getCode(),'message'=>$e->getMessage());
        }
    }

    private function getSearchUrl($clearInput) {
        $url = '';
        switch ($clearInput['tab']) {
            case 'delivery':
                $url = $this->searchUrlsMobile->getDeliverUrl($clearInput);
                break;
            case 'takeout':
                $url = $this->searchUrlsMobile->getTakeoutUrl($clearInput);
                break;
            case 'reservation':
                $url = $this->searchUrlsMobile->getReservationUrl($clearInput);
                break;
            case 'dinein':
                $url = $this->searchUrlsMobile->getDineinUrl($clearInput);
                break;
            case 'all':
                $url = $this->searchUrlsMobile->getDiscoverUrl($clearInput);
                break;
        }
        return $url;
    }

    private function prepareReturnData($unescapedurl, $request) 
    {
        $unescapedurl .=    $this->searchUrlsMobile->getUrlDealsFacetPart($request['tab']);
        $retData       =    array();
        $unescapedurl .=    $this->searchUrlsMobile->getHighlightFl(array($request['q'], $request['cuisines'], $request['features']), $request['view_type']);
        
        $url = preg_replace('/\s+/', '%20', $unescapedurl);
        if ($this->debug) 
        {
            $retData['url']         =   $this->searchUrlsMobile->getDebugUrl($url);
        }
        //pr($url,1);
        $output = $this->searchUrlsMobile->getCurlUrlData($url);   ### Send and execute @ Solr
        
        if ($output['status_code'] == 200) 
        {
            $responseArr            =   json_decode($output['data'], true);             
            $retData['count']       =   $responseArr['response']['numFound'];
            $retData['data']        =   $responseArr['response']['docs'];
            $retData['has_deals']   =   $responseArr['facet_counts']['facet_queries']['has_deals'];

            if (isset($responseArr['highlighting'])) 
            {
                $retData['highlight'] = $responseArr['highlighting'];
            }
        } 
        else 
        {
            $responseArr        =   isset($output['result']) ? json_decode($output['result'], true) : array('error' => 'server error');
            $retData['error']   =   $responseArr['error'];
            $retData['count']   =   0;
            $retData['data']    =   array();
        }
        return $retData;
    }
}

?>