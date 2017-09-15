<?php

namespace Rest\Authenticators;

use Zend\Http\PhpEnvironment\Request;
use Zend\Json\Json;
use Zend\ServiceManager\ServiceLocatorInterface;
use MCommons\StaticFunctions;


class Authenticate {

    protected $_request;
    protected $_serviceLocator;
    protected $_tokenValue;

    /**
     *
     * @var array
     */
    protected $contentTypes = array(
        self::CONTENT_TYPE_JSON => array(
            'application/hal+json',
            'application/json'
        )
    );

    const CONTENT_TYPE_JSON = 'json';

    /**
     *
     * @var int From Zend\Json\Json
     */
    protected $jsonDecodeType = Json::TYPE_ARRAY;

    public function authenticateRequest(Request $request, ServiceLocatorInterface $serviceLocator) {
        $this->_request = $request;
        $this->_serviceLocator = $serviceLocator;

        $this->_tokenValue = $this->getTokenValue();
       
        if ($this->_request->getMethod() == "OPTIONS") {
            return true;
        }
        
        if ($this->isValidToken()) {
            $this->updateTokenTimestamp();
            return true;
        }
        
        $this->deleteToken();
        return false;
    }

    protected function getTokenValue() {
        $config = $this->_serviceLocator->get('Config');        
        $tokenText = isset($config ['api_standards']) && isset($config ['api_standards'] ['token_text']) ? $config ['api_standards'] ['token_text'] : 'token';
        $tokenValue = false;
        $route = $this->_serviceLocator->get('router');           
        $routeName = $route->match($this->_request)->getMatchedRouteName();
         
        if ($routeName == "user-login-google") {
            $encodedState = $this->_request->getQuery('state', false);
            $decodedState = base64_decode($encodedState);
            $info = explode(":::", $decodedState);
            if ($info && isset($info[0])) {
                return $info[0];
            }
        }
        if ($routeName == "user-login-google-contact") {
            return $tokenValue = $this->_request->getQuery('state', false);
        }
        if ($routeName == "user-login-microsoft") {
            return $tokenValue = $this->_request->getQuery('state', false);
        }
        /* if($routeName == "user-login-yahoo-contact"){
          return $tokenValue =$this->_request->getQuery ( 'state', false );
          } */
        
        switch ($this->_request->getMethod()) {
            case "GET" :
            case "DELETE" :
                $tokenValue = $this->_request->getQuery($tokenText, false);
                break;
            case "POST" :
                $postData = $this->getPostData();
                if (isset($postData) && isset($postData [$tokenText]) && $postData [$tokenText]) {
                    $tokenValue = $postData [$tokenText];
                } else {
                    $tokenValue = false;
                }
                break;
            case "PUT" :
                // Parse the PUT content to extract the token
                $vars = $this->getPutData();
                $tokenValue = isset($vars [$tokenText]) ? $vars [$tokenText] : false;
                break;
            default :
                break;
        }
        
        return $tokenValue;
    }
//    protected function isValidToken() {
//        $tokenValue = $this->_tokenValue;
//        if (! $tokenValue) {
//                return false;
//        }
//        $redisCache = StaticFunctions::getRedisCache();
//        if($redisCache) {
//                $tokenDetails = false;
//                if($redisCache->hasItem($tokenValue)) {
//                        $tokenDetailsData = $redisCache->getItem($tokenValue);
//                        $tokenDetails = $this->_serviceLocator->get(\Auth\Model\Auth::class);
//                        $tokenDetails->exchangeArray($tokenDetailsData);
//                }
//        } else {
//               
//                $tokenDetails = $this->_serviceLocator->get(\Auth\Model\Auth::class);
//                $tokenDetails = $tokenDetails->findToken2($tokenValue);
//        }
//        if (! $tokenDetails) {
//                return false;
//        }
//
//        $userDetails = @unserialize ( $tokenDetails->user_details );
//        if (! $userDetails) {
//                $userDetails = array ();
//        }
//        $userDetails ['user_id'] = ( int ) $tokenDetails->user_id;
//        $userSessionModel =  $this->_serviceLocator->get(\Auth\Model\UserSession::class);
//        pr($tokenDetails,1);
//        $userSessionModel->exchangeArray ( $tokenDetails );
//        StaticFunctions::setUserSession ( $userSessionModel );
//        $ttl = ( int ) $tokenDetails->ttl;
//        $lastUpdateTimestamp = ( int ) $tokenDetails->last_update_timestamp;
//
//        // Add according to server time rather than city dependent time
//        $currentTimestamp = ( int ) StaticFunctions::getDateTime ()->getTimestamp ();
//        if (($lastUpdateTimestamp + $ttl) < $currentTimestamp) {
//                return false;
//        }
//        return true;
//    }
    protected function isValidToken() {
        $tokenValue = $this->_tokenValue;
        if (!$tokenValue) {
            return false;
        }
        $redisCache = StaticFunctions::getRedisCache();
        $authModel = $this->_serviceLocator->get(\Auth\Model\Auth::class);
        if ($redisCache) {
            $tokenDetails = false;
            if ($redisCache->hasItem($tokenValue)) {
                $tokenDetailsData = $redisCache->getItem($tokenValue);                
                $tokenDetails = $this->_serviceLocator->get(\Auth\Model\Auth::class);
                $tokenDetails->exchangeArray($tokenDetailsData);
                $tokenDetails->user_id = (isset($tokenDetails->user_id) && !empty($tokenDetails->user_id))?$tokenDetails->user_id:NULL;
                $authModel->exchangeArray($tokenDetails->toArray());                
                $userDetails = (isset($tokenDetails->user_details) && !empty($tokenDetails->user_details))?@unserialize ( $tokenDetails->user_details ):array();    
                $ttl = (int) $tokenDetails->ttl;
                $lastUpdateTimestamp = (int) $tokenDetails->last_update_timestamp;
            }
        } else {     
            
            $tokenDetails = $authModel->findToken2($tokenValue);  
            $tokenDetails[0]['user_id'] = (isset($tokenDetails[0]['user_id']) && !empty($tokenDetails[0]['user_id']))?$tokenDetails[0]['user_id']:NULL;
            $authModel->exchangeArray($tokenDetails);
            $userDetails = (isset($tokenDetails[0]['user_details']) && !empty($tokenDetails[0]['user_details']))?unserialize($tokenDetails[0]['user_details']):array();    
            $ttl = (int) $tokenDetails[0]['ttl'];
            $lastUpdateTimestamp = (int) $tokenDetails[0]['last_update_timestamp'];
        }
      
        if (!$tokenDetails) {
            return false;
        }       
       $userSessionModel =  $this->_serviceLocator->get(\Auth\Model\UserSession::class);
       
       $userSessionModel->exchangeArray ( $tokenDetails[0] );
       
       StaticFunctions::setUserSession($userSessionModel);

        // Add according to server time rather than city dependent time
        $currentTimestamp = (int) StaticFunctions::getDateTime()->getTimestamp();
        if (($lastUpdateTimestamp + $ttl) < $currentTimestamp) {
            return false;
        }       
        return true;
    }

    protected function updateTokenTimestamp() {
        $tokenValue = $this->_tokenValue;
        $redisCache = StaticFunctions::getRedisCache();
        $authModel =$this->_serviceLocator->get(\Auth\Model\Auth::class);
        if ($redisCache) {
            
            $tokenModel = $authModel->findToken($this->_tokenValue);
            if (!$tokenModel) {
                $tokenModel = $authModel;
            }
            $tokenModel->token = $tokenValue;
            $tokenModel->last_update_timestamp = time();
            $tokenModel->save();
            return true;
        }
    
        $updated = $authModel->update(
                array('last_update_timestamp' => time()), 
                array('token' => $this->_tokenValue)
                );
        if (!$updated) {
            return false;
        }
        return true;
    }

    protected function deleteToken() {
        $auth = $this->_serviceLocator->get(\Auth\Model\Auth::class);        
        return $auth->delete(['token' => $this->_tokenValue]);        
    }

    private function getPostData() {
        if ($this->requestHasContentType(self::CONTENT_TYPE_JSON)) {
            $data = Json::decode($this->_request->getContent(), $this->jsonDecodeType);
        } else {
            $data = $this->_request->getPost()->toArray();
        }
        return $data;
    }

    private function requestHasContentType($contentType = '') {
        /**
         * @var $headerContentType \Zend\Http\Header\ContentType
         */
        $headerContentType = $this->_request->getHeaders()->get('content-type');
        if (!$headerContentType) {
            $userAgent = $this->_request->getHeaders()->get('User-Agent');
            if ($userAgent) {
                $userAgentData = $userAgent->getFieldValue();
                if (preg_match('/(?i)msie [1-9]/', strtolower($userAgentData)) && $contentType == self::CONTENT_TYPE_JSON) {
                    return true;
                }
            }
            return false;
        }

        $requestedContentType = $headerContentType->getFieldValue();
        if (strstr($requestedContentType, ';')) {
            $headerData = explode(';', $requestedContentType);
            $requestedContentType = array_shift($headerData);
        }
        $requestedContentType = trim($requestedContentType);
        if (array_key_exists($contentType, $this->contentTypes)) {
            foreach ($this->contentTypes [$contentType] as $contentTypeValue) {
                if (stripos($contentTypeValue, $requestedContentType) === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Process the raw body content from PUT request
     *
     * If the content-type indicates a JSON payload, the payload is immediately
     * decoded and the data returned. Otherwise, the data is passed to
     * parse_str(). If that function returns a single-member array with a key
     * of "0", the method assumes that we have non-urlencoded content and
     * returns the raw content; otherwise, the array created is returned.
     *
     * @param mixed $request        	
     * @return object string array
     */
    protected function getPutData() {
        $content = $this->_request->getContent();
        // JSON content? decode and return it.
        if ($this->requestHasContentType(self::CONTENT_TYPE_JSON)) {
            return Json::decode($content, $this->jsonDecodeType);
        }

        parse_str($content, $parsedParams);

        // If parse_str fails to decode, or we have a single element with key
        // 0, return the raw content.
        if (!is_array($parsedParams) || (1 == count($parsedParams) && isset($parsedParams [0]))) {
            return $content;
        }
        return $parsedParams;
    }

}
