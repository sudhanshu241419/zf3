<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\TableGateway\TableGatewayInterface;

class UserCheckin extends AbstractModel {

    public $id;
    public $user_id;
    public $restaurant_id;
    public $message;
    public $created_at;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function insert($data) {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->insert($data);
        $lastInsertId = $writeGateway->getAdapter()->getDriver()->getLastGeneratedValue();
        return $lastInsertId;
    }

    public function getTotalUsercheckin($userId) {
        $select = new Select();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'total_checkin' => new Expression('COUNT(id)')
        ));
        $select->where(array(
            'user_id' => $userId
        ));
        $totalCheckin = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);
        return $totalCheckin->toArray();
    }

    public function getCheckinActivity($restaurantId, $userId, $bookmarkType) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'user_id'
        ));
        $select->where(array(
            'restaurant_id' => $restaurantId,
            'user_id' => $userId,
        ));

        $userTip = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);

        return $userTip->toArray();
    }

    public function getUsercheckin($userId) {
        $select = new Select();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array('id', 'restaurant_id'));
        $select->where(array('user_id' => $userId, 'assignMuncher' => '0'));
        $totalCheckin = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);
        return $totalCheckin->toArray();
    }

    public function updateMuncher($data) {
        $this->getDbTable()->getWriteGateway()->update($data, array(
            'id' => $this->id
        ));
        return true;
    }

    /*  this function is used to get the winners data
     *  No parameter required
     *  find data where winner status has 4
     *  five tables are require to get the data(user_checkin,checkin_images,user_reviews,user_review_images,user_restaurant_image)
     */

    public function getWinnersUserRestaurantIds() {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'restaurant_id',
            'user_id', 'created_at', 'type' => new Expression("'userCheckin'")
        ));
        $select->join(array('chi' => 'checkin_images'), 'chi.checkin_id = user_checkin. id', array('id', 'image_path'), $select::JOIN_INNER
        );
        $select->where(array(
            'chi.sweepstakes_status_winner' => '2',
        ));
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $openNightData = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $openNightData;
    }

}
