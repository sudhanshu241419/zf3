<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGatewayInterface;

class UserMenuReview extends AbstractModel {

    public $id;
    public $user_review_id;
    public $menu_id;
    public $image_name;
    public $liked;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function getRatedFoodItem(array $options = array()) {
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());

        $select->columns(array(
            'menu_image' => 'image_name',
            'menu_id'
        ));

        $select->join(array(
            'm' => 'menus'
                ), 'user_menu_reviews.menu_id = m.id', array(
            'menu_name' => 'item_name'
                ), $select::JOIN_LEFT);

        $select->where(array(
            'user_menu_reviews.user_review_id' => $options['columns']['review_id'],
            'm.restaurant_id' => $options['columns']['restaurant_id'],
            'm.status' => 1
        ));
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $ratedFoodItemDetail = $this->getDbTable()
                ->setArrayObjectPrototype('ArrayObject')
                ->getReadGateway()
                ->selectWith($select);
        return $ratedFoodItemDetail;
    }

    public function addItemsReview() {
        $data = $this->toArray();
        $writeGateway = $this->getDbTable()->getWriteGateway();
        if (!$this->id) {
            $rowsAffected = $writeGateway->insert($data);
        } else {
            $rowsAffected = $writeGateway->update($data, array(
                'id' => $this->id
            ));
        }

        $lastInsertId = $writeGateway->getAdapter()
                ->getDriver()
                ->getLastGeneratedValue();

        if ($rowsAffected >= 1) {
            $this->id = $lastInsertId;
            return $this->toArray();
        }
        return false;
    }

    public function insert($data) {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->insert($data);
        $lastInsertId = $writeGateway->getAdapter()
                ->getDriver()
                ->getLastGeneratedValue();
        return $lastInsertId;
    }

    public function getImagesForUpload() {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $options = array(
            'columns' => array(
                'id',
                'image_name',
                'user_review_id',
                'menu_id'
            ),
            'where' => array(
                'user_menu_reviews.image_status' => 1
            ),
            'joins' => array(
                array(
                    'name' => array(
                        'ur' => 'user_reviews'
                    ),
                    'on' => 'ur.id = user_menu_reviews.user_review_id',
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

    public function update($data) {
        $this->getDbTable()->getWriteGateway()->update($data, array('user_review_id' => $this->user_review_id, 'menu_id' => $this->menu_id));
        return true;
    }

}
