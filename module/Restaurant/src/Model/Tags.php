<?php

namespace Restaurant\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGatewayInterface;

class Tags extends AbstractModel {

    public $id;
    public $name;
    public $status;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->tableGateway = $tableGateway;
    }

    /*     * **************Get Tags detail from tags table using tag name************** */

    public function getTagDetailByName($tagName) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'tags_id' => 'id',
            'tag_name' => 'name'
        ));
        $select->where(array(
            'name' => $tagName,
            'status' => 1,
        ));
        $tagDetails = $this->_tableGateway->selectWith($select)->toArray();
        return $tagDetails;
    }

}
