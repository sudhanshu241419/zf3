<?php
namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Where;

class UserTransactions extends AbstractModel
{

    public $id;
    public $user_id;
    public $transaction_type;
    public $transaction_amount;
    public $transaction_date;
    public $remark;
    protected $_primary_key = 'id';
    protected $_db_table_name = 'User\Model\DbTable\UserTransactionsTable';
    
    /**
     * For debiting and creding money in/from user's wallet.
     * Sample $data = array('user_id'=>1,'transaction_type'=>'credit','transaction_amount'=> '12.45','category' => 1,'remark' => 'referral bonus');
     * @param array $data
     * @return type
     * @throws \Exception
     * Please do not modify this code without consulting dhirendra
     */
    public function doTransaction($data) {
        $check = $this->validate($data);
        if (!$check['valid']) {
            throw new \Exception($check['error']);
        }
        $data['transaction_date'] = date("Y-m-d H:i:s");
        if(!isset($data['category'])){
            $data['category'] = 1; //1=default, 2=referral credit, 3=redemption
        }

        /* @var $conn \Zend\Db\Adapter\Driver\Pdo\Connection */
        $conn = $this->getDbTable()->getWriteGateway()->getAdapter()->getDriver()->getConnection();
        $conn->beginTransaction();

        try {
            //add record in user_transactions table
            $rows_affected = $this->getDbTable()->getWriteGateway()->insert($data);
            if($rows_affected != 1){
                throw new \Exception('Could not update wallet balance.');
            }

            //update wallet_balance in users table
            $update = new \Zend\Db\Sql\Update();
            $users = new \User\Model\User();
            $update->table($users->getDbTable()->getTableName());
            $where = new Where();
            $where->equalTo('id', $data['user_id']);
            $update->where($where);
            if ($data['transaction_type'] == 'credit') {
                $expression = new \Zend\Db\Sql\Expression("`wallet_balance` + " . $data['transaction_amount']);
            } else {
                $expression = new \Zend\Db\Sql\Expression("`wallet_balance` - " . $data['transaction_amount']);
            }
            $update->set(array('wallet_balance' => $expression,'update_at'=> date("Y-m-d H:i:s")));
            $rows_affected = $users->getDbTable()->getWriteGateway()->updateWith($update);
            if($rows_affected != 1){
                throw new \Exception('Could not update wallet balance.');
            }

            /* @var $tableGateway \Zend\Db\TableGateway\TableGateway */
            //$tableGateway = $users->getDbTable()->getWriteGateway();
            $conn->commit();
        } catch (\Exception $exc) {
            $conn->rollback();
            if (isset($_REQUEST['DeBuG'])) {
                throw $exc;
            }
            return false;
        }
        return true;
    }

    private function validate($data){
        $valid = true;
        $error = '';
        $data_flag = isset($data['user_id']) && isset($data['transaction_type']) && isset($data['transaction_amount'])  && isset($data['remark']);
        if(!$data_flag ){
           $valid = false;
           $error .= "Missing required field(s).\n";
        }
        
        if($valid){
            if(!in_array($data['transaction_type'], array('debit','credit'))){
                $valid = false;
                $error .= "Invalid transaction type\n";
            }
        }
        
        if($valid){
           if(! is_numeric($data['transaction_amount'])){
               $valid = false;
               $error .= "Invalid transaction amount\n";
           } 
        }
        
        if($valid){
           if( !(strlen($data['remark']) > 0) ){
               $valid = false;
               $error .= "Invalid remark\n";
           } 
        }
        return array('valid' => $valid, 'error' => $error);
    }
    
    public function getUserReferralEarning($userId){
        $options = [
            'columns' => ['earning' => new \Zend\Db\Sql\Expression("SUM(transaction_amount)")],
            'where' => ['user_id' => $userId, 'category' => 2, 'transaction_type' => 'credit']
        ];
        $userEarning = $this->find($options)->toArray();
        return floatval($userEarning[0]['earning']);
    }
    
    public function insertRecord($data) {
        $check = $this->validate($data);
        if (!$check['valid']) {
            throw new \Exception($check['error']);
        }
        $data['transaction_date'] = date("Y-m-d H:i:s");
        if (!isset($data['category'])) {
            $data['category'] = 1; //1=default, 2=referral credit, 3=redemption
        }
        return $this->getDbTable()->getWriteGateway()->insert($data);
    }
    
        /**
     * For debiting and creding money in/from user's wallet.
     * Sample $data = array('user_id'=>1,'transaction_type'=>'credit','transaction_amount'=> '12.45','category' => 1,'remark' => 'referral bonus');
     * @param array $data
     * @return type
     * @throws \Exception
     * Please do not modify this code without consulting dhirendra
     */
    public function insertDataUpdateWallet($data) {
        $check = $this->validate($data);
        if (!$check['valid']) {
            throw new \Exception($check['error']);
        }
        $data['transaction_date'] = date("Y-m-d H:i:s");
        if(!isset($data['category'])){
            $data['category'] = 1; //1=default, 2=referral credit, 3=redemption
        }

        try {
            //add record in user_transactions table
            $rows_affected = $this->getDbTable()->getWriteGateway()->insert($data);
            if($rows_affected != 1){
                throw new \Exception('Could not update wallet balance.');
            }

            //update wallet_balance in users table
            $update = new \Zend\Db\Sql\Update();
            $users = new \User\Model\User();
            $update->table($users->getDbTable()->getTableName());
            $where = new Where();
            $where->equalTo('id', $data['user_id']);
            $update->where($where);
            if ($data['transaction_type'] == 'credit') {
                $expression = new \Zend\Db\Sql\Expression("`wallet_balance` + " . $data['transaction_amount']);
            } else {
                $expression = new \Zend\Db\Sql\Expression("`wallet_balance` - " . $data['transaction_amount']);
            }
            $update->set(array('wallet_balance' => $expression,'update_at'=> date("Y-m-d H:i:s")));
            $rows_affected = $users->getDbTable()->getWriteGateway()->updateWith($update);
            if($rows_affected != 1){
                throw new \Exception('Could not update wallet balance.');
            }
        } catch (\Exception $exc) {
            if (isset($_REQUEST['DeBuG'])) {
                throw $exc;
            }
            return false;
        }
        return true;
    }
    
    public function doTransactionOrder($data) {       
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->insert($data);
        //update wallet_balance in users table
        $update = new \Zend\Db\Sql\Update();
        $users = new \User\Model\User();
        $update->table($users->getDbTable()->getTableName());
        $where = new Where();
        $where->equalTo('id', $data['user_id']);
        $update->where($where);
        if ($data['transaction_type'] == 'credit') {
            $expression = new \Zend\Db\Sql\Expression("`wallet_balance` + " . $data['transaction_amount']);
        } else {
            $expression = new \Zend\Db\Sql\Expression("`wallet_balance` - " . $data['transaction_amount']);
        }
        $update->set(array('wallet_balance' => $expression, 'update_at' => $data['transaction_date']));
        $users->getDbTable()->getWriteGateway()->updateWith($update);
        return true;
    }

}

