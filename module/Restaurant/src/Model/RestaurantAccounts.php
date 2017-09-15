<?php
namespace Restaurant\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class RestaurantAccounts extends AbstractModel
{

    public $id;

    public $restaurant_id;

    public $city_id;

    public $user_name;

    public $user_password;

    public $name;

    public $email;

    public $phone;

    public $mobile;

    public $role;

    public $titke;

    public $created_on;

    public $updated_at;

    public $status;

    protected $_db_table_name = 'Restaurant\Model\DbTable\RestaurantAccountTable';

    public function getRestaurantAccountDetail(array $options = array())
    {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        return $this->find($options)->current();
    }

    public function fetchCount($rest_id=0,$email="",$password="")
    {
        $options = array(
        	'columns'=>array('count' => new \Zend\Db\Sql\Expression('COUNT(*)')),
            'where'=>array('restaurant_id'=>$rest_id,'email'=>$email,'user_password'=>$password)
        );
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        return $this->find($options)->current()->getArrayCopy();
    }
    public function checkRestaurantForMail($restaurantId,$flag=NULL){
        //return false;
        if (!empty($restaurantId)){
            $record = $this->getRestaurantAccountDetail(array(
                    'columns' => array(
                            'status',
                            'email'
                    ),
                    'where' => array(
                            'restaurant_id' => $restaurantId
                    )
            ));
            if($record['status']==1 && !empty($record['email'])){
                $restaurantSetting = new RestaurantNotificationSettings();
                $sendMail = $restaurantSetting->getRestaurantSettingStatus($restaurantId,$flag);
                return  $sendMail;
            }
        }
    }
    public function getAccountDetail($restId) {
        $select = new Select();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'restaurant_id',
            'created_on',
        ));
        $where = new Where ();
        $where->equalTo('restaurant_id', $restId);
        $where->equalTo('status', 1);
        $select->where($where);
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $records = $this->getDbTable()
                        ->setArrayObjectPrototype('ArrayObject')
                        ->getReadGateway()
                        ->selectWith($select)->current();
        return $records;
    }

}