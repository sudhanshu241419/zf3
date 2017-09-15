<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGatewayInterface;

class UserReviewImage extends AbstractModel {

    public $id;
    public $user_review_id;
    public $image;
    public $image_url;
    public $created_at;
    public $image_status;
    
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->tableGateway = $tableGateway;
    }

    public function insert($data) {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->insert($data);
        return $rowsAffected;
    }

    public function getImagesForUpload() {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $options = array(
            'columns' => array(
                'id',
                'image_name' => 'image_path',
                'user_review_id'
            ),
            'where' => array(
                'user_review_images.image_status' => 1
            ),
            'joins' => array(
                array(
                    'name' => array(
                        'ur' => 'user_reviews'
                    ),
                    'on' => 'ur.id = user_review_images.user_review_id',
                    'columns' => array(
                        'restaurant_id'
                    ),
                    'type' => 'left'
                )
            )
        );
        $rows = $this->find($options)->toArray();
        return $rows;
    }

    public function updateImageStatus($id) {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->update(array(
            'image_status' => 2
                ), array(
            'id' => $id
        ));
        return $rowsAffected;
    }

    public function delete() {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->delete(array('id' => $this->id));
        return $rowsAffected;
    }

    public function deleteImage() {

//        $writeGateway = $this->getDbTable ()->getWriteGateway ();
//        $sql = $writeGateway->getSql();
//        $delete = $sql->delete ();  
//        $where = array (
//            'user_review_id' => $this->user_review_id
//        );
//        $rowsAffected = $delete->where($where);
        $rowsAffected = $this->getDbTable()->getWriteGateway()->delete(array('user_review_id' => $this->user_review_id));
        //pr($delete,true);
        //pr($delete->getSqlString($this->getPlatform('WRITE')));
        //die;
        // $rowsAffected = $writeGateway->delete ( array ('user_review_id' => $this->user_review_id) );
        return $rowsAffected;
    }

    public function update($data) {
        $this->getDbTable()->getWriteGateway()->update($data, array('user_review_id' => $this->user_review_id));
        return true;
    }

    public function userTotalReviewImage($userId) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'images' => new \Zend\Db\Sql\Expression('count(user_review_images.id)'),
        ));
        $select->join(array(
            'rew' => 'user_reviews'
                ), 'user_review_id=rew.id', array(), $select::JOIN_INNER);
        $select->where(array(
            'rew.user_id' => $userId
        ));
        //pr($select->getSqlString($this->getPlatform('READ')),true);
        $userEatingHabitDetails = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);
        return $userEatingHabitDetails->toArray();
    }

}
