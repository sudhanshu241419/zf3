<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGatewayInterface;

class UserRestaurantImage extends AbstractModel {

    public $id;
    public $user_id;
    public $restaurant_id;
    public $image;
    public $image_url;
    public $created_on;
    public $updated_on;
    public $status;
    public $image_type;
    public $image_status;
    public $source;
    public $sweepstakes_status_winner = 0;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function createRestaurantImage($data = []) {
        if (empty($data)) {
            $data = $this->toArray();
        }
        $writeGateway = $this->_tableGateway;
        if (!$this->id) {
            $rowsAffected = $writeGateway->insert($data);
        } else {
            $rowsAffected = $writeGateway->update($data, []);
        }
        $lastInsertId = $this->_tableGateway->lastInsertValue;
        if ($rowsAffected >= 1) {
            $this->id = $lastInsertId;
            return $this->toArray();
        }
        return false;
    }

    public function delete() {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->delete(array('id' => $this->id, 'user_id' => $this->user_id));
        return $rowsAffected;
    }

    public function findSweepstakesImage($userId, $restId, $campaignsData) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'images' => new \Zend\Db\Sql\Expression('count(id)'),
        ));

        $select->where(array(
            'user_id' => $userId,
            'restaurant_id' => $restId,
            'source' => 1
        ));
        $select->where->between('created_on', $campaignsData[0]['start_on'], $campaignsData[0]['end_date']);
        //pr($select->getSqlString($this->getPlatform('READ')),true);
        $swi = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        return $swi[0]['images'];
    }

    public function updateCronOrder($id = false) {
        $this->getDbTable()->getWriteGateway()->update(array('cronUpdate' => 1), array(
            'id' => $id
        ));
        return true;
    }

    public function userImageStatus($galleryid = 0) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            "status", "updated_on"
        ));
        $select->where(array(
            'id' => $galleryid,
        ));

        //pr($select->getSqlString($this->getPlatform('READ')),true); 
        $gallery = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();
        //pr($gallery,true);
        return $gallery;
    }

}
