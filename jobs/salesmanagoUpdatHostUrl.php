<?php
use MCommons\StaticOptions;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS['application']->getServiceManager());
$salesManago=new Salesmanago();
$joins = [];
$user = new \User\Model\User();
$select = new Select();
$where = New Where();
$select->from($user->getDbTable()->getTableName());
$select->columns(array('email'));
$select->join ( array ('rs' => 'restaurant_servers'), 'users.id=rs.user_id', array ('id','restaurant_id','user_id'), $select::JOIN_INNER);
$select->join(array('r'=>'restaurants'), 'rs.restaurant_id=r.id',array('restaurant_name'),$select::JOIN_INNER);
$select->group('rs.user_id');
$select->order('rs.id asc');
//var_dump($select->getSqlString($user->getPlatform('READ')));
//die;
$userEmail = $user->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select)->toArray();

$response = $salesManago->updateHostUrl($userEmail);
pr($response);