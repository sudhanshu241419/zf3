<?php

namespace Restaurant\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\TableGateway\TableGatewayInterface;

class Menu extends AbstractModel {

    public $id;
    public $pid;
    public $restaurant_id;
    public $item_name;
    public $cuisines_name;
    public $image_name;
    public $item_desc;
    public $selection_type;
    public $created_on;
    public $status;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public $bookmark_types = array(
        "li",
        "lo",
        "ti",
        "wi",
        "bt",
        "wl"
    );
    protected $_primary_key = 'id';

    public function restaurantMenues(array $options = array(), $menuSortOrder = false) {
        $select = new Select ();
        $path = "rest_code" . "/" . THUMB . "/" . "image_name";
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'category_id' => 'id',
            'pid',
            'category_name' => 'item_name',
            'category_desc' => 'item_desc',
            'item_image_url' => 'image_name',
            'online_order_allowed', 'cuisines_id', 'item_rank'
        ));

        $select->join(array(
            'mp' => 'menu_prices'
                ), 'mp.menu_id = menus.id', array(
            //'item_price' => new Expression ( 'IF(price IS NULL,0,round(price,2))' ),
            'price_id' => 'id',
            'price' => new Expression('IF(mp.price IS NULL,0,round(mp.price,2))'),
            'price_desc' => 'price_desc'
                ), $select::JOIN_LEFT);

        $select->join(array(
            'r' => 'restaurants'
                ), 'r.id = menus.restaurant_id', array(
            'rest_code', 'restaurant_name'
                ), $select::JOIN_LEFT);

        $select->where(array(
            'menus.restaurant_id' => $options ['columns'] ['restaurant_id'],
            'menus.user_deals' => $options ['columns'] ['user_deals'],
            'menus.status' => 1
        ));

        if ($menuSortOrder == 1) {
            $select->order('menus.item_rank ASC');
        }
        //requested by Rahul on 25-Feb-2016
//        $r_tags = new \Home\Model\RestaurantTag(); 
//        if($r_tags->hasTags($options ['columns'] ['restaurant_id'])){ 
//            $select->order('item_rank ASC');
//        }
//        var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $allmenues = $this->_tableGateway->selectWith($select);
        return $allmenues;
    }

    public function getTotalMenusCount($restaurant_id) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'menu_count' => new Expression('COUNT(*)')
        ))->where(array(
            'restaurant_id' => $restaurant_id
        ));
        $reviewCount = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);
        return $reviewCount;
    }

    public function getMenuDetail($menu_id) {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $options = array(
            'columns' => array(
                'item_name',
                'restaurant_id',
                'image_name',
                'id'
            ),
            'where' => array(
                'id' => $menu_id
            )
        );

        $menuDetail = $this->find($options)->current();
        return $menuDetail;
    }

    public function getTopTwentyMenues(array $options = array(), $item) {
        $select = new Select ();
        $path = "rest_code" . "/" . THUMB . "/" . "image_name";
        $select->from($this->getDbTable()->getTableName());
        $select->columns($item);

        $select->join(array(
            'mp' => 'menu_prices'
                ), 'mp.menu_id = menus.id', array(
            'item_price' => new Expression('IF(price IS NULL,0,round(price,2))')
                ), $select::JOIN_INNER);

        $select->where(array(
            'menus.restaurant_id' => $options ['columns'] ['restaurant_id'],
            'menus.status' => 1
                //'mp.price_type' => 1    //it will modify in feature
        ));

        $select->order('item_price DESC');
        $select->limit($options ['columns'] ['limit']);
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $topTwentyMenues = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);

        return $topTwentyMenues;
    }

    public function isFoodExists($food_id = 0, $rest_id = 0) {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $res = $this->find(array(
                    'columns' => array(
                        'total' => new Expression('COUNT(id)')
                    ),
                    'where' => array(
                        'id' => $food_id,
                        'restaurant_id' => $rest_id
                    )
                ))->current()->getArrayCopy();
        return $res ['total'];
    }

    public function getPopularMenues(array $options = array(), $item) {
        $select = new Select ();
        $path = "rest_code" . "/" . THUMB . "/" . "image_name";
        $select->from($this->getDbTable()->getTableName());
        $select->columns($item);

        $select->join(array(
            'mp' => 'menu_prices'
                ), 'mp.menu_id = menus.id', array(
            'item_price' => new Expression('IF(price IS NULL,0,round(price,2))')
                ), $select::JOIN_INNER);

        $select->where(array(
            'menus.restaurant_id' => $options ['columns'] ['restaurant_id'],
            'menus.status' => 1,
            'mp.price_type' => 1
        ));

        $select->order('item_price DESC');
        $select->limit($options ['columns'] ['limit']);

        $topTwentyMenues = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);

        return $topTwentyMenues;
    }

    public function restaurantMenuesSpecific(array $options = array()) {
        $selectedLocation = \MCommons\StaticOptions::getUserSession()->getUserDetail('selected_location', array());
        $cityModel = new \Home\Model\City(); //18848
        $cityId = isset($selectedLocation ['city_id']) ? $selectedLocation ['city_id'] : 18848;
        $cityDetails = $cityModel->cityDetails($cityId);
        $cityDateTime = \MCommons\StaticOptions::getRelativeCityDateTime(array(
                    'state_code' => $cityDetails [0] ['state_code']
        ));
        $currentDateTime = $cityDateTime->format('Y-m-d H:i:s');

        $userId = \MCommons\StaticOptions::getUserSession()->getUserId();

        $select = new Select ();
        $path = "rest_code" . "/" . THUMB . "/" . "image_name";
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'category_id' => 'id',
            'pid',
            'category_name' => 'item_name',
            'category_desc' => 'item_desc',
            'item_image_url' => 'image_name',
            'online_order_allowed', 'cuisines_id'
        ));

        $select->join(array(
            'r' => 'restaurants'
                ), 'r.id = menus.restaurant_id', array(
            'rest_code', 'restaurant_name'
                ), $select::JOIN_LEFT);

        $select->join(array(
            'rdc' => 'restaurant_deals_coupons'
                ), new Expression('rdc.menu_id = menus.id AND rdc.start_on <="' . $currentDateTime . '" AND rdc.end_date >="' . $currentDateTime . '"'), array(
            'menu_id',
            'deal_title' => 'title',
            'deal_description' => 'description',
                ), $select::JOIN_LEFT);

//        $select->join(array(
//            'ud' => 'user_deals'
//            ), 'rdc.id =ud.deal_id', array(
//            'deal_id'
//            ), $select::JOIN_LEFT);

        $select->where(array(
            'menus.restaurant_id' => $options ['columns'] ['restaurant_id'],
            //'menus.user_deals' => $options ['columns'] ['user_deals'],
            //'ud.user_id' => $userId,
            'menus.status' => 1,
            //'ud.deal_status' => 0,
            'rdc.status' => 1,
            "rdc.user_deals" => 0,
            'menus.user_deals' => 1,
                //'ud.availed' => 0
        ));

        //requested by Rahul on 25-Feb-2016
//        $r_tags = new \Home\Model\RestaurantTag(); 
//        if($r_tags->hasTags($options ['columns'] ['restaurant_id'])){ 
//            $select->order('item_rank ASC');
//        }
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $allmenues = $this->_tableGateway->selectWith($select);
        return $allmenues;
    }

    public function particularUserDealOnMenu(array $options = array()) {
        $selectedLocation = \MCommons\StaticOptions::getUserSession()->getUserDetail('selected_location', array());
        $cityModel = new \Home\Model\City(); //18848
        $cityId = isset($selectedLocation ['city_id']) ? $selectedLocation ['city_id'] : 18848;
        $cityDetails = $cityModel->cityDetails($cityId);
        $cityDateTime = \MCommons\StaticOptions::getRelativeCityDateTime(array(
                    'state_code' => $cityDetails [0] ['state_code']
        ));
        $currentDateTime = $cityDateTime->format('Y-m-d H:i:s');

        $userId = \MCommons\StaticOptions::getUserSession()->getUserId();

        $select = new Select ();
        $path = "rest_code" . "/" . THUMB . "/" . "image_name";
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'category_id' => 'id',
            'pid',
            'category_name' => 'item_name',
            'category_desc' => 'item_desc',
            'item_image_url' => 'image_name',
            'online_order_allowed', 'cuisines_id'
        ));

        $select->join(array(
            'r' => 'restaurants'
                ), 'r.id = menus.restaurant_id', array(
            'rest_code', 'restaurant_name'
                ), $select::JOIN_LEFT);

        $select->join(array(
            'rdc' => 'restaurant_deals_coupons'
                ), new Expression('rdc.menu_id = menus.id AND rdc.start_on <="' . $currentDateTime . '" AND rdc.end_date >="' . $currentDateTime . '"'), array(
            'menu_id',
            'deal_title' => 'title',
            'deal_description' => 'description',
                ), $select::JOIN_LEFT);

        $select->join(array(
            'ud' => 'user_deals'
                ), 'rdc.id =ud.deal_id', array(
            'deal_id'
                ), $select::JOIN_LEFT);

        $select->where(array(
            'menus.restaurant_id' => $options ['columns'] ['restaurant_id'],
            'menus.user_deals' => $options ['columns'] ['user_deals'],
            'ud.user_id' => $userId,
            'menus.status' => 1,
            'ud.deal_status' => 0,
            'rdc.status' => 1,
            'ud.availed' => 0
        ));

        //requested by Rahul on 25-Feb-2016
//        $r_tags = new \Home\Model\RestaurantTag(); 
//        if($r_tags->hasTags($options ['columns'] ['restaurant_id'])){ 
//            $select->order('item_rank ASC');
//        }
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $allmenues = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);

        return $allmenues;
    }

    public function restaurantMenuesSpecificPrice($menu_id = 0) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'category_id' => 'id'
        ));

        $select->join(array(
            'mp' => 'menu_prices'
                ), 'mp.menu_id = menus.id', array(
            'id',
            'value' => 'price',
            'desc' => 'price_desc'
                ), $select::JOIN_LEFT);
        $select->where(array('menu_id' => $menu_id));
        $allmenues = $this->_tableGateway->selectWith($select)->toArray();;
        return $allmenues;
    }

    public function restaurantMenuesNew(array $options = array()) {
        $select = new Select ();
        //$path = "rest_code" . "/" . THUMB . "/" . "image_name";
        $select->from($this->getDbTable()->getTableName());

        $select->columns(array(
            'category_id' => 'id',
            'pid',
            'category_name' => 'item_name',
            'category_desc' => 'item_desc',
            'item_image_url' => 'image_name',
            'online_order_allowed', 'cuisines_id'
        ));

        $select->join(array(
            'mp' => 'menu_prices'
                ), 'mp.menu_id = menus.id', array(
            //'item_price' => new Expression ( 'IF(price IS NULL,0,round(price,2))' ),
            'price_id' => 'id',
            'price' => new Expression('IF(mp.price IS NULL,0,round(mp.price,2))'),
            'price_desc' => 'price_desc'
                ), $select::JOIN_LEFT);

        $select->join(array(
            'r' => 'restaurants'
                ), 'r.id = menus.restaurant_id', array(
            'rest_code', 'restaurant_name'
                ), $select::JOIN_LEFT);

        $select->where(array(
            'menus.restaurant_id' => $options ['columns'] ['restaurant_id'],
            'menus.status' => 1
        ));
//pr($select->getSqlString($this->getPlatform('READ')),true);
        //requested by Rahul on 25-Feb-2016
//        $r_tags = new \Home\Model\RestaurantTag(); 
//        if($r_tags->hasTags($options ['columns'] ['restaurant_id'])){ 
//            $select->order('item_rank ASC');
//        }

        $allmenues = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);

        return $allmenues;
    }

    public function menuesDetails(array $options = array()) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'item_id' => 'id', 'item_name', 'item_desc', 'item_image_url' => 'image_name', 'online_order_allowed', 'user_deals'
        ));
        $select->join(array(
            'r' => 'restaurants'
                ), 'r.id = menus.restaurant_id', array(
            'rest_code', 'restaurant_name', 'available' => 'menu_available'
                ), $select::JOIN_LEFT);

        $select->where(array(
            'menus.id' => $options ['columns'] ['menu_id'],
            'menus.status' => 1
        ));
        $allmenues = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $allmenues;
    }

    /**
     * For gallery API /wapi/search/banners?reqtype=gallery
     * @param type $restId
     * @return null | ResultSetInterface
     */
    public function getRestaurantDishImages($restId) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(
                array(
                    'menu_id' => 'id',
                    'dish_name' => 'item_name',
                    'dish_image' => 'image_name',
                )
        );

        $select->where(
                array(
                    'restaurant_id' => $restId,
                    new \Zend\Db\Sql\Predicate\IsNotNull('image_name'),
                    new \Zend\Db\Sql\Predicate\Expression("image_name != ''"),
                    'menuType' => 1,
                    'status' => 1
                )
        );
        $select->limit(10);
        //pr($select->getSqlString($this->getPlatform()),1);
        $allmenues = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $allmenues;
    }

    public function getMenuStatus($options) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(
                $options['columns']
        );

        $select->where(
                $options['where']
        );

        //pr($select->getSqlString($this->getPlatform()),1);
        $allmenues = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $allmenues;
    }

}

//end of class
