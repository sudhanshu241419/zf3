<?php

namespace MCommons\Controller;

use Zend\Mvc\Controller\AbstractRestfulController as RestfulController;
use Zend\Stdlib\RequestInterface as Request;
use MCommons\StaticFunctions;

class AbstractRestfulController extends RestfulController {

    /**
     * Check if request has certain content type
     *
     * @param Request $request            
     * @param string|null $contentType            
     * @return bool
     */
    public $userAgent;

    public function requestHasContentType(Request $request, $contentType = '') {
        $headerContentType = $request->getHeaders()->get('content-type');
        if (!$headerContentType) {
            $userAgent = $request->getHeaders()->get('User-Agent');
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
            foreach ($this->contentTypes[$contentType] as $contentTypeValue) {
                if (stripos($contentTypeValue, $requestedContentType) === 0) {
                    return true;
                }
            }
        }
        return false;
    }
    
    public function getGlobalConfig() {
        $config = $this->getServiceLocator('config');
        return $config;
    }

    protected $_sm_vars = [];

    public function getTable() {
        $args = func_get_args();
        switch (count($args)) {
            case 1:
                $table = $args[0];
                break;
            case 2:
                $table = $args[0] . "\\Model\\DbTable\\" . $args[1];
                break;
            default:
                throw new \Exception("Invalid DbTable Name: ");
        }
        if (!isset($this->_sm_vars[$table])) {
            $this->_sm_vars[$table] = $this->getServiceLocator()->get($table);
        }
        return $this->_sm_vars[$table];
    }

    public function sendError($message = '', $code = 500) {
        if (is_array($message)) {
            $message = isset($message['message']) ? $message['message'] : $message;
        }
        return StaticOptions::getErrorResponse($this->getServiceLocator(), $message, $code);
    }

    public function getQueryParams($key, $default = false) {
        return $this->getRequest()->getQuery($key, $default);
    }

    /**
     *
     * @return \Auth\Model\UserSession
     */
    public function getUserSession()
    {
        return StaticFunctions::getUserSession();
    }

    public function isMobile() {
        return (bool) $this->getQueryParams('mob', false);
    }

    public function getStripeKey() {
        $sKey = NULL;
        $config = $this->serviceLocator->get('config');
        if (isset($config['constants']['stripe']['secret_key'])) {
            $sKey = $config['constants']['stripe']['secret_key'];
        }
        return $sKey;
    }

    public final function options() {
        $response = $this->getResponse();
        $headers = $response->getHeaders();

        $headers->addHeaderLine('Allow', implode(',', [
            'GET',
            'PATCH',
            'PUT',
            'DELETE'
        ]));
        return $response;
    }

    public function getBaseUrl() {
        $uri = $this->getRequest()->getUri();
        return sprintf('%s://%s', $uri->getScheme(), $uri->getHost());
    }

    public function getServiceLocator($service) {
        $sl = $this->getEvent()->getApplication()->getServiceManager();
        return $sl->get($service);
    }

}
