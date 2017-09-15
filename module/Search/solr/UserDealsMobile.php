<?php

namespace Search\Solr;

use MCommons\StaticFunctions;
use Search\Solr\SearchUrlsMobile;

class UserDealsMobile 
{

    public function getUserDeals($origReq) 
    {
        $response = ['status' => 'OK'];
        if (!$this->valid($origReq)) 
        {
            return $this->getInvalidResponse('invalid uid');
        }
        $objUserDeals       = StaticFunctions::getServiceLocator()->get(\User\Model\UserDeals::class);
        $resIds             = $objUserDeals->getUserDealsResIds($origReq['uid']);
        $response['data']   = $this->getResData($resIds);
        $response['count']  = count($resIds);
        return $response;
    }

    private function valid($req) 
    {
        if ($req['uid'] == 0) {
            return false;
        }
        return true;
    }

    private function getResData($resIds) 
    {
        if (count($resIds) <= 0) 
        {
            return [];
        }
        $objSearchUrlsMobile = StaticFunctions::getServiceLocator()->get(SearchUrlsMobile::class);
        
        $url                 = StaticFunctions::getSolrUrl();
        $url                .= 'hbr/hbsearch?pt=40.7127,-74.0059';
        $url                .= '&fq=res_id:("' . implode('"+OR+"', $resIds) . '")';
        
        $result         =   [];
        $output         =   $objSearchUrlsMobile->getCurlUrlData($url);
        
        if ($output['status_code'] == 200) 
        {
            $responseArr    =   json_decode($output['data'], true);
            $result         =   $responseArr['response']['docs'];
            $this->updateResUserDeals($result);
        }
        return $result;
    }

    private function getInvalidResponse($errMsg) 
    {
        return ['status' => 'fail', 'data' => [], 'error' => $errMsg];
    }
    
    private function updateResUserDeals(&$result)
    {
        $rdc = StaticFunctions::getServiceLocator()->get(\Restaurant\Model\RestaurantDealsCoupons::class);
        foreach ($result as $i => $res) 
        {
            $result[$i]['deals'] = $rdc->getRestaurantUserDeals(intval($res['res_id']));
        }
    }

}
