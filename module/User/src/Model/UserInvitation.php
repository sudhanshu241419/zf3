<?php

namespace User\Model;

use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\TableGatewayInterface;

class UserInvitation extends AbstractModel {

    public $id;
    public $user_id;
    public $to_id;
    public $restaurant_id;
    public $message;
    public $msg_status;
    public $reservation_id;
    public $friend_email;
    public $user_type;
    public $created_on;

    const ACCEPTED = 1;
    const INVITE = 0;

    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function getUserInvitation(array $options = array()) {
        return $this->find($options)->current();
    }

    public function createInvitation() {
        $data = $this->toArray();

        if (!$this->id) {
            $rowsAffected = $this->_tableGateway->insert($data);
            $lastInsertId = $this->_tableGateway->lastInsertValue;
        } else {
            if ($this->msg_status == 2) {
                $this->msg_status = 0;
                $rowsAffected = $this->_tableGateway->update(array(
                    'msg_status' => 0
                        ), array(
                    'reservation_id' => $this->reservation_id,
                    'id' => $this->id,
                    'friend_email' => $this->friend_email
                ));

                $lastInsertId = $this->id;
            } else {
                return false;
            }
        }

        if ($rowsAffected >= 1) {

            return $lastInsertId;
        }
        return false;
    }

    public function updateReservationInvitaion($id) {
        $rowsAffected = $writeGateway->update(array(
            'msg_status' => 1
                ), array(
            'id' => $id
        ));
        if ($rowsAffected) {
            return true;
        } else {
            return false;
        }
    }

    public function getAllUserInvitation(array $options = array()) {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $UserInvitation = $this->find($options)->toArray();
        return $UserInvitation;
    }

    public function getInvitationAdmitted($userId, $msg_status = null) {
        $allInvitationAdmitted = array();
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->where->equalTo('to_id', $userId);
        $select->where->in('msg_status', $msg_status);
        $select->order('created_on desc');
        $allInvitationAdmitted = $this->getDbTable()
                ->setArrayObjectPrototype('ArrayObject')
                ->getReadGateway()
                ->selectWith($select)
                ->toArray();
        return $allInvitationAdmitted;
    }

    public function getInvitationStatusCheck($msg_status = null) {
        $data = array(
            'msg_status' => $msg_status
        );

        $writeGateway = $this->getDbTable()->getWriteGateway();
        $dataUpdated = array();
        if ($this->id == 0) {
            throw new \Exception("Invalid reservation invitation ID provided", 500);
        } else {
            $dataUpdated = $writeGateway->update($data, array(
                'id' => $this->id
            ));
        }

        if ($dataUpdated) {
            return true;
        } else {
            return false;
        }
    }

    public function getFindInvitee($reservationId) {
        $data = "";
        $userModel = new User();
        $allInvitation = $this->getAllUserInvitation(array(
            'columns' => array(
                'to_id',
                'friend_email'
            ),
            'where' => array(
                'reservation_id' => $reservationId
            )
        ));

        if (count($allInvitation) > 0) {
            $count = count($allInvitation);
            $i = 1;
            foreach ($allInvitation as $key => $value) {

                if ($value['to_id'] == '0') {
                    if ($i == $count - 1)
                        $data .= $value['friend_email'] . ' and ';
                    else
                        $data .= $value['friend_email'] . ', ';
                } else {
                    if ($i == $count - 1)
                        $data .= $userModel->getName($value['to_id']) . ' and ';
                    else
                        $data .= $userModel->getName($value['to_id']) . ', ';
                }
                $i ++;
            }
            $data = substr($data, 0, - 2);
            return $data;
        } else {
            return "";
        }
    }

    public function saveInviteResponse($data) {
        $token = base64_decode($data);
        $tokenArray = explode('##', $token);
        $email = $tokenArray[0];
        $user_id = (int) $tokenArray[1];
        $msg_status = (int) $tokenArray[2];
        $reservation_id = (int) $tokenArray[3];
        // $record = $this->getAllUserInvitation(array('columns'=>array('to_id','friend_email'),
        // 'where'=>array('reservation_id'=>$reservationId)));
        $conditions = ""; // array("user_id = ? and reservation_id = ? and friend_email = ?", $user_id, $reservation_id,$email) ;
        $record = self::find_one($conditions);

        if ($record) {
            if ($record->msg_status == 0 && $record->to_id == 0) {
                $conditions = array(
                    "email = ?",
                    $email
                );
                $user = User::find_one($conditions);
                $record->update_attributes(array(
                    'id' => $record->id,
                    'to_id' => $user->id
                ));
                return 'updated';
            }
        }
    }

    public function getReservationWithCount($userId, $currentUserId) {
        /*
         * $record = "select * FROM user_reservation_invitation AS m INNER JOIN user_reservations ON user_reservations.id = m.reservation_id WHERE (m.to_id =$user_id and m.user_id=$current_user_id) OR (m.to_id =$current_user_id and m.user_id=$user_id)";
         */
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->join(array(
            'rs' => 'user_reservations'
                ), 'rs.id =  user_reservation_invitation.reservation_id', array(), $select::JOIN_INNER);
        $where = new Where();
        $where->NEST->equalTo('user_reservation_invitation.to_id', $userId)->AND->equalTo('user_reservation_invitation.user_id', $currentUserId)->UNNEST->OR->NEST->equalTo('user_reservation_invitation.to_id', $currentUserId)->AND->equalTo('user_reservation_invitation.user_id', $userId)->AND->in('user_reservation_invitation.msg_status', array('0', '1'))->UNNEST;

        $select->where($where);

        //pr($select->getSqlString($this->getPlatform('READ')),1); die();
        $totalInvitation = $this->getDbTable()
                ->setArrayObjectPrototype('ArrayObject')
                ->getReadGateway()
                ->selectWith($select)
                ->toArray();
        if ($totalInvitation) {
            return count($totalInvitation);
        } else {
            return 0;
        }
    }

    public function getReservationInviCount($userId, $currentUserId = false) {
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->columns(array(
            'total_reservation' => new \Zend\Db\Sql\Expression('COUNT(id)'), 'user_id'
        ));
//		$select->where (array(
//				'to_id' => $userId,
//                                'user_id' => $currentUserId,
//                'msg_status' => array('0','1')
//		));
        //$select->where->NEST->equalTo('msg_status','0')->OR->equalTo('msg_status', '1')->UNNEST->NEST->AND->NEST->equalTo('to_id', $userId)
        //      ->AND->equalTo('user_id',$currentUserId)->UNNEST
        //     ->OR->NEST->equalTo('to_id', $currentUserId)->AND->equalTo('user_id', $userId)->UNNEST->UNNEST;

        $select->where->NEST->equalTo('msg_status', '1')->UNNEST->NEST->AND->NEST->equalTo('to_id', $userId)
                ->AND->equalTo('user_id', $currentUserId)->UNNEST
                ->OR->NEST->equalTo('to_id', $currentUserId)->AND->equalTo('user_id', $userId)->UNNEST->UNNEST;


        //pr($select->getSqlString($this->getPlatform('READ')),1);

        $totalOrder = $this->getDbTable()
                        ->setArrayObjectPrototype('ArrayObject')
                        ->getReadGateway()
                        ->selectWith($select)->toArray();
        $inviReserve = $totalOrder[0]['total_reservation'];
        return $inviReserve;
    }

    public function getFindInviteeNew($reservationId) {
        $data = "";
        $userModel = new User();
        $allInvitation = $this->getAllUserInvitation(array(
            'columns' => array(
                'to_id',
                'friend_email'
            ),
            'where' => array(
                'reservation_id' => $reservationId,
                'msg_status' => array(
                    0,
                    1
                )
            )
        ));

        if (count($allInvitation) > 0) {
            $count = count($allInvitation);
            $i = 1;
            foreach ($allInvitation as $key => $value) {

                if ($value['to_id'] == '0') {
                    if ($i == $count - 1)
                        $data .= $value['friend_email'] . ' and ';
                    else
                        $data .= $value['friend_email'] . ', ';
                } else {
                    if ($i == $count - 1)
                        $data .= $userModel->getName($value['to_id']) . ' and ';
                    else
                        $data .= $userModel->getName($value['to_id']) . ', ';
                }
                $i ++;
            }
            $data = substr($data, 0, - 2);
            return $data;
        } else {
            return "";
        }
    }

    public function getAcceptedInvition($reservationId) {
        $data = "";
        $userModel = new User();
        $allInvitation = $this->getAllUserInvitation(array(
            'columns' => array(
                'id',
                'to_id',
                'friend_email'
            ),
            'where' => array(
                'reservation_id' => $reservationId,
                'msg_status' => 1
            )
        ));
        return count($allInvitation);
    }

    public function update($data, $where) {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->update($data, $where);
        return true;
    }

    public function declineReservationInvitaion($id) {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->update(array(
            'msg_status' => 2
                ), array(
            'id' => $id
        ));
        if ($rowsAffected) {
            return true;
        } else {
            return false;
        }
    }

    public function getInvitation(array $options = array()) {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        return $this->find($options)->toArray();
    }

    public function updateReservationData($to_Id = false, $email_id = false) {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->update(array(
            'to_id' => $to_Id,
            'user_type' => 1
                ), array(
            'friend_email' => $email_id
        ));
        if ($rowsAffected) {
            return true;
        } else {
            return false;
        }
    }

    public function updateCron($id = false) {
        $this->getDbTable()->getWriteGateway()->update(array('cronUpdate' => 1), array(
            'id' => $id
        ));
        return true;
    }

}
