<?php
namespace Restaurant\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;

class Feature extends AbstractModel 
{
	public $id;
	public $features;
	public $feature_type;
	public $search_status;
	public $status;
	public $features_key;
        
	
	protected $_tableGateway;
    
        public function __construct(TableGatewayInterface $tableGateway) {
            parent::__construct($tableGateway);
            $this->_tableGateway = $tableGateway;
        }
        
        public function getFeatures(array $options = array()) 
        {
            $select = new Select ();
            $select->from ( $this->_tableGateway->getTable() );
            $select->columns ( array ( 'id','features','feature_type', 'features_key' ) );	
            $featureData = $this->_tableGateway->selectWith ( $select );
            return $featureData;
	}
}