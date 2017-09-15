<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class UserReservation extends AbstractModel {

    public $id;
    public $user_id;
    public $restaurant_id;
    public $seat_type_id;
    public $party_size;
    public $reserved_on;
    public $user_instruction;
    public $restaurant_comment;
    public $time_slot;
    public $meal_slot;
    public $status;
    public $restaurant_name;
    public $first_name;
    public $last_name;
    public $phone;
    public $email;
    public $reserved_seats;
    public $receipt_no;
    public $is_reviewed = 0;
    public $host_name;
    public $user_ip;
    public $order_id;
    public $city_id = NULL;
    public $is_modify = 0;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function getUserReservation(array $options = array()) {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        return $this->find($options)->toArray();
    }

    public function getAllReservation(array $options = array()) {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $reservations = $this->find($options)->toArray();
        return $reservations;
    }

    public function getCurrentNotificationReservation($userId = null, $currentDate) {
        $output = [];
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(['id', 'reserved_on']);
        $select->where->equalTo('user_id', $userId);
        $select->where->greaterThan('time_slot', $currentDate);
        $select->order('time_slot DESC');
        $select->limit(1);
        $currentNotification = $this->_tableGateway->selectWith($select)->toArray();
        if (!empty($currentNotification)) {
            $output ['order_created_time'] = 'available';
        }
        return $output;
    }

    public function getReservationDetailForMob($options) {
        $select = new Select ();
        $select->from($this->_tableGateway->getTable());

        $select->columns(array(
            'reservation_id' => 'id',
            'order_id',
            'receipt_no',
            'restaurant_id',
            'restaurant_name',
            'reservation_member_count' => 'party_size',
            'reservation_date' => 'time_slot',
            'reservation_created_on' => 'reserved_on',
            'first_name',
            'last_name',
            'phone',
            'email',
            'user_instruction',
            'status',
            'is_reviewed',
            'review_id',
            'user_id'
        ));

        $select->join(
                array('r' => 'restaurants'), 'r.id=user_reservations.restaurant_id', array('rest_code', 'restaurant_image_name', 'address', 'zipcode', 'city_id', 'inactive', 'closed'), $select::JOIN_LEFT
        );

        $select->join(
                array('c' => 'cities'), 'c.id=r.city_id', array('city_name', 'state_code'), $select::JOIN_LEFT
        );

        $where = new Where ();
        if (!empty($options ['reservationIds'])) {
            $where->in('user_reservations.id', $options ['reservationIds']);
            $invitationBy = "1";
        } else {
            $where->equalTo('user_reservations.user_id', $options ['userId']);
            $invitationBy = "0";
        }
        $where->in('user_reservations.status', $options ['status']);
        //$where->greaterThanOrEqualTo( 'user_reservations.time_slot', $options ['currentDate'] );
        $select->where($where);

        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $reservationData = $this->_tableGateway->selectWith($select)->toArray();

        $response = array();

        if ($reservationData) {
            $reservationID = array_unique(array_map(function ($i) {
                        return $i['reservation_id'];
                    }, $reservationData));

            $i = 0;
            $response = array();
            foreach ($reservationData as $key => $value) {

                $response[] = $value;
            }
            $upcommingStatus = false;
        }
        return $response;
    }

    public function getTotalUserReservations($userId) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'total_reservation' => new Expression('COUNT(id)'), 'user_id'
        ));
        $select->where(array(
            'user_id' => $userId
        ));
        $totalOrder = $this->_tableGateway->selectWith($select)->toArray();
        return $totalOrder;
    }

}
