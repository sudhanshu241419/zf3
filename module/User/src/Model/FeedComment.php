<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class FeedComment extends AbstractModel {

    public $id;
    public $feed_id;
    public $user_id;
    public $comment;
    public $status;
    public $created_on;
    protected $_db_table_name = 'User\Model\DbTable\FeedCommentTable';
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
}
