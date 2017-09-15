<?php

namespace MUtility;

class MunchException extends \Exception {
    
    /**
     * @var String Custom Message
     */
    private $customMessage;
    
    /**
     * @param String $customMessage*/
    public function __construct($errMessage,$customMessage,$errCode = 0) {
        if($errCode != 0) {
            parent::__construct($errMessage, $errCode);
        } else {
            parent::__construct($errMessage);
        }
        $this->customMessage = $customMessage;
    }
    
    public function getCustomMessage(){
        return $this->customMessage;
    }

}
