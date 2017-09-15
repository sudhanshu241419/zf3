<?php

/**
 * Description of SearchHelpers: This class is Helper class. Method defined inside this class 
 * will be of nature Helper. 
 *
 * @author Athar
 */

namespace Search\Solr;

class SearchHelpers 
{

    public static $mapPrevDay = array('mo' => 'su', 'tu' => 'mo', 'we' => 'tu','th' => 'we', 'fr' => 'th', 'sa' => 'fr', 'su' => 'sa');
    public static $mapNextDay = array('mo' => 'tu', 'tu' => 'we', 'we' => 'th', 'th' => 'fr','fr' => 'sat', 'sa' => 'su', 'su' => 'mo');
    
    public static $deliver_global_fq  =  '&fq=(accept_cc_phone:1+AND+res_delivery:1+AND+r_menu_available:1+AND+r_menu_without_price:0)';
    public static $takeout_global_fq  =  '&fq=(res_takeout:1+AND+r_menu_available:1+AND+r_menu_without_price:0)';
    public static $dinein_global_fq   =  '&fq=(res_dining:1)';
    public static $reserve_global_fq  =  '&fq=(res_reservations:1)';
    public static $discover_global_fq =  '';

    /**
     * Returns url data as string and http status code of the request
     * @param string $url
     * @return array with value of curl_exec in <b>data</b> and int <b>status_code</b>
     */
    public static function getCurlUrlData($url) 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response ['data']          =   curl_exec($ch); // data string
        $response ['status_code']   =   curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $response;
    }

    public static function getDebugUrl($url) 
    {
        return $url . '&echoParams=explicit&debug=true';
    }
    
    public static function applyIgnoreList($text) 
    {
        $newtext = " " . strtolower($text) . " ";
        $ignoredWords = array('/-/','/\sand\s/', '/\sor\s/', '/\srestaurants/', '/\sorder\s/',
            '/\sreservation\s/', '/\sdelivery\s/', '/\stakeout\s/', '/\saustin\s/',
            '/\snearby\s/', '/\sin\s/', '/\sbest\s/', '/\spopular\s/', '/\srestaurant\s/', '/\ssan francisco\s/', '/\ssfo\s/', '/\saustin\s/');
        return trim(preg_replace($ignoredWords, " ", $newtext));
    }
    
    /**
     * sort_by in {relevancy,popularity,distance,price,min_delivery} sort_type in {asc,desc}
     * @param string $sort_by defaults to 'relelancy'
     * @param string $sort_type
     * @param string $view_type
     * @return string
     */
    public static function getSortByFilter($sort_by, $sort_type, $view_type = 'restaurant', $at = 'street') 
    {        
        $secondSort = ($at == 'street') ? ',geodist()+asc' : ',accept_cc_phone+desc';

        $filter = '';
        switch ($sort_by) {
            case 'relevancy':
                break;
            case 'popularity':
                $filter = '&sort=popularity+' . $sort_type . $secondSort;
                break;
            case 'distance':
                $filter = '&sort=geodist()+asc,accept_cc_phone+desc';
                break;
            case 'price':
                if ($view_type == 'restaurant') {
                    $filter = '&sort=r_price_num+' . $sort_type . $secondSort;
                } else {
                    $filter = '&sort=menu_price_num+' . $sort_type . $secondSort;
                }
                break;
            case 'name':
                if ($view_type == 'restaurant') {
                    $filter = '&sort=res_fct+' . $sort_type . $secondSort;
                } else {
                    $filter = '&sort=menu_fct+' . $sort_type . $secondSort;
                }
                break;
            case 'min_delivery':
                $filter = '&sort=res_minimum_delivery+' . $sort_type . $secondSort;
            default:
                break;
        }
        return $filter;
    }
    
    
    public static function getDiscoverTimeFq($day, $time) 
    {
        if ($time == 0) {
            $ooh1_day = 'ooh1_' . $day;
            $coh1_day = 'coh1_' . $day;
            return '&fq=(' . $ooh1_day . ':[0+TO+30]+AND+' . $coh1_day . ':[0+TO+2359])';
        }
        $ooh1_day = 'ooh1_' . $day;
        $coh1_day = 'coh1_' . $day;
        $ooh2_day = 'ooh2_' . $day;
        $coh2_day = 'coh2_' . $day;
        $ooh3_day = 'ooh3_' . $day;
        $coh3_day = 'coh3_' . $day;
        $ooh4_day = 'ooh4_' . $day;
        $coh4_day = 'coh4_' . $day;
        return '&fq=' .
                '(' . $ooh1_day . ':[0+TO+' . $time . ']+AND+' . $coh1_day . ':[' . $time . '+TO+2359])+OR+' .
                '(' . $ooh2_day . ':[0+TO+' . $time . ']+AND+' . $coh2_day . ':[' . $time . '+TO+2359])+OR+' .
                '(' . $ooh3_day . ':[0+TO+' . $time . ']+AND+' . $coh3_day . ':[' . $time . '+TO+2359])+OR+' .
                '(' . $ooh4_day . ':[0+TO+' . $time . ']+AND+' . $coh4_day . ':[' . $time . '+TO+2359])';
    }
    
    /**
     * 
     * @param string $day {su,mo,tu,we,th,fr,sa}
     * @param int $time between 0-2359 (time in 24 hours format)
     * @return string
     */
    public static function getDeliverTimeFq($day, $time) 
    {
        if ($time == 0) {
            //$prevDay = SearchHelpers::$mapPrevDay[$this->day];
            $dst2_day = 'dst2_' . $day;
            $det2_day = 'det2_' . $day;
            $bst1_day = 'bst1_' . $day;
            $bet1_day = 'bet1_' . $day;
            //$openFromPrevDay = '((' . $dst1_prevday . ':[0+TO+2359]+AND+' . $det1_prevday . ':[0+TO+2359])+AND+('. $dst2_day . ':[0+TO+2359]+AND+' . $det2_day . ':[1+TO+2359]))';
            $openFromPrevDay = '(' . $dst2_day . ':[0+TO+2359]+AND+' . $det2_day . ':[1+TO+2359])';
            $openToday = '(' . $bst1_day . ':0+AND+' . $bet1_day . ':[1+TO+2359])';
            return '&fq=' . $openFromPrevDay . '+OR+' . $openToday;
        } else {
            $bst1_day = 'bst1_' . $day;
            $bet1_day = 'bet1_' . $day;
            $lst1_day = 'lst1_' . $day;
            $let1_day = 'let1_' . $day;
            $dst1_day = 'dst1_' . $day;
            $det1_day = 'det1_' . $day;
            //$nextDay = SearchHelpers::$mapNextDay[$this->day];
            $dst2_day = 'dst2_' . $day;
            $det2_day = 'det2_' . $day;
            return '&fq=' .
                    '(' . $bst1_day . ':[0+TO+' . $time . ']+AND+' . $bet1_day . ':[' . $time . '+TO+2359])+OR+' .
                    '(' . $lst1_day . ':[0+TO+' . $time . ']+AND+' . $let1_day . ':[' . $time . '+TO+2359])+OR+' .
                    '(' . $dst1_day . ':[0+TO+' . $time . ']+AND+' . $det1_day . ':[' . $time . '+TO+2359])+OR+' .
                    '(' . $dst2_day . ':[0+TO+' . $time . ']+AND+' . $det2_day . ':[' . $time . '+TO+2359])';
        }
    }

    /**
     * 
     * @param string $day {su,mo,tu,we,th,fr,sa}
     * @param int $time between 0-2359 (time in 24 hours format)
     * @return string
     */
    public static function getTakeoutTimeFq($day, $time) 
    {
        if ($time == 0) {
            //$prevDay = SearchHelpers::$mapPrevDay[$this->day];
            $ooh1_day = 'ooh1_' . $day;
            $coh1_day = 'coh1_' . $day;
            return '&fq=(' . $ooh1_day . ':[0+TO+30]+AND+' . $coh1_day . ':[0+TO+2359])';
        }
        $ooh1_day = 'ooh1_' . $day;
        $coh1_day = 'coh1_' . $day;
        $ooh2_day = 'ooh2_' . $day;
        $coh2_day = 'coh2_' . $day;
        $ooh3_day = 'ooh3_' . $day;
        $coh3_day = 'coh3_' . $day;
        $ooh4_day = 'ooh4_' . $day;
        $coh4_day = 'coh4_' . $day;
        return '&fq=' .
                '(' . $ooh1_day . ':[0+TO+' . $time . ']+AND+' . $coh1_day . ':[' . $time . '+TO+2359])+OR+' .
                '(' . $ooh2_day . ':[0+TO+' . $time . ']+AND+' . $coh2_day . ':[' . $time . '+TO+2359])+OR+' .
                '(' . $ooh3_day . ':[0+TO+' . $time . ']+AND+' . $coh3_day . ':[' . $time . '+TO+2359])+OR+' .
                '(' . $ooh4_day . ':[0+TO+' . $time . ']+AND+' . $coh4_day . ':[' . $time . '+TO+2359])';
    }
    
    /**
     * 
     * @param string $day {su,mo,tu,we,th,fr,sa}
     * @return string
     */
    public static function getDineinTimeFq($day) 
    {
        $ot1_day = 'ot1_' . $day; //solr field for open time query
        return '&fq=(' . $ot1_day . ':[*+TO+*])';
    }
    
    /**
     * 
     * @param string $day {su,mo,tu,we,th,fr,sa}
     * @return string
     */
    public static function getDineinTimeFqMob($day, $time) 
    {
        return self::getTakeoutTimeFq($day, $time);
    }

    /**
     * 
     * @param string $day {su,mo,tu,we,th,fr,sa}
     * @return string
     */
    public static function getReservationTimeFq($day) 
    {
        $ot1_day = 'ot1_' . $day; //solr field for open time query
        return '&fq=(' . $ot1_day . ':[*+TO+*])';
    }

    /**
     * 
     * @param string $day {su,mo,tu,we,th,fr,sa}
     * @return string
     */
    public static function getReservationTimeFqMob($day, $time) 
    {
        return self::getTakeoutTimeFq($day, $time);
    }
    
    /**
     * 
     * @param type $selection
     * @return string
     */
    public static function getMealsFq($selection)
    {
        if(! in_array($selection, array('breakfast','lunch', 'dinner'))){
            return '';
        }
        return '&fq=meals_arr:"' . $selection . '"';
    }
    
    /**
     * For food and restaurant view highlighting
     * @param array $keywords
     * @param string $view_type food/restaurant
     * @return string
     */
    public static function getHighlightFl($keywords, $view_type)
    {
        if(count($keywords) == 0){ return ''; }
        
        $q = '';
        foreach($keywords as $kw)
        {
            $q .= $kw . ' ';
        }
        
        $q       =   rawurlencode(preg_replace('/[&\/{}]/', ' ', trim($q)));
        $hlquery =   '&hl=true&hl.useFastVectorHighlighter=true&hl.tag.pre=&hl.tag.post=&hl.q='.$q;
        if ($view_type == 'restaurant') 
        {
            $hlquery .= '&hl.fl=res_cuisine,res_menu,feature_name,res_description';
            $hlquery .= '&f.res_cuisine.hl.fragsize=40&f.res_menu.hl.fragsize=40&hl.fragsize=245';
        } 
        else 
        {
            $hlquery .= '&hl.fl=menu_item_desc,menu_cuisine,res_cuisine,feature_name';
            //$hlquery .= '&f.res_cuisine.hl.fragsize=25&f.menu_item_desc.hl.fragsize=40';
            $hlquery .= '&hl.fragsize=25';
        }
        return $hlquery;
    }
    
    /**
     * $price allowed values 1,2,3,4
     * @param int $price selected price
     * @return string solr fq filter for this price
     */
    public static function getPriceFqFilter($price)
    {
        return '&fq=r_price_num:[' . ($price - 1) . '+TO+' . $price . ']';  // inclusive 
        //return '&fq=r_price_num:{' . ($price - 1) . '+TO+' . $price . ']';  // exclusive  
    }
    
    /**
     * What type of deals to facet on
     * @param string $open_tab delivery,takeout,dinein,reservation,all
     * @return string facet key has_deals for open tab
     */
    public static function getUrlDealsFacetPart($open_tab)
    {
        $result = '';
        switch ($open_tab) {
            case 'deliver':
                $result = '&facet=on&facet.query={!key=has_deals}has_delivery_deals:1';
                break;
            case 'delivery':
                $result = '&facet=on&facet.query={!key=has_deals}has_delivery_deals:1';
                break;
            case 'takeout':
                $result = '&facet=on&facet.query={!key=has_deals}has_takeout_deals:1';
                break;
            case 'dinein':
                $result = '&facet=on&facet.query={!key=has_deals}has_dinein_deals:1';
                break;
            case 'reservation':
                $result = '&facet=on&facet.query={!key=has_deals}has_dinein_deals:1';//yash/adit sir requested
                break;
            default:
                $result = '&facet=on&facet.query={!key=has_deals}has_deals:1';
                break;
        }
        return $result;
    }
    
    /**
     * get deals fq for corresponding open tab
     * @param string $open_tab
     * @return string deals fq
     */
    public static function getSolrDealsFq($open_tab)
    {
        $result = '';
        switch ($open_tab) {
            case 'deliver':
                $result = '&fq=has_delivery_deals:1';
                break;
            case 'delivery':
                $result = '&fq=has_delivery_deals:1';
                break;
            case 'takeout':
                $result = '&fq=has_takeout_deals:1';
                break;
            case 'dinein':
                $result = '&fq=has_dinein_deals:1';
                break;
            case 'reservation':
                $result = '&fq=has_dinein_deals:1';
                break;
            default:
                $result = '&fq=has_deals:1';
                break;
        }
        return $result;
    }
    
        
    /**
     * Get latlong for a given zip
     * @param String $address
     * @return array with keys <b>lat</b> and <b>lng</b>
     */
    public static function getLatlong($address)
    {
        $latlong = ['lat' => 0, 'lng' => 0];
        $url = "http://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&sensor=false";
        $result_string = file_get_contents($url);
        $result_arr = json_decode($result_string, true);
        if($result_arr['status']==='OK' && isset($result_arr['results'][0]['geometry']['location'])){
            $latlong = $result_arr['results'][0]['geometry']['location'];
        }
        return $latlong;
    }
    
    /**
     * Get default api response with OK as <b>status</b> and empty array as <b>data</b>
     * @return array
     */
    public static function getDefaultApiResponseArr() 
    {
        $response = ['status' => 'OK', 'data' => []];
        if(isset($_REQUEST['DeBuG'])){
            $response['version'] = Version::VERSION;
        }
        return $response;
    }

    /**
     * Update API response if some error occurs
     * @param string $err
     * @param array $response
     */
    public static function updateBadApiResponseArr(&$response, $err)
    {
        $response['status'] = 'FAIL';
        $response['error'] = $err;
        $response['data'] = [];
    }
}
