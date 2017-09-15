<?php

namespace Search\Solr;

use MCommons\StaticFunctions;
use Search\Solr\SearchUrlsMobile;

/**
 * For discover/find_by_mood, restaurant count use getRestaurantCounts()
 *
 * For pick an area use getLandmarksData()
 *
 * @author Dhirendra Singh Yadav   / revised by Athar
 */
class FriendsMobile 
{

    private $debug                      = 0 ;
    private $_solr_url                  = '';
    private $__obj_search_urls_mobile   = '';
    private $__obj_user_function        = '';
    
    public function __construct() 
    {
        $this->__obj_search_urls_mobile = StaticFunctions::getServiceLocator()->get(SearchUrlsMobile::class);
        $this->_solr_url                = $this->__obj_search_urls_mobile->solr_url . 'hbu/hbsearch?';
    }
    
        /**
     * Get list of suggested users based on partial user query
     * @param array $input request array
     * @return array friends data
     */
    public function getFriendSuggestions($input)
    {
        if ($input['DeBuG'] == 404) 
        {
            $this->debug = 1;
        }
        
        $queryPart  =   'rows='.$input['rows'] .'&q=' . $input['term'];
        $url        =   $this->_solr_url . $queryPart;
        //$url        =   "http://qc.munchado.in:8984/solr/hbu/hbsearch?" . $queryPart;
        $output     =   $this->__obj_search_urls_mobile->getCurlUrlData($url);
        $response   =   array();
        if ($output ['status_code'] == 200) 
        {
            $dataArr    = json_decode($output ['data'], true);
            $numCount   = $dataArr ['response'] ['numFound'];
            if ($numCount > 0) 
            {
                $response       = $dataArr ['response'] ['docs'];
                foreach($response as $i => $user)
                {
                    $this->__obj_user_function = new \User\Functions\UserFunctions();
                    $response[$i]['image_url'] = $this->__obj_user_function->findImageUrlNormal($response[$i]['image_url'], $response[$i]['uid']);
                    $response[$i]['is_friend'] = $this->isFriend($input['user_id'], $user['uid']);
                    if($response[$i]['is_friend'] == 1)
                    {
                        $response[$i]['req_pending']    =   0;
                    } 
                    else 
                    {
                       $response[$i]['req_pending']     =   $this->isRequestPending($input['user_id'], $user); 
                    }
                }
            }
        }
        if ($this->debug) 
        {
            $response[] =   array('url' => $this->__obj_search_urls_mobile->getDebugUrl($url), 'landmark' => '', 'latitude' => '', 'longitude' => '');
        }

        return $response;
    }
    
    /**
     * Check if current_user is friend of other user or not
     * @param int $user_id
     * @param array $other_user_id
     */
    private function isFriend($user_id, $other_user_id)
    {
        $userFriends = StaticFunctions::getServiceLocator()->get(\User\Model\UserFriends::class);
        return $userFriends->isFriend($user_id, $other_user_id);
    }
    
    /**
     * Check if current_user's has a pending request with other_user
     * @param int $user_id
     * @param array $other_user
     */
    private function isRequestPending($user_id, $other_user)
    {
        return 0;
    }

}
