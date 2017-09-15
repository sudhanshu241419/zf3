<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Zend\Db\TableGateway\TableGatewayInterface;

class UserReview extends AbstractModel {

    public $id;
    public $user_id;
    public $restaurant_id;
    public $review_for;
    public $on_time = 0;
    public $fresh_prepared = 0;
    public $temp_food = 2;
    public $as_specifications = 0;
    public $taste_test;
    public $services;
    public $noise_level;
    public $rating = 0;
    public $order_again = 0;
    public $come_back = 0;
    public $review_desc;
    public $created_on;
    public $status = 0;
    public $approved_by = 0;
    public $sentiment;
    public $order_id = 0;
    public $replied = 0;
    public $restaurant_response;
    public $userReviewForRestaurant;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->tableGateway = $tableGateway;
    }

    public function getReviews(array $options = array()) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());

        $select->columns(array(
            'id',
            'restaurant_id',
            'user_id',
            'review_desc',
            'review_for',
            'sentiment',
            'on_time',
            'fresh_prepared',
            'as_specifications',
            'temp_food',
            'taste_test',
            'order_again',
            'rating',
            'created_on'
        ));

        $select->join(array(
            'uri' => 'user_review_images'
                ), 'uri.user_review_id = user_reviews.id', array(
            'image_path' => new Expression('if(uri.image is NULL,"' . REST_DEFAULT_IMAGE . '",uri.image)')
                ), $select::JOIN_LEFT);

        $select->join(array(
            'u' => 'users'
                ), 'u.id = user_reviews.user_id', array(
            'first_name',
            'last_name',
            'display_pic_url' => new Expression('if(display_pic_url is NULL,"' . REST_DEFAULT_IMAGE . '",display_pic_url)'),
            'created_at'
                ), $select::JOIN_LEFT);

        $select->join(array(
            'ua' => 'user_addresses'
                ), 'ua.user_id = user_reviews.user_id', array(
            'city' => new Expression('if(city is NULL,"",city)')
                ), $select::JOIN_LEFT);

        $select->where(array(
            'user_reviews.restaurant_id' => $options ['columns'] ['restaurant_id'],
            'user_reviews.status' => 1
        ));

        //pr($select->getSqlString($this->getPlatform('READ')),1);

        $reviewDetail = $this->_tableGateway->selectWith($select);
        return $reviewDetail;
    }

    public function getTotalUserRreview(array $options = array()) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'total_review' => new Expression('COUNT(user_reviews.id)')
        ));
        $select->where(array(
            'user_reviews.user_id' => $options ['columns'] ['user_id'],
            'user_reviews.status' => 1
        ));
        $select->group('user_reviews.user_id');
        //var_dump($select->getSqlString($this->getPlatform('READ')));
        $totalReview = $this->_tableGateway->selectWith($select);
        return $totalReview;
    }

    public function createReview() {
        $data = $this->toArray();
        if (!$this->id) {
            $rowsAffected = $this->_tableGateway->insert($data);
        } else {
            $rowsAffected = $this->_tableGateway->update($data, array(
                'id' => $this->id
            ));
        }

        $lastInsertId = $this->_tableGateway->lastInsertValue;

        if ($rowsAffected >= 1) {
            $this->id = $lastInsertId;
            return $this->toArray();
        }
        return false;
    }

    public function insert($data) {
        $rowsAffected = $this->_tableGateway->insert($data);
        $lastInsertId = $this->_tableGateway->lastInsertValue;
        return $lastInsertId;
    }

    public function getUserReviews($user_id) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());

        $select->columns(array(
            'id',
            'user_id',
            'restaurant_id',
            'review_for',
            'created_on',
            'review_desc',
            'order_id',
            'rating',
            'status'
        ));

        $select->join(array(
            'r' => 'restaurants'
                ), 'r.id = user_reviews.restaurant_id', array(
            'restaurant_name',
            'rest_code'
                ), $select::JOIN_LEFT);

        $select->join(array(
            'umr' => 'user_menu_reviews'
                ), 'umr.user_review_id = user_reviews.id', array(
            'image_path' => 'image_name'
                ), $select::JOIN_LEFT);
        $select->where(array(
            'user_reviews.user_id' => $user_id,
            'user_reviews.status' => 1
        ));

        //var_dump($select->getSqlString($this->getPlatform('READ')));

        $reviewDetail = $this->_tableGateway->selectWith($select);
        return $reviewDetail->toArray();
    }

    public function getUserReviewDetail($user_id, $review_id) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());

        $select->columns(array(
            'id',
            'user_id',
            'restaurant_id',
            'review_for',
            'created_on',
            'review_desc',
            'order_id',
            'rating',
            'status',
            'on_time',
            'fresh_prepared',
            'as_specifications',
            'temp_food',
            'taste_test',
            'order_again',
            'services',
            'noise_level',
            'come_back'
        ));

        $select->join(array(
            'r' => 'restaurants'
                ), 'r.id = user_reviews.restaurant_id', array(
            'restaurant_name',
            'rest_code'
                ), $select::JOIN_LEFT);

        $select->join(array(
            'uod' => 'user_order_details'
                ), 'uod.user_order_id = user_reviews.order_id', array(
            'item_id' => 'id',
            'item_name' => 'item'
                ), $select::JOIN_LEFT);

        $select->join(array(
            'umr' => 'user_menu_reviews'
                ), 'umr.user_review_id = user_reviews.id', array(
            'image_path' => 'image_name'
                ), $select::JOIN_LEFT);
        $select->where(array(
            'user_reviews.user_id' => $user_id,
            'user_reviews.id' => $review_id,
            'user_reviews.status' => 1
        ));

        //var_dump($select->getSqlString($this->getPlatform('READ')));die;

        $reviewDetail = $this->_tableGateway->selectWith($select);
        return $reviewDetail->toArray();
    }

    public function getUserMenuReviews($user_id) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());

        $select->columns(array(
            'id',
            'user_id',
            'restaurant_id',
            'review_for',
            'created_on',
            'review_desc',
            'order_id',
            'rating',
            'status'
        ));

        $select->join(array(
            'umr' => 'user_menu_reviews'
                ), 'umr.user_review_id = user_reviews.id', array(
            'image_path' => 'image_name'
                ), $select::JOIN_LEFT);

        $select->join(array(
            'm' => 'menues'
                ), 'm.id = umr.menu_id', array(
            'menu_name' => 'item_name'
                ), $select::JOIN_LEFT);

        $select->where(array(
            'user_reviews.user_id' => $user_id,
            'm.id = umr.menu_id',
            'user_reviews.status' => 1
        ));

        //var_dump($select->getSqlString($this->getPlatform('READ')));

        $reviewDetail = $this->_tableGateway->selectWith($select);
        return $reviewDetail->toArray();
    }

    public function getUserTotalRreview($userId) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'total_review' => new Expression('COUNT(user_reviews.id)')
        ));
        $select->where(array(
            'user_reviews.user_id' => $userId,
            'user_reviews.status' => array(0, 1, 2)
        ));

        $totalReview = $this->_tableGateway->selectWith($select)->current();
        //pr($select->getSqlString($this->getPlatform('READ')),true);
        return $totalReview;
    }

    public function getRestaurantReviewCount($restaurant_id = 0) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'total_count' => new Expression('COUNT(restaurant_id)')
        ));
        $select->where(array(
            'restaurant_id' => $restaurant_id,
            'status' => 1
        ));
        $totalReview = $this->_tableGateway->selectWith($select)->current();
        $this->userReviewForRestaurant = $totalReview;
        return $totalReview;
    }

    public function getAllUserReview(array $options = array()) {
        $reservations = $this->find($options)->toArray();
        return $reservations;
    }

    public function updateReview() {
        $data = array(
            'replied' => $this->replied,
        );

        $writeGateway = $this->getDbTable()->getWriteGateway();
        $dataUpdated = array();
        if ($this->id == 0) {
            throw new \Exception("Invalid review ID provided", 500);
        } else {
            $dataUpdated = $this->_tableGateway->update($data, array(
                'id' => $this->id
            ));
        }
        if ($dataUpdated) {
            return true;
        } else {
            return false;
        }
    }

    public function delete() {
        $data = array(
            'status' => 3
        );
        $rowsAffected = $this->_tableGateway->update($data, array('id' => $this->id));

        if ($rowsAffected) {
            return true;
        } else {
            return false;
        }
    }

    public function UserMenuTotalReview($userId) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array());
        $select->join(array(
            'rew' => 'user_menu_reviews'
                ), 'user_review_id=user_reviews.id', array('id', 'image_name'), $select::JOIN_INNER);
        $select->join(array(
            'res' => 'restaurants'
                ), 'res.id=user_reviews.restaurant_id', array(), $select::JOIN_INNER);
        $select->where(array(
            'user_reviews.user_id' => $userId
        ));
        //pr($select->getSqlString($this->getPlatform('READ')),true);
        $userEatingHabitDetails = $this->_tableGateway->selectWith($select);
        $menuImages = $userEatingHabitDetails->toArray();
        $totalMenuImages = 0;
        if (count($menuImages) > 0) {
            foreach ($menuImages as $key => $val) {
                if (!empty($val['image_name'])) {
                    $totalMenuImages += 1;
                }
            }
        }
        return (int) $totalMenuImages;
    }

    public function getUserAllReview($userId = false) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array('id', 'restaurant_id'));
        $select->where(array('user_id' => $userId, 'assignMuncher' => '0', 'status' => '1'));
        $select->where->notEqualTo('review_desc', '');
        $totalCheckin = $this->_tableGateway->selectWith($select);
        return $totalCheckin->toArray();
    }

    public function updateMuncher($data) {
        $this->_tableGateway->update($data, array('id' => $this->id));
        return true;
    }

    public function updateCronOrder($id = false) {
        $this->_tableGateway->update(array('cronUpdate' => 1), array('id' => $id));
        return true;
    }

    /*  this function is used to get the winners data
     *  No parameter required
     *  find data where winner status has 4
     *  five tables are require to get the data(user_reviews,user_review_images,user_restaurant_image)
     */

    public function getWinnersUserRestaurantIds() {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'restaurant_id',
            'user_id', 'type' => new Expression("'userReview'")
        ));
        $select->join(array('uri' => 'user_review_images'), 'uri.user_review_id = user_reviews. id', array('id', 'created_at', 'image_path' => new Expression('image_url')), $select::JOIN_INNER
        );
        $select->where(array(
            'uri.image_status' => '4',
        ));
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $openNightData = $this->_tableGateway->selectWith($select)->toArray();
        return $openNightData;
    }

    public function getMenuWinnersUserRestaurantIds() {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'restaurant_id',
            'user_id', 'type' => new Expression("'userReview'"), 'created_at' => new Expression('user_reviews.created_on')
        ));
        $select->join(array('uri' => 'user_menu_reviews'), 'uri.user_review_id = user_reviews. id', array('id', 'image_path' => new Expression('image_name')), $select::JOIN_INNER
        );
        $select->join(array('r' => 'restaurants'), 'r.id= user_reviews.restaurant_id', array('rest_code'), $select::JOIN_INNER
        );
        $select->where(array(
            'uri.image_status' => '4',
        ));
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $openNightData = $this->_tableGateway->selectWith($select)->toArray();
        //$path=WEB_URL.APP_PUBLIC_PATH.USER_IMAGE_UPLOAD .strtolower($restDetail['rest_code']) . DS . 'reviews' . DS;
        if (count($openNightData) > 0) {

            foreach ($openNightData as $key => $val) {
                $path = WEB_URL . USER_IMAGE_UPLOAD . strtolower($val['rest_code']) . DS . 'reviews' . DS . $val['image_path'];
                $openNightData[$key]['image_path'] = $path;
            }
        }
        return $openNightData;
    }

    public function getRestaurantReviewsRatings($restId, $restStartDate, $restEndDate) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'user_id',
            'rating',
        ));
        $select->join(array(
            'rs' => 'restaurant_servers'
                ), 'rs.user_id = user_reviews.user_id', array(
                ), $select::JOIN_INNER);
        $where = new Where ();
        $where->equalTo('user_reviews.restaurant_id', $restId);
        $where->equalTo('user_reviews.status', '1');
        $where->between('user_reviews.approved_date', $restStartDate, $restEndDate);
        $select->where($where);
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $data = $this->_tableGateway->selectWith($select)->toArray();
        $rating = [];
        $positive = 0;
        $negative = 0;
        if (!empty($data)) {
            foreach ($data as $value) {
                if ($value['rating'] >= 3) {
                    $positive ++;
                } else {
                    $negative ++;
                }
            }
        }
        $rating['positive'] = $positive;
        $rating['negative'] = $negative;
        return $rating;
    }

    public function getRestaurantTotalReviews($restId, $restStartDate, $restEndDate) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'total' => new Expression('COUNT(user_id)')
        ));
        $where = new Where();
        $where->equalTo('user_reviews.restaurant_id', $restId);
        $where->equalTo('user_reviews.status', '1');
        $where->between('user_reviews.approved_date', $restStartDate, $restEndDate);
        $select->where($where);
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $data = $this->_tableGateway->selectWith($select)->toArray();
        if (!empty($data)) {
            return $data[0]['total'];
        } else {
            return '';
        }
    }

    public function reviewStatus($reviewId = 0) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTableName());
        $select->columns(array("status"));
        $select->where(array('id' => $reviewId, 'status' => 1));
        $review = $this->_tableGateway->selectWith($select)->toArray();

        return $review;
    }

//    public function getReviews(array $options = []) {
//        $reviews = $this->find($options)->toArray();
//        return $reviews;
//    }

}
