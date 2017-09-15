<?php
/**
 * Description of SearchHelpers
 *
 * @author Athar
 */
namespace Search\Solr;

class AtAvFq {
    
    /** Selected city id
     * @var int 
     */
    public $city_id;
    
    /**
     * address_type
     * @var string
     */
    public $at = '';
    
    /**
     * address_value
     * @var string
     */
    public $av = '';
    
    /**
     * lattitude,longitude (user selected location)
     * @var string 
     */
    public $latlong = '';
    
    /**
     * center lattitude,longitude in map view
     * @var string 
     */
    public $cl_latlong = '';

    /** "longitude lattitude" (user location)
     *  @var string */
    public $longlat = '';
    
    public static $zip_geofilt              =   '';                         //'&fq={!geofilt}&d=1.6'; as requested on 16-Feb-2016
    public static $nbd_geofilt              =   '';                         //
    public static $street_geofilt           =   '&fq={!geofilt}&d=1.6';     //5 miles ~ 8 kms
    public static $street_geofilt_discover  =   '&fq={!geofilt}&d=200';     //needless d kept for compatibility
    
    public static $bf_distance = '&bq={!func}recip(geodist(),1,100,1)';     // recip(x,m,a,b) implementing a/(m*x+b)
    
    const BF_ALL         = '&bf=sum(product(accept_cc_phone,sum(res_delivery,res_takeout,product(res_reservations,2),is_registered),100),product(has_deals,200),r_score)';
    const BF_DELIVERY    = '&bf=sum(product(accept_cc_phone,res_delivery,100),product(has_delivery_deals,200),r_score)';
    const BF_TAKEOUT     = '&bf=sum(product(accept_cc_phone,res_takeout,100),product(has_takeout_deals,200),r_score)';
    const BF_DINEIN      = '&bf=sum(product(accept_cc_phone,res_reservations,100),product(has_dinein_deals,200),r_score)';
    const BF_RESERVATION = '&bf=sum(product(accept_cc_phone,res_reservations,100),product(has_dinein_deals,200),r_score)';
    
    /**
     * 
     * @param array $req
     * @param string $type {web,mob}
     */
    public function __construct($req, $type) 
    {
        $this->city_id  =   $req['city_id'];
        $this->at       =   $req['at'];
        $this->av       =   $req['av'];
        
        if($type == 'web')
        {
            $this->latlong      =   $req['lat'] . ',' . $req['lng'];
            $this->cl_latlong   =   $this->latlong;
            $this->longlat      =   $req['lng'] . '+' . $req['lat'];
        } 
        elseif($type == 'mob')
        {
            $this->latlong      =   $req['latlong'];
            $this->cl_latlong   =   $req['cl_latlong'];
            $latlong            =   explode(',', $req['latlong']);
            $this->longlat      =   $latlong[1] . '+'. $latlong[0];
        } 
        else 
        {
            throw new \Exception('invalid call @ Atavfq constructor' , 400);
        }
    }
    
    private function getCommonAtAvFq()
    {
        return '&fq=city_id:'.$this->city_id;
    }
    
    public function getDiscoverAtAvFq() 
    {
        $atavfq = $this->getCommonAtAvFq() . self::BF_ALL;
        switch ($this->at) {
            case 'city':
                $atavfq .= $this->getCityAtAvFq();
                break;
            case 'zip':
                $atavfq .= $this->getZipAtAvFq();
                break;
            case 'nbd':
                $atavfq .= $this->getNbdAtAvFq();
                break;
            case 'street':
                $atavfq .= $this->getStreetAtAvFqDiscover();
                break;
            case 'exactzip':
                $atavfq .= $this->getExactZipAtAvFq();
                break;
            case 'exactnbd':
                $atavfq .= $this->getExactNbdAtAvFq();
                break;
        }
        return $atavfq;
    }
    
    public function getDeliverAtAvFq() {
        $atavfq = $this->getCommonAtAvFq(). self::BF_DELIVERY;
        switch ($this->at) {
            case 'city':
                $atavfq .= $this->getCityAtAvFq();
                break;
            case 'zip':
                $atavfq .= $this->getZipAtAvFq();
                break;
            case 'nbd':
                $atavfq .= $this->getNbdAtAvFq();
                break;
            case 'street':
                if ($this->city_id == 18848) {
                    $atavfq .= '&fq=delivery_geo:"Intersects(' . $this->longlat . ')"' . self::$bf_distance;
                } else {
                    $atavfq .= '&fq={!frange u=0}sub(mul(geodist(latlong,' . $this->latlong . '),0.621371),delivery_area)';
                }
                break;
            case 'exactzip':
                $atavfq .= $this->getExactZipAtAvFq();
                break;
            case 'exactnbd':
                $atavfq .= $this->getExactNbdAtAvFq();
                break;
        }
        return $atavfq;
    }

    public function getTakeoutAtAvFq() {
        $atavfq = $this->getCommonAtAvFq() . self::BF_TAKEOUT;
        switch ($this->at) {
            case 'city':
                $atavfq .= $this->getCityAtAvFq();
                break;
            case 'zip':
                $atavfq .= $this->getZipAtAvFq();
                break;
            case 'nbd':
                $atavfq .= $this->getNbdAtAvFq();
                break;
            case 'street':
                $atavfq .= $this->getStreetAtAvFq();
                break;
            case 'exactzip':
                $atavfq .= $this->getExactZipAtAvFq();
                break;
            case 'exactnbd':
                $atavfq .= $this->getExactNbdAtAvFq();
                break;
        }
        return $atavfq;
    }

    public function getDineinAtAvFq() {
        $atavfq = $this->getCommonAtAvFq() . self::BF_DINEIN;
        switch ($this->at) {
            case 'city':
                $atavfq .= $this->getCityAtAvFq();
                break;
            case 'zip':
                $atavfq .= $this->getZipAtAvFq();
                break;
            case 'nbd':
                $atavfq .= $this->getNbdAtAvFq();
                break;
            case 'street':
                $atavfq .= $this->getStreetAtAvFq();
                break;
            case 'exactzip':
                $atavfq .= $this->getExactZipAtAvFq();
                break;
            case 'exactnbd':
                $atavfq .= $this->getExactNbdAtAvFq();
                break;
        }
        return $atavfq;
    }

    public function getReserveAtAvFq() {
        $atavfq = $this->getCommonAtAvFq() . self::BF_RESERVATION;
        switch ($this->at) {
            case 'city':
                $atavfq .= $this->getCityAtAvFq();
                break;
            case 'zip':
                $atavfq .= $this->getZipAtAvFq();
                break;
            case 'nbd':
                $atavfq .= $this->getNbdAtAvFq();
                break;
            case 'street':
                $atavfq .= $this->getStreetAtAvFq();
                break;
            case 'exactzip':
                $atavfq .= $this->getExactZipAtAvFq();
                break;
            case 'exactnbd':
                $atavfq .= $this->getExactNbdAtAvFq();
                break;
        }
        return $atavfq;
    }
    
    private function getCityAtAvFq(){
        return '';
    }
    
    private function getNbdAtAvFq()
    {
        $nbd = rawurlencode(htmlspecialchars_decode($this->av));
        //return self::$nbd_geofilt . '&fq=res_landmark:"'.$nbd.'"';
        return self::$nbd_geofilt . '&fq=res_neighborhood:"'.$nbd.'"';
        //return self::$bf_distance .'&fq=res_landmark:"'.$nbd.'"';
    }
    
    private function getExactNbdAtAvFq()
    {
        $nbd = rawurlencode(htmlspecialchars_decode($this->av));
        //return '&fq=res_landmark:"'.$nbd.'"';
        return '&fq=res_neighborhood:"'.$nbd.'"';
        //return self::$bf_distance .'&fq=res_landmark:"'.$nbd.'"';
    }
    
    private function getZipAtAvFq()
    {
        $zip = rawurlencode(htmlspecialchars_decode($this->av));
        return self::$zip_geofilt . '&fq=res_zipcode:"' . $zip . '"';
        //return self::$bf_distance . '&fq=res_zipcode:"' . $zip . '"';
    }
    
    private function getExactZipAtAvFq()
    {
        $zip = rawurlencode(htmlspecialchars_decode($this->av));
        return '&fq=res_zipcode:"' . $zip . '"';
        //return self::$bf_distance . '&fq=res_zipcode:"' . $zip . '"';
    }
    
    private function getStreetAtAvFq()
    {
        return self::$street_geofilt.self::$bf_distance;
    }
    
    /**
     * Discover case street at av filters
     * Requested on 01-Sep-2016
     * @return string
     */
    private function getStreetAtAvFqDiscover()
    {
        return self::$street_geofilt_discover.self::$bf_distance;
    }

}