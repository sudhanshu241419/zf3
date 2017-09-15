<?php

namespace Restaurant\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

class Restaurant extends AbstractModel {

    public $id;
    public $rest_code;
    public $restaurant_name;
    public $description;
    public $address;
    public $street;
    public $city_id;
    public $zipcode;
    public $landmark;
    public $restaurant_image_name;
    public $accept_cc;
    public $accept_cc_phone;
    public $accept_dc;
    public $delivery;
    public $takeout;
    public $dining;
    public $reservations;
    public $menu_available;
    public $is_chain;
    public $latitude;
    public $longitude;
    public $neighborhood;
    public $nbd_latitude;
    public $nbd_longitude;
    public $closed;
    public $inactive;
    public $price;
    public $delivery_area;
    public $minimum_delivery;
    public $min_partysize;
    public $delivery_charge;
    public $sentiments;
    public $cash;
    public $delevery_charge_type;
    public $attire_desc;
    public $good_for_group_desc;
    public $facebook_url;
    public $twitter_url;
    public $gmail_url;
    public $pinterest_url;
    public $instagram_url;
    public $delivery_desc;
    public $notable_chef_desc;
    public $parking_desc;
    public $updated_on;
    public $total_seats;
    public $ratings;
    public $phone_no;
    public $email;
    public $mobile_no;
    public $source_url;
    public $phone_no2;
    public $fax;
    public $menu_without_price;
    public $allowed_zip;
    public $delivery_geo;
    public $order_pass_through;
    public $menu_sort_order;
    public $cod;
    public $restaurant_video_name;
    public $restaurant_logo_name;

    protected $_tableGateway;
    
    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;     
    }

    public function findRestaurant(array $options = []) {

        $restaurant = $this->find($options)->toArray();
        if (!$restaurant) {
            throw new \Exception("No Result Found");
        }
       return $restaurant;
    }

    public function getRestaurantLocation($restaurant_id = 0) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'id',
            'restaurant_name',
            'rest_code',
            'address',
            'zipcode',
            'restaurant_image_name',
            'phone_no',
            'latitude',
            'longitude'
        ));

        $select->join(array(
            'c' => 'cities'
                ), 'c.id = restaurants.city_id', array(
            'city_name'
                ), $select::JOIN_INNER);

        $select->join(array(
            's' => 'states'
                ), 's.id = c.state_id', array(
            'state'
                ), $select::JOIN_INNER);

        $select->join(array(
            'co' => 'countries'
                ), 'co.id = c.country_id', array(
            'country_name'
                ), $select::JOIN_INNER);

        $select->where(array(
            'restaurants.id' => $restaurant_id
        ));
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $restaurantLocation = $this->_tableGateway->selectWith($select);
        return $restaurantLocation;
    }

    public function getRestaurantShortAddress($restaurant_id = 0) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());

        $select->columns(array(
            'address'
        ));

        $select->join(array(
            'rl' => 'restaurants_location'
                ), 'rl.restaurant_id = restaurants.id', array(
            'miles' => 'max_delivery_distance'
                )
                , $select::JOIN_INNER);

        $select->where(array(
            'restaurants.id' => $restaurant_id
        ));

        $restaurantAddress = $this->_tableGateway->selectWith($select)->current();
        return $restaurantAddress->getArrayCopy();
    }

    public function isRestaurantExists($rest_id = 0) {
       
        $res = $this->find(array(
                    'columns' => array(
                        'total' => new Expression('COUNT(id)')
                    ),
                    'where' => array(
                        'id' => $rest_id
                    )
                ))->current()->getArrayCopy();
        return $res['total'];
    }

    public function findByRestaurantId(array $options = array()) {
        $restaurant = $this->find($options)->current();
        return $restaurant;
    }

    public function getRestaurantCode($ids) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'rest_code',
                )
        );
        $where = New Where();
        $where->in('id', $ids);
        $select->where($where);
        // var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $points = $this->_tableGateway->selectWith($select)->toArray();

        return $points;
    }

    public function getRestaurantCountByCity($city_id) {
        
        $count = $this->find(array(
            'columns' => array(
                'total_count' => new Expression('COUNT(id)')
            ),
            'where' => array(
                'menu_available' => 1,
                'inactive' => 0,
                'closed' => 0,
                'city_id' => $city_id
            )
        ));
        $data = $count->current();
        if ($data) {
            $data = $data->getArrayCopy();
            $data = (int) $data['total_count'];
        } else {
            $data = 0;
        }
        return $data;
    }

    public function getRestaurantListByCity($city_id, $page = 1) {
        $limit = 2000;
        $tags = new Tags();
        $tagDetails = $tags->getTagDetailByName('dine-more');

        $tagId = 0;
        if (!empty($tagDetails)) {
            $tagId = $tagDetails[0]['tags_id'];
        }
        $offset = ((int) $page - 1) * $limit;
       
        $joins = array();
        $joins [] = array(
            'name' => array(
                'rs' => 'restaurant_stories'
            ),
            'on' => new Expression("(restaurants.id = rs.restaurant_id)"),
            'columns' => array(
                'story_id' => 'id'
            ),
            'type' => 'left'
        );
        $joins [] = array(
            'name' => array(
                'ri' => 'restaurant_images',
            ),
            'on' => new Expression("(restaurants.id = ri.restaurant_id)"),
            'columns' => array(
                'galary_id' => 'id',
            ),
            'type' => 'left'
        );
        $joins [] = array(
            'name' => array(
                'rt' => 'restaurant_tags'
            ),
            'on' => new Expression("(restaurants.id = rt.restaurant_id AND rt.tag_id=$tagId AND rt.status=1)"),
            'columns' => array(
                'tag_id'
            ),
            'type' => 'left'
        );
        $count = $this->find(array(
            'columns' => array(
                'id',
                'restaurant_name',
                'delivery',
                'takeout',
                'accept_cc_phone'
            ),
            'where' => array(
                'menu_available' => 1,
                'inactive' => 0,
                'closed' => 0,
                'city_id' => $city_id
            ),
            'joins' => $joins,
            'group' => 'ri.restaurant_id',
            'limit' => $limit,
            'offset' => $offset
        ));
        $data = $count->toArray();
        return $data;
    }

    /**
     * Returns delivery_area,delivery_geo, lat,long of the restaurants
     * @param int $res_id
     * @return array
     */
    public function getRestaurantDeliveryData($res_id = 0) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'id',
            'city_id',
            'delivery',
            'latitude',
            'longitude',
            'delivery_area',
            'delivery_geo'
        ));

        $select->where(array(
            'id' => $res_id
        ));
        $data = $this->_tableGateway->selectWith($select)->toArray();
        if ($data) {
            return $data[0];
        } else {
            return false;
        }
    }

    public function isAcceptCcPhoneEnabled($rest_id = 0) {
      
        $res = $this->find(array(
                    'columns' => array('accept_cc_phone'),
                    'where' => array('id' => $rest_id)
                ))->toArray();
        if ((count($res) > 0) && ($res[0]['accept_cc_phone'] == 1)) {
            return true;
        }
        return false;
    }

    public function getHispanicRestaurant($promotionId, $currentDate) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'restaurant_name',
            'price',
            'address',
            'zipcode',
            'city_id',
            'rest_code',
            'restaurant_image_name'
        ));
        $select->join(array('pre' => 'promotion_restaurant_event'), 'pre.restaurant_id = restaurants.id', array('restaurant_id'), $select::JOIN_INNER
        );

        $select->join(array('p' => 'promotions'), 'pre.promotionId=p.promotionId', array(
            'restaurantEventStartDate' => 'promotionStartDate',
            'restaurantEventId' => 'promotionId',
            'restaurantEventName' => 'promotionName',
            'restaurantEventDesc' => 'promotionDesc',
            'restaurantEventEndDate' => 'promotionEndDate',
            'restaurantEventStatus' => 'promotionStatus',
                ), $select::JOIN_INNER
        );
        $select->where(array(
            'pre.promotionId' => $promotionId,
        ));
        $select->where->greaterThanOrEqualTo('p.promotionStartDate', $currentDate);
        //$select->where->lessThanOrEqualTo('p.promotionEndDate', $currentDate);
        //var_dump($select->getSqlString($this->getPlatform('READ')));

        $openNightData = $this->_tableGateway->selectWith($select)->toArray();
        return $openNightData;
    }

    /**
     * Get restaurant primary image name
     * @param int $rest_id
     * @return string
     */
    public function getResPrimaryImgName($rest_id = 0) {
        //$rest_id = -1;
       
        $data = $this->find(array(
                    'columns' => array('restaurant_image_name'),
                    'where' => array('id' => $rest_id)
                ))->toArray();
        if (!empty($data) && strlen($data[0]['restaurant_image_name']) > 0) {
            return $data[0]['restaurant_image_name'];
        }
        return '';
    }

    public function getAllRestaurant($offset = 1, $limit = 10) {
        $options = array(
            'columns' => array(
                'id'
            ), 'offset' => $offset, 'limit' => $limit
        );
      
        $restaurants = $this->find($options)->toArray();
        return $restaurants;
    }

    public function getRestaurantCounts() {        
        $count = $this->find(array(
            'columns' => array(
                'total_count' => new Expression('COUNT(id)')
            )
        ));
        $data = $count->current();
        if ($data) {
            $data = $data->getArrayCopy();
            $data = (int) $data['total_count'];
        } else {
            $data = 0;
        }
        return $data;
    }

    public function getDineAndMoreTaggedRestaurants($tagId) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'id',
            'restaurant_name',
            'address',
            'restaurant_logo_name',
        ));
        $select->join(array(
            'rt' => 'restaurant_tags'
                ), 'rt.restaurant_id = restaurants.id', array(
            'tag_id'
                ), $select::JOIN_INNER);
        $select->where(array(
            'rt.status' => 1,
            'rt.tag_id' => $tagId
        ));
        $restaurantsList = $this->_tableGateway->selectWith($select)->toArray();
        return $restaurantsList;
    }

    public function dineAndMoreRestaurant($limit = FALSE, $order = FALSE, $restaurantIds = array()) {
        $tags = \MCommons\StaticFunctions::getServiceLocator()->get(Tags::class);
        $tagsDetails = $tags->getTagDetailByName("dine-more");
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'id',
            'restaurant_name',
            'res_code' => 'rest_code',
            'price',
            'allowed_zip',
            'restaurant_image_name',
            'minimum_delivery',
            'description',
            'city_id',
            'address',
            'zipcode',
            'has_delivery' => 'delivery',
            'has_takeout' => 'takeout',
            'has_dining' => 'dining',
            'has_menu' => 'menu_available',
            'has_reservation' => 'reservations',
            'price' => 'price',
            'delivery_area',
            'minimum_delivery',
            'delivery_charge',
            'latitude',
            'longitude',
            'accept_cc',
            'menu_without_price',
            'accept_cc_phone',
            'phone_no',
            'delivery_desc',
            'allowed_zip',
            'restaurant_image_name',
            'order_pass_through'
        ));

        $select->join(array(
            'rt' => 'restaurant_tags'
                ), 'restaurants.id = rt.restaurant_id', array(
            'tag_id', 'rest_short_url'
                ), $select::JOIN_INNER);

        $where = new Where();
        $where->NEST->equalTo('restaurants.closed', 0)->AND->equalTo('restaurants.inactive', 0)->AND->equalTo('rt.status', 1)->AND->equalTo('rt.tag_id', $tagsDetails[0]['tags_id'])->UNNEST;

        if (!empty($restaurantIds)) {
            $where->andPredicate(new \Zend\Db\Sql\Predicate\NotIn('restaurants.id ', $restaurantIds));
        }
        $select->where($where);
        if ($limit && $limit != 0) {
            $select->limit($limit);
        }
        $select->order(new \Zend\Db\Sql\Predicate\Expression('RAND()'));
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $userDineAndMoreRestaurant = $this->_tableGateway->selectWith($select)->toArray();
        return $userDineAndMoreRestaurant;
    }

    public function getFeaturedRestaurant($limit) {
        $joins = array();
        $joins [] = array(
            'name' => array(
                'ra' => 'restaurant_accounts'
            ),
            'on' => 'ra.restaurant_id = restaurants.id',
            'columns' => array(
                'account_status' => 'status'
            ),
            'type' => 'inner'
        );
        $options = array(
            'columns' => array(
                'id',
                'restaurant_name',
                'res_code' => 'rest_code',
                'price',
                'allowed_zip',
                'restaurant_image_name',
                'minimum_delivery',
                'description',
                'city_id',
                'address',
                'zipcode',
                'has_delivery' => 'delivery',
                'has_takeout' => 'takeout',
                'has_dining' => 'dining',
                'has_menu' => 'menu_available',
                'has_reservation' => 'reservations',
                'price' => 'price',
                'delivery_area',
                'minimum_delivery',
                'delivery_charge',
                'latitude',
                'longitude',
                'accept_cc',
                'menu_without_price',
                'accept_cc_phone',
                'phone_no',
                'delivery_desc',
                'allowed_zip',
                'restaurant_image_name',
                'order_pass_through'
            ),
            'where' => array(
                'restaurants.featured' => 1, 'restaurants.closed' => 0, 'restaurants.inactive' => 0
            ),
            'order' => new Expression('RAND()'),
            'limit' => $limit,
        );

        return $this->find($options)->toArray();
    }

    public function getRestaurantSocialUrls($restId) {
        $data = current($this->find(array(
                    'columns' => array('id',
                        'restaurant_logo_name',
                        'restaurant_name',
                        'facebook_url',
                        'twitter_url',
                        'gmail_url',
                        'pinterest_url',
                        'instagram_url',
                        'rest_code'),
                    'where' => array('id' => $restId)
                ))->toArray());
        return $data;
    }

    public function getAllRestaurantByCity($cityId, $tagId, $offset = 1, $limit = 10) {
        $joins = array();
        $joins [] = array(
            'name' => array(
                'rt' => 'restaurant_tags'
            ),
            'on' => new Expression("(restaurants.id = rt.restaurant_id AND rt.tag_id=$tagId AND rt.status=1)"),
            'columns' => array(
                'tag_id'
            ),
            'type' => 'left'
        );
        $options = array(
            'columns' => array(
                'id',
                'restaurant_name',
            ),
            'where' => array('city_id' => $cityId, 'closed' => 0, 'inactive' => 0),
            'joins' => $joins,
            'offset' => $offset, 'limit' => $limit
        );       
        $restaurants = $this->find($options)->toArray();
        return $restaurants;
    }

    public function getRestaurantCountsByCity($cityId) {       
        $count = $this->find(array(
            'columns' => array(
                'total_count' => new Expression('COUNT(id)')
            ),
            'where' => array('city_id' => $cityId, 'closed' => 0, 'inactive' => 0)
        ));
        $data = $count->current();
        if ($data) {
            $data = $data->getArrayCopy();
            $data = (int) $data['total_count'];
        } else {
            $data = 0;
        }
        return $data;
    }
    public function getTimeZoneResult($restaurantId){
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'id',
            'city_id'
        ));
        $select->join(array(
            'c' => 'cities'
                ), 'restaurants.city_id = c.id', array(
            'time_zone'
                ), $select::JOIN_INNER);
        $select->where(array(
            'restaurants.id' => $restaurantId
        ));
              
        return $this->_tableGateway->selectWith($select)->current();
    }

}
