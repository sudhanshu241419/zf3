<?php
/**
 * @author Athar
 */

namespace Search\Solr;

use Search\Solr\SearchHelpers;
use Search\Solr\AtAvFq;
use MCommons\StaticFunctions;

class SearchUrlsMobile extends SearchHelpers {
    
    /**
     *
     * @var Solr\Common\AtAvFq 
     */
    public $atavfq;
    
    /**
     * open view type
     * @var type 
     */
    public $view_type = 'restaurant';
    
    /**
     *
     * @var type 
     */
    public $day = ''; //selected day {mo,tu,we,th,fr,sa,su}
    /**
     * order time (between 0 and 2359, 24 hours format)
     * @var integer 
     */
    public $time = 0;
    //main search variables
    
    /** order time (between 0 and 2359, 24 hours format)
     * @var string */
    public $sort_by;
    
    /** order time (between 0 and 2359, 24 hours format)
     * @var string */
    public $sort_type;

    /**
     * main q, covers free text, food, restaurant
     * @var string 
     */
    public $search_q = '';

    /**
     * cuisines filters
     * @var string 
     */
    public $search_cui = '';

    /**
     * type of place filters
     * @var string
     */
    public $search_top = '';

    /**
     * price filter e.g. fq=r_price_num:[*+TO+4]
     * @var string
     */
    public $price_fq = '';

    /**
     * deals filter e.g. fq=has_deals:1
     * @var string
     */
    public $deals_fq = '';

    /**
     * covers 'aor' and 'acp' filters
     * @var string
     */
    public $etc_fq = '';

    /**
     * deals filter e.g. fq=has_deals:1
     * @var string
     */
    public $accepts_order_fq = '';

    /**
     * e.g. http://localhost:8983/solr/
     * @var string
     */
    public $solr_url = '';

    /**
     * solr res core search handler http://localhost:8983/solr/hbr/hbsearch?
     * @var string 
     */
    public $res_url = '';

    /**
     * solr food core search handler http://localhost:8983/solr/hbm/hbsearch?
     * @var string
     */
    public $food_url = '';

    /**
     * http://localhost:8983/solr/hbm/select?
     * @var type 
     */
    public $food_select_url = '';

    /**
     * http://localhost:8983/solr/hbr/hbauto?
     * @var string
     */
    public $ac_res_url = '';

    /**
     * http://localhost:8983/solr/hbm/hbauto?
     * @var string
     */
    public $ac_food_url = '';

    function __construct() {
        //$this->solr_url         =   StaticFunctions::getSolrUrl();
        $this->solr_url         =   StaticFunctions::getSolrUrl();
        $this->res_url          =   $this->solr_url . "hbr/hbsearch?";
        $this->food_url         =   $this->solr_url . 'hbm/hbsearch?';
        $this->food_select_url  =   $this->solr_url . 'hbm/select?';
        $this->ac_res_url       =   $this->solr_url . 'hbr/hbauto?';
        $this->ac_food_url      =   $this->solr_url . 'hbm/hbauto?';
    }
    
    /**
     * Sets class variables like day, time, latlong, at, av, search_q, search_cui,
     * search_top, and nbd_cities etc.
     * @param array $req
     */
    public function setClassVariables($req) 
    {
        $this->atavfq       = new AtAvFq($req, 'mob');
        $this->day          = 'mo';//$req['day'];
        $this->time         = !empty($req['stime'])?(int) $req['stime']:0;
        $this->view_type    = $req['view_type'];
        $this->sort_by      = $req['sort_by'];
        $this->sort_type    = $req['sort_type'];
        
        $this->setQueryCuisinePlaces($req);
        $this->setPriceDealsAcceptsOrderFq($req);

        //aor=1 if Accept Online Orders
        if ($req['aor'] > 0) 
        {
            $this->etc_fq .= '&fq=ordering_enabled:1';
        }

        //acp =1 if registered restaurants or with some tag
        if ($req['acp'] > 0) 
        {
            //$this->etc_fq .= '&fq=(is_registered:1+OR+tags_fct:[*+TO+*])';
            $this->etc_fq .= '&fq=(is_registered:1)';
        }
    }
    
    private function setPriceDealsAcceptsOrderFq($req) 
    {
        if ($req['price'] > 0) 
        {
            $this->price_fq = self::getPriceFqFilter((int)$req['price']);
        }
        
        if ($req['deals'] > 0) 
        {
            $this->deals_fq = self::getSolrDealsFq($req['tab']);
        }

        if ($req['accepts_order'] == 1) 
        {
            $this->accepts_order_fq = '&fq=ordering_enabled:1';
        }
    }

    //======================= MAIN SEARCH DATA =============================
    
    private function getCommonUrl($req)
    {
        $this->setClassVariables($req); 
        
        $baseUrl    =   $this->food_url;
        if ($this->view_type == 'restaurant') 
        {
            $baseUrl = $this->res_url;
        } 
        
        $solr_sv  =  'start=' . $req['start'] . '&rows=' . $req['rows'] . '&pt=' . $this->atavfq->cl_latlong;//for mobile map view center
        $solr_sv .=  self::getSortByFilter($this->sort_by, $this->sort_type, $this->view_type, $req['at']); //sort by filter
        $query    =  $this->search_q . $this->search_cui . $this->search_top;
        return $baseUrl . $solr_sv . $query;
    }

    public function getDiscoverUrl($req) 
    {
        $commonUrl =  $this->getCommonUrl($req);
        $solrFq    =  $this->getDiscoverFqFilters($req); //all fq filters
        return $commonUrl.$solrFq;
    }
    
    public function getDeliverUrl($req) 
    {
        $commonUrl  =   $this->getCommonUrl($req);
        $solrFq     =   $this->getDeliverFqFilters($req); //all fq filters
        return $commonUrl.$solrFq;
    }

    public function getTakeoutUrl($req) {
        $commonUrl = $this->getCommonUrl($req);
        $solrFq = $this->getTakeoutFqFilters($req); //all fq filters
        return $commonUrl.$solrFq;
    }

    public function getDineinUrl($req) {
        $commonUrl = $this->getCommonUrl($req);
        $solrFq = $this->getDineinFqFilters($req); //all fq filters
        return $commonUrl.$solrFq;;
    }

    public function getReservationUrl($req) {
        $commonUrl = $this->getCommonUrl($req);
        $solrFq = $this->getReserveFqFilters($req); //all fq filters
        return $commonUrl.$solrFq;
    }

    //======================= END MAIN SEARCH DATA ==========================

    //================== FQ FILTERS ==================
    
    private function getCommonFqFilters($req){
        $commonFq = '';
        if ($this->view_type == 'food') {//donot show menu with zero price
            $commonFq .= '&fq=!(menu_price_num:0)';
        }
        $commonFq .= $this->getPriceDealsAcceptsOrderFq();
        if($req['orn'] == 1){
            $commonFq .= self::getDiscoverTimeFq($req['day'], $req['curr_time']);
        }

        $commonFq .= $this->etc_fq; //etc filter

        return $commonFq;
    }

    private function getDiscoverFqFilters($req) {
        $solrfq = $this->getCommonFqFilters($req);
        $solrfq .= self::$discover_global_fq;
        $solrfq .= $this->atavfq->getDiscoverAtAvFq($req);
        return $solrfq;
    }
    
    private function getDeliverFqFilters($req) {
        $solrfq = $this->getCommonFqFilters($req);
        $solrfq .= self::$deliver_global_fq;
        $solrfq .= $this->atavfq->getDeliverAtAvFq();
        if( !empty($req['sdate'])){
            $solrfq .= self::getDeliverTimeFq($this->day, $this->time); //time filter
        }
        return $solrfq;
    }

    private function getTakeoutFqFilters($req) {
        $solrfq = $this->getCommonFqFilters($req);
        $solrfq .= self::$takeout_global_fq;
        $solrfq .= $this->atavfq->getTakeoutAtAvFq();
        if( !empty($req['sdate'])){
            $solrfq .= self::getTakeoutTimeFq($this->day, $this->time); //time filter
        }
        return $solrfq;
    }

    private function getDineinFqFilters($req) {
        $solrfq = $this->getCommonFqFilters($req);
        $solrfq .= self::$dinein_global_fq;
        $solrfq .= $this->atavfq->getDineinAtAvFq();
        //$solrfq .= '&fq=meals_arr:"' . $req['subtab'] . '"';
        if( !empty($req['sdate'])){
            $solrfq .= self::getDineinTimeFqMob($this->day, $this->time);
        }
        return $solrfq;
    }

    private function getReserveFqFilters($req) {
        $solrfq = $this->getCommonFqFilters($req);
        
        $solrfq .= self::$reserve_global_fq;
        
        $solrfq .= $this->atavfq->getReserveAtAvFq($req); //address_type, address_value filter
        
        //search_reservationtype
        //$solrfq .= '&fq=meals_arr:"' . $req['subtab'] . '"';
        if($req['all'] == '0'){
            $solrfq .= self::getReservationTimeFqMob($this->day, $this->time);
        }
        
        $solrfq .= $this->getReservationTypeFq($req['res_type']);
        return $solrfq;
    }
    
    //================== END FQ FILTERS ==================

    private function setQueryCuisinePlaces($req) {
        //SearchHelpers::pr($req);
        //set main search query q
        if ($req['q'] != '' && $req['dt'] != '') {
            $this->search_q = $this->setSolrQ($req);
        }

        $search_cui = '';
        $search_top = '';

        
        $q_helper = array();
        //set solr fq's
        if ($req['cuisines'] != '') 
        {
            $cuisinesArr = explode(',', html_entity_decode($req['cuisines']));
            $n = count($cuisinesArr);
            for ($i = 0; $i < $n; $i++) 
            {
                $q_helper['cuisines'][] = '"' . rawurlencode($cuisinesArr[$i]) . '"';
            }
        }

        if($req['features'] != '')
        {
            $topArr = explode(',', html_entity_decode($req['features']));
            $n = count($topArr);
            for ($i = 0; $i < $n; $i++) 
            {
                $q_helper['features'][] = '"' . rawurlencode($topArr[$i]) . '"';
            }
        }

        if (isset($q_helper['features'])) 
        {
            $search_top .= '&fq=feature_fct:(' . implode('+OR+', $q_helper['features']) . ')';
        }

        if (isset($q_helper['cuisines'])) 
        {
            if ($this->view_type == 'restaurant') 
            {
                $search_cui .= '&fq=cuisine_fct:(' . implode('+OR+', $q_helper['cuisines']) . ')';
            } 
            elseif ($this->view_type == 'food') 
            {
                $search_cui .= '&fq=menu_cuisine_fct:(' . implode('+OR+', $q_helper['cuisines']) . ')';
            }
        }
        
        $this->search_cui = $search_cui;
        $this->search_top = $search_top;
    }
       
    //======================== PICK AN AREA =================================
    public function getPickAnAreaFq($req) {
        $this->setClassVariables($req);
        $fq = '';
        switch ($req ['tab']) {
            case 'all' :
                $fq = $this->getPaaDiscoverFq($req);
                break;
            case 'delivery' :
                $fq = $this->getPaaDeliverFq($req);
                break;
            case 'takeout' :
                $fq = $this->getPaaTakeoutFq($req);
                break;
            case 'dinein' :
                $fq = $this->getPaaDineinFq($req);
                break;
            case 'reservation' :
                $fq = $this->getPaaReserveFq($req);
                break;
        }
        return $fq;
    }
    
    private function getPaaCommonFq($req){
        $solrfq = $this->search_q . $this->search_cui . $this->search_top;
        $solrfq .= '&fq=city_id:' . $req['city_id'];
        $solrfq .= $this->getPriceDealsAcceptsOrderFq();
        if($req['orn'] == 1){
            $solrfq .= self::getDiscoverTimeFq($req['day'], $req['curr_time']);
        }
        return $solrfq;
    }
    
    private function getPaaDiscoverFq($req) {
        $solrfq = $this->getPaaCommonFq($req);
        $solrfq .= self::$discover_global_fq;
        return $solrfq;
    }

    private function getPaaDeliverFq($req) {
        $solrfq = $this->getPaaCommonFq($req);
        $solrfq .= self::$deliver_global_fq;
        $solrfq .= '&fq=city_id:' . $req['city_id'];
        //$solrfq .= $this->atavfq->getDeliverAtAvFq();
        $solrfq .= self::getDeliverTimeFq($this->day,  $this->time);
        return $solrfq;
    }

    private function getPaaTakeoutFq($req) {
        $solrfq = $this->getPaaCommonFq($req);
        $solrfq .= self::$takeout_global_fq;
        //$solrfq .= $this->atavfq->getTakeoutAtAvFq();
        $solrfq .= self::getTakeoutTimeFq($this->day, $this->time);
        return $solrfq;
    }

    private function getPaaDineinFq($req) {
        $solrfq = $this->getPaaCommonFq($req);
        $solrfq .= self::$dinein_global_fq;
        //$solrfq .= $this->atavfq->getDineinAtAvFq();
        //search_reservationtype
        //$solrfq .= '&fq=meals_arr:"' . $req['subtab'] . '"';
        $solrfq .= self::getDineinTimeFqMob($this->day, $this->time);;
        return $solrfq;
    }

    private function getPaaReserveFq($req) {
        $solrfq = $this->getPaaCommonFq($req);
        $solrfq .= self::$reserve_global_fq;
        //$solrfq .= $this->atavfq->getReserveAtAvFq($req);
        //search_reservationtype
        //$solrfq .= '&fq=meals_arr:"' . $req['subtab'] . '"';
        if($req['all'] == '0'){
            $solrfq .= self::getReservationTimeFqMob($this->day, $this->time);
        }
        $solrfq .= $this->getReservationTypeFq($req['res_type']);
        return $solrfq;
    }

    //======================= FACET DATA ===============================

    public function getFacetData($req = array()) {
        $this->setClassVariables($req);
        $url = '';
        switch ($req['tab']) {
            case 'delivery':
                $url = $this->getFacetDeliverUrl($req);
                break;
            case 'takeout':
                $url = $this->getFacetTakeoutUrl($req);
                break;
            case 'dinein':
                $url = $this->getFacetDineinUrl($req);
                break;
            case 'reservation':
                $url = $this->getFacetReservationUrl($req);
                break;
            case 'all':
                $url = $this->getFacetDiscoverUrl($req);
                break;
        }
        return array('view_type' => $this->view_type, 'url' => $url, 'cui' => $this->search_cui, 'features' => $this->search_top);
    }
    
    private function getFacetCommonUrl($params){
        if ($params['view_type'] == 'food') {
            $commonUrl = $this->food_url;
        } else {
            $commonUrl = $this->res_url;
        }
        return $commonUrl.'start=0&rows=0&pt=' . $params['latlong'] .$this->search_q;
    }
    
    private function getFacetDeliverUrl($params = array()) {
        $commonUrl = $this->getFacetCommonUrl($params);
        //all fq filters
        $solrFq = $this->getDeliverFqFilters($params);
        return $commonUrl . $solrFq;
    }

    private function getFacetTakeoutUrl($params = array()) {
        $commonUrl = $this->getFacetCommonUrl($params);
        //all fq filters
        $solrFq = $this->getTakeoutFqFilters($params);
        return $commonUrl . $solrFq;
    }

    private function getFacetDineinUrl($params) {
        $commonUrl = $this->getFacetCommonUrl($params);
        //all fq filters
        $solrFq = $this->getDineinFqFilters($params);
        return $commonUrl . $solrFq;
    }

    private function getFacetReservationUrl($params) {
        $commonUrl = $this->getFacetCommonUrl($params);
        //all fq filters
        $solrFq = $this->getReserveFqFilters($params);
        return $commonUrl . $solrFq;
    }

    private function getFacetDiscoverUrl($params) {
        $commonUrl = $this->getFacetCommonUrl($params);
        //all fq filters
        $solrFq = $this->getDiscoverFqFilters($params);
        return $commonUrl . $solrFq;
    }
    
    //======================= AUTOSUGGESTION DATA =============================
    
    private function getAcCommonUrl($request){
        if ($request['view_type'] == 'restaurant') {
            $baseUrl = $this->ac_res_url;
        } else {
            $baseUrl = $this->ac_food_url;
        }
        return $baseUrl;
    }
    
    private function getAcCuiPart(){
        return 'start=0&rows=0&pt=' . $this->atavfq->latlong . $this->search_q . $this->search_top;
    }
    
    private function getAcTopPart(){
        return 'start=0&rows=0&pt=' . $this->atavfq->latlong . $this->search_q . $this->search_cui;
    }
    
    private function getAcNamePart($req){
        return 'pt=' . $this->atavfq->latlong . $this->search_q . $this->search_cui . $this->search_top . '&q=' . $req['term'];
    }

    private function getAcUrl($req, $solrFq){
        $baseUrl = $this->getAcCommonUrl($req);
        $cui_url = $baseUrl . $this->getAcCuiPart() . $solrFq;
        $top_url = $baseUrl . $this->getAcTopPart() . $solrFq;
        $name_url = $baseUrl . $this->getAcNamePart($req) . $solrFq;
        return array($cui_url, $top_url, $name_url);
    }

    public function getAcDiscoverUrls($req) {
        $solrFq = $this->getDiscoverFqFilters($req); //all fq filters
        return $this->getAcUrl($req, $solrFq);
    }
    
    public function getAcDeliverUrls($req = array()) {
        $solrFq = $this->getDeliverFqFilters($req);
        return $this->getAcUrl($req, $solrFq);
    }

    public function getAcTakeoutUrls($req = array()) {
        $solrFq = $this->getTakeoutFqFilters($req); //all fq filters
        return $this->getAcUrl($req, $solrFq);
    }

    public function getAcDineinUrls($req) {
        $solrFq = $this->getDineinFqFilters($req); //all fq filters
        return $this->getAcUrl($req, $solrFq);
    }

    public function getAcReservationUrls($req) {
        $solrFq = $this->getReserveFqFilters($req); //all fq filters
        return $this->getAcUrl($req, $solrFq);
    }

    //======================= END AUTOSUGGESTION DATA =========================
    
    private function setSolrQ($req) {
        //pr($req,1);
        $solr_q = '';
        if($req['q'][0] == "-")
        {
            $req['q']   =   substr($req['q'],1,strlen($req['q'])-1);
        }
        else if($req['q'][0] == '"' || $req['q'][0] == "'")
        {
            if ($req['q'][1] == "-")
            {
                $req['q']   =   $req['q'][0].substr($req['q'],2,strlen($req['q'])-1);
            }
        }
        /*
         * possible values of $req['dt']
         *  {c: "cuisine", fav: "favorite", p: "preference", t: "type-of-place", a: "ambience", 
         *   fe: "feature", r: "restaurant", fo: "food", ft: "free-text"}
         */
        $q = rawurlencode($req['q']);
        if ($req['dt'] == 'ft') 
        {
            $exp = explode(" ", $q);
            if (count($exp)>1)
            {
                $solr_q = "&q='" . $q."'^5+OR+".$q;
            }
            else
            {
                $solr_q = "&q=" .$q;
            }
        } 
        else if ($req['dt'] == 'top') 
        {
            $solr_q .= '&q=feature_fct:"' . rawurlencode($req['q']) . '"';
        } 
        elseif ($req['dt'] == 'cuisine') 
        {
            if ($this->view_type == 'restaurant') 
            {
                $solr_q .= '&q=cuisine_fct:"' . $q . '"';
            } 
            elseif ($this->view_type == 'food') 
            {
                $solr_q .= '&q=menu_cuisine_fct:"' . $q . '"';
            }
        } 
        else if ($req['dt'] == 'curated') {
            $solr_q .= '&q=curated_id:' . rawurlencode(intval($req['q']));
        }
        return $solr_q;
    }

    private function getPriceDealsAcceptsOrderFq()
    {
        return $this->price_fq . $this->deals_fq . $this->accepts_order_fq;
    }
    
    private function getReservationTypeFq($res_type){
        $fq = '';
        switch ($res_type) {
            case 'all':
                break;
            case 'regular':
                $fq .= '&fq=accept_cc_phone:1';
                break;
            case 'prepaid':
                $fq .= '&fq=(accept_cc_phone:1+AND+is_registered:1+AND+r_menu_without_price:0)';
                break;
            default:
                break;
        } 
        return $fq;
    }

}
