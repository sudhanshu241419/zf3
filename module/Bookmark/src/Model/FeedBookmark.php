<?php

namespace Bookmark\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

class FeedBookmark extends AbstractModel {

    public $id;
    public $feed_id;
    public $user_id;
    public $created_on;
    public $type;
    protected $_db_table_name = 'Bookmark\Model\DbTable\FeedBookmarkTable';
    protected $_primary_key = 'id';

    public function insert($data) {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->insert($data);
        if ($rowsAffected) {
            return true;
        } else {
            return false;
        }
    }

    public function getFeedBookmarkCountOfType($feed_id = 0, $type = false) {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        if ($type) {
            $res = $this->find(array(
                'columns' => array(
                    'total_count' => new Expression('COUNT(feed_id)')
                ),
                'where' => array(
                    'feed_id' => $feed_id,
                    'type' => $type
                ),
            ));
        }
        return $res->toArray();
    }

    public function delete() {

        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->delete(array(
            'id' => $this->id
                ));
        return $rowsAffected;
    }

}
