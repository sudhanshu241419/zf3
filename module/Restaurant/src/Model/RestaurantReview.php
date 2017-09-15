<?php

namespace Restaurant\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Expression;
use Zend\Db\TableGateway\TableGatewayInterface;

class RestaurantReview extends AbstractModel {

    public $id;
    public $restaurant_id;
    public $source;
    public $date;
    public $reviewer;
    public $reviews;
    public $sentiments;
    public $review_type;
    public $source_url;
    public $is_read;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->tableGateway = $tableGateway;
    }

    public function getRestaurantReviewCount($restaurant_id = 0) {
        $res = $this->find(array(
            'columns' => array(
                'total_count' => new Expression('COUNT(restaurant_id)'),
            ),
            'where' => array(
                'restaurant_id' => $restaurant_id,
                'status' => 1,
                'review_type' => 'N'
            )
        ));
        return $res->current();
    }

    public function restaurantTotalReview($restaurant_id = 0) {
        $res = $this->find(array(
            'columns' => array(
                'total_count' => new Expression('COUNT(restaurant_id)'),
            ),
            'where' => array(
                'restaurant_id' => $restaurant_id,
            )
        ));
        return $res->toArray()[0];
    }

    public function getRestaurantPositiveReview($restId = 0) {
        $res = $this->find(
                array(
                    'columns' => array(
                        'reviewer' => 'reviewer',
                        'reviews' => 'reviews',
                    ),
                    'where' => array(
                        'restaurant_id' => $restId,
                        'sentiments' => 'Positive',
                        'status' => 1
                    ),
                    'limit' => 10,
                )
        );
        return $res->toArray();
    }

    public function getReviews(array $options = []) {
        $reviews = $this->find($options)->toArray();
        return $reviews;
    }

}
