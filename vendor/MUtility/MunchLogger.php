<?php

namespace MUtility;

use MCommons\StaticFunctions;

class MunchLogger {

    const LEVEL_CRITICAL = 1;
    const LEVEL_ERROR = 2;
    const LEVEL_WARNING = 3;
    const LEVEL_INFO = 4;
    const LEVEL_VALIDATION = 5;

    /**
     *
     * @var array mapping from numbers to log-levels 
     */
    protected static $levels = [
        1 => 'CRITICAL',
        2 => 'ERROR',
        3 => 'WARNING',
        4 => 'INFO',
        5 => 'VALIDATION',
    ];

    /**
     * Write info about thrown exception into file or db
     * @param \Exception $e Thrown Exception object (if not in try catch block, create a new Exception)
     * @param int $level log level
     * @param String $msg Error Message
     * @level : int (Error level) 1=CRITICAL, 2=ERROR, 3=WARNING, 4=INFO, 5=VALIDATION
     */
    public static function writeLog(\Exception $e, $level = 3, $msg = '') {
        if($level==10){
            return;
        }
        $config = StaticFunctions::getServiceLocator()->get('config');
        if (!isset($config['constants']['logging']['enabled']) || !$config['constants']['logging']['enabled']) {
            return false;
        }
        if ($config['constants']['logging']['log_to_file']) {
            $errorMessage = self::getFileErrMessage($e, $level, $msg);
            return self::writeToFile($errorMessage);
        }
        if ($config['constants']['logging']['log_to_db']) {
            $data = self::getDbErrMessage($e, $level, $msg);
            return self::writeToDb($data);
        } else {
            return FALSE;
        }
    }

    /**
     * writes error message to LOG_DIR . munch_api.log file
     * @param string $errorMessage
     * @return boolean
     */
    private static function writeToFile($errorMessage) {
        if (!file_exists(LOG_DIR)) {
            if (!mkdir(LOG_DIR, 0755, true)) {
                $data = [
                    'level' => 1,
                    'message' => 'Unable to create log directory',
                    'origin' => 'MunchLogger.php',
                ];
                self::writeToDb($data);
            }
        }
        $file_handle = fopen(LOG_FILE_NAME, 'a+');
        fwrite($file_handle, $errorMessage);
        fclose($file_handle);
        return true;
    }    
    
      /**
     * Write info about thrown exception into file
     * @param \Exception $e Thrown Exception object (if not in try catch block, create a new Exception)
     * @param int $level log level
     * @param String $msg Error Message
     * @level : int (Error level) 1=CRITICAL, 2=ERROR, 3=WARNING, 4=INFO, 5=VALIDATION
     */
    public static function writeLogSalesmanago(\Exception $e, $level = 3, $msg = '') {
        if($level==10){
            return;
        }
        $config = StaticFunctions::getServiceLocator()->get('config');
        if (!isset($config['constants']['logging']['enabled']) || !$config['constants']['logging']['enabled']) {
            return false;
        }
        if ($config['constants']['logging']['log_to_file']) {
            $errorMessage = self::getFileErrMessage($e, $level, $msg);
            return self::writeToSalesMangoLog($errorMessage);
        }else {
            return FALSE;
        }
    }
    
        
    /**
     * writes error message for salesmanago to LOG_DIR . salesmanago.log file
     * @param string $errorMessage
     * @return boolean
     */
    private static function writeToSalesMangoLog($errorMessage) {
        if (!file_exists(LOG_DIR)) {
            if (!mkdir(LOG_DIR, 0755, true)) {
                $data = array(
                    'level' => 1,
                    'message' => 'Unable to create log directory',
                    'origin' => 'MunchLogger.php',
                );
                self::writeToDb($data);
            }
        }
        $file_handle = fopen(SALESMANGO_LOG_FILE_NAME, 'a+');
        fwrite($file_handle, $errorMessage);
        fclose($file_handle);
        return true;
    }

    /**
     * Insert data in log_error table
     * @param array $data required keys 'level', 'message', 'origin'
     * @return boolean
     */
    private static function writeToDb($data) {
        $err_log = new \Search\Model\LogError();
        return $err_log->saveErrorLog($data);
    }

    private static function getFileErrMessage(\Exception $e, $level, $msg) {
        if ($msg == '') {
            $msg = $e->getMessage();
        }
        $errorMessage = 'date:' . date('Y-m-d H:i:s') . '|Message:' . $msg . '|Level:' . self::$levels[$level];
        $errorMessage .= '|File:' . $e->getFile() . '|Line:' . $e->getLine() . "\n";
        return $errorMessage;
    }

    /**
     * 
     * @param \Exception $e
     * @param int $level
     * @param string $msg
     * @return array with keys: level, message, origin
     */
    private static function getDbErrMessage(\Exception $e, $level, $msg) {
        if ($msg == '') {
            $msg = $e->getMessage();
        }
        return [
            'level' => $level,
            'message' => $msg,
            'origin' => 'File:' . $e->getFile() . '|Line:' . $e->getLine(),
        ];
    }

    /**
     * Insert data in log_error table
     * @param array $data
     * @return boolean
     */
    public static function writeAbandonedCartToDb($data) {
        $err_log = new \Search\Model\AbandonedCart();
        return $err_log->saveAbandonedCart($data);
    }
    
    public static function writeLogCleverTap(\Exception $e, $level = 3, $msg = '') {
        if($level==10){
            return;
        }
        $config = StaticFunctions::getServiceLocator()->get('config');
        if (!isset($config['constants']['logging']['enabled']) || !$config['constants']['logging']['enabled']) {
            return false;
        }
        if ($config['constants']['logging']['log_to_file']) {
            $errorMessage = self::getFileErrMessage($e, $level, $msg);
            return self::writeToFileCleverTap($errorMessage);
        }
        if ($config['constants']['logging']['log_to_db']) {
            $data = self::getDbErrMessage($e, $level, $msg);
            return self::writeToDb($data);
        } else {
            return FALSE;
        }
    }

    /**
     * writes error message to LOG_DIR . munch_api.log file
     * @param string $errorMessage
     * @return boolean
     */
    private static function writeToFileCleverTap($errorMessage) {
        if (!file_exists(LOG_DIR)) {
            if (!mkdir(LOG_DIR, 0755, true)) {
                $data = [
                    'level' => 1,
                    'message' => 'Unable to create log directory',
                    'origin' => 'MunchLogger.php',
                ];
                self::writeToDb($data);
            }
        }
        $file_handle = fopen(LOG_FILE_NAME_CLEVER, 'a+');
        fwrite($file_handle, $errorMessage);
        fclose($file_handle);
        return true;
    }    

}
