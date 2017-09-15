<?php

namespace Restaurant\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\TableGateway\TableGatewayInterface;
use Zend\Db\Sql\Select;

class MenuAddons extends AbstractModel {

    public $id;
    public $menu_id;
    public $addon_id;
    public $addon_option;
    public $selection_type;
    public $price;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function menuAddons(array $options = array()) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'id',
            'name' => 'addon_option',
            'price'
        ));

        $select->join(array(
            'a' => 'addons'
                ), 'a.id = menu_addons.addon_id', array(
            'category_id' => 'id',
            'category' => 'addon_name'
                ), $select::JOIN_LEFT);

        $select->where(array(
            'menu_addons.menu_id' => $options ['columns'] ['menu_id'],
            'menu_addons.status' => 1
        ));
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $menuaddons = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);

        return $menuaddons;
    }

    public function reorderMenuAddons($menuAddonsId) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'addon_price_description' => 'price_description', 'selection_type', 'addon_description' => 'description'
        ));

        $select->where(array(
            'id' => $menuAddonsId
                )
        );
        $reorderAddonSetting = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->current();
        return $reorderAddonSetting;
    }

}