<?php

namespace Rest;

use Zend\Mvc\MvcEvent;
use MCommons\StaticFunctions;
use User\Functions\UserFunctions;

/**
 * Module Management for rest api
 *
 * @author tirth
 * @namespace Rest
 */
class Module {

    protected $_namespace = __NAMESPACE__;
    protected $_dir = __DIR__;

    /**
     * Higher the priority in the events they are executed earlier
     * Negative priorites allowed
     *
     * @param MvcEvent $e        	
     */
    public function getServiceConfig(){
       
        return[
            'factories' => [ 
            UserFunctions::class => function( $container){
                return new UserFunctions();
            },
            \MUtility\MunchLogger::class=> function($container){
                return new \MUtility\MunchLogger();
            }
                                               
             ],
        ];
    }
    public function onBootstrap(MvcEvent $e) {
        $eventManager = $e->getApplication()->getEventManager();
        $sharedEventManager = $eventManager->getSharedManager();
        
        $sharedEventManager->attach(\Zend\Mvc\Controller\AbstractRestfulController::class, MvcEvent::EVENT_DISPATCH, [$this, 'addServiceLocator'], 999);
        $sharedEventManager->attach(\Zend\Mvc\Controller\AbstractRestfulController::class, MvcEvent::EVENT_DISPATCH, [$this, 'getUserAgent'], 998);
        $sharedEventManager->attach(\Zend\Mvc\Controller\AbstractRestfulController::class, MvcEvent::EVENT_DISPATCH, [$this, 'checkAndAddRedis'], 997);

        $sharedEventManager->attach(\Zend\Mvc\Controller\AbstractRestfulController::class, MvcEvent::EVENT_DISPATCH, [$this, 'authenticate'], 995);
        
        $sharedEventManager->attach(\Zend\Mvc\Controller\AbstractRestfulController::class, MvcEvent::EVENT_DISPATCH, [$this, 'postProcess'], - 100);
        $sharedEventManager->attach(\Zend\Mvc\Controller\AbstractRestfulController::class, MvcEvent::EVENT_DISPATCH, [$this, 'memCacheServiceSetting'], 994);

        //Error Handling
        $sharedEventManager->attach(\Zend\Mvc\Application::class, MvcEvent::EVENT_DISPATCH_ERROR, [$this,'errorProcess'], 899);
//
//        /**
//         * Initialize Constants before moving forward
//         */
//        $sharedEvents->attach('Zend\Mvc\Controller\AbstractRestfulController', MvcEvent::EVENT_DISPATCH, array(
//            $this,
//            'initConstants'
//                ), 999);
    }

    /**
     * Adding service locator to the static options so that its accessible form anywhere
     *
     * @param MvcEvent $e        	
     */
    public function addServiceLocator(MvcEvent $e) {
        return StaticFunctions::setServiceLocator($e->getApplication()->getServiceManager());
    }
    
    public function checkAndAddRedis(MvcEvent $e) {
        $redis_cache = false;
        $sl = $e->getApplication()->getServiceManager();
        $config = $sl->get('config');
        
        if (class_exists('\Redis') && isset($config['constants']['redis']) && !empty($config['constants']['redis']) && $config['constants']['redis']['enabled']) {
            $redisConfig = $config['constants']['redis'];
            try {

                $redis_options = new \Zend\Cache\Storage\Adapter\RedisOptions();
                $redis_options->setServer($redisConfig);
                $redis_options->setLibOptions(array(
                    \Redis::OPT_SERIALIZER => \Redis::SERIALIZER_PHP
                ));

                $redis_cache = new \Zend\Cache\Storage\Adapter\Redis($redis_options);
                $space = $redis_cache->getTotalSpace();
            } catch (\Exception $ex) {
                $redis_cache = false;
            }
        }
        
        $sl->setService("RedisCache", $redis_cache);
    }
    
    public function memCacheServiceSetting(MvcEvent $e)
    {
        $sl          = $e->getApplication()->getServiceManager();
        $conf        = $sl->get('config');
        $memCfgArray = $conf['memcached']['servers'];
        //pr($memCfgArray,1);
        if( count($memCfgArray) )
        {
            try {
                $memOptions = new \Zend\Cache\Storage\Adapter\MemcachedOptions();
                $memOptions->setServers($memCfgArray);
                $memOptions->setTtl(7200);
           
                $objMemcache = new \Zend\Cache\Storage\Adapter\Memcached($memOptions);
            } 
            catch (\Exception $ex) {
                $objMemcache = false;
            }
        }
        
        $sl->setService("memCachedObject", $objMemcache);
    }
    
    
    
    /**
     * Before continuing with any API requests, please check if the token provided by
     * the user is authentic/expired or not
     *
     * @param MvcEvent $e        	
     * @return boolean
     */
    public function authenticate(MvcEvent $e) {
        $serverData = $e->getRequest()->getServer()->toArray();

        if (preg_match('/dashboard/', $serverData['REQUEST_URI'])) {
            return true;
        }
        $response = $e->getResponse();

        $sl = $e->getApplication()->getServiceManager();
        $routeMatch = $e->getRouteMatch();
        $isPost = $e->getRequest()->isPost();
        $isOptions = $e->getRequest()->isOptions();
        $isGet = $e->getRequest()->isGet();
        $isDelete = $e->getRequest()->isDelete();
        $authmodel = $sl->get(\Auth\Model\Auth::class);

        /*
         * For Mob App
         * Register invalid token incase of old version of app if user is not login
         */
        $userFunction = new UserFunctions();
        $queryParams = $e->getRequest()->getQuery()->toArray();
        $isMobile = isset($queryParams['mob']) ? $queryParams['mob'] : false;
//        
//        if(preg_match('/dashboard/',$serverData['REQUEST_URI']) && $isMobile){
//            return true;
//        }


        if (isset($queryParams['slrRestaurant']) && !empty(isset($queryParams['slrRestaurant']))) {
            return true;
        }

        if (isset($queryParams['token']) && $queryParams['token'] == "munchsmsreg") {
            $http_server = $e->getRequest()->getServer()->toArray();

            ######### Get Token ########
            $token = isset($queryParams['token']) ? $queryParams['token'] : false;
            if ($token) {
                $tokenDetails = $userFunction->findTokenFromRedis($token);
                if (!$tokenDetails) {
                    $userFunction->handleInvalidToken($token);
                }
            }
        }

        $version = false;
        if ($isMobile) {
            $http_server = $e->getRequest()->getServer()->toArray();
            
            if (isset($http_server['HTTP_APP_VERSION'])) {
                $version = $e->getRequest()->getHeader('App-Version')->getFieldValue();
            }

            ######### Get Token ########
            $token = isset($queryParams['token']) ? $queryParams['token'] : false;
            
            if (!empty($e->getRequest()->getPost()->toArray())) {
                $parameters = $e->getRequest()->getPost()->toArray();                
                $token = isset($parameters['token']) ? $parameters['token'] : '';
            }
            if ($e->getRequest()->getHeader('Authorization')) {
                $authorization = $e->getRequest()->getHeader('Authorization')->getFieldValue();
                $authToken = explode(" ", trim($authorization));
                $token = isset($authToken[1]) ? $authToken[1] : false;
            }
            
            if (!$version && $token) {
     
                $tokenDetails = $authmodel->findToken($token);
                
                if (!$tokenDetails) {
                    $vars = array(
                        'error' => 'Invalid/Expired token'
                    );
                    $vars = StaticFunctions::formatResponse($vars, 403, 'Invalid/Expired token', 'mobile', false);
                    $response = StaticFunctions::getResponse($sl, $vars, 403);
                    $e->stopPropagation();
                    return $response;
//                    $userFunction->handleInvalidToken($token);
                }
            }
        }

        ######################## End of register invalid token ############################

        /**
         * @todo: optimize this for common google urls
         */
        $isTokenRoute = $routeMatch->getMatchedRouteName() == 'api-token' || $routeMatch->getMatchedRouteName() == 'wapi-token';
      
        if ($isTokenRoute && ($isPost || $isGet || $isDelete || $isOptions)) {
            return true;
        }
        $authenticator = new \Rest\Authenticators\Authenticate ();
      
        if (!$authenticator->authenticateRequest($e->getRequest(), $sl)) {
            if (!$version && $isMobile) {                
                return true;
            }
            
            $vars = array(
                'error' => 'Invalid/Expired token'
            );
            $vars = StaticFunctions::formatResponse($vars, 403, 'Invalid/Expired token', 'mobile', false);
           
            $response = StaticFunctions::getResponse($sl, $vars, 403);            
            $e->stopPropagation();
            return $response;
        }
       
    }

    /**
     *
     * @param MvcEvent $e        	
     * @return null \Zend\Http\PhpEnvironment\Response
     */
    public function postProcess(MvcEvent $e) {
        $formatter = StaticFunctions::getFormatter();
       
        /**
         *
         * @var \Zend\Di\Di $di
         */
        $sl = StaticFunctions::getServiceLocator();
        
        if ($formatter !== false) {
            if ($e->getResult() instanceof \Zend\View\Model\ViewModel) {
                if (is_array($e->getResult()->getVariables())) {
                    // Get the variables from
                    $vars = $e->getResult()->getVariables();
                } else {
                    $vars = null;
                }
            } else {
                $vars = $e->getResult();
            }

            $request = $sl->get('request');            
            $requestType = (bool) $request->getQuery('mob', false) ? 'mobile' : 'web';
            $vars = StaticFunctions::formatResponse($vars, 200, 'Success', $requestType);
            $response = StaticFunctions::getResponse($sl, $vars, 200);
            return $response;
        }
        return false;
    }

    /**
     *
     * @todo This can be more optimized if we can set the value of Service locator
     *       before executing the code as we can use the
     *       StaticFunctions::getResponse and StaticFunctions::getFormatter functions for centralizing the code
     * @param MvcEvent $e        	`
     * @return null \Zend\Http\PhpEnvironment\Response
     */
    public function errorProcess(MvcEvent $e) {
        /**
         *
         * @var \Zend\Di\Di $di
         */ 
        $sl = $e->getApplication()->getServiceManager();
        $di = $sl->get("Di");

        $eventParams = $e->getParams();
        //pr($eventParams,1);
        /**
         *
         * @var array $configuration
         */
        $configuration = $e->getApplication()->getConfig();
        

        $statusCode = \Zend\Http\PhpEnvironment\Response::STATUS_CODE_500;

        $vars = array();
        if (isset($eventParams ['exception'])) {
            /**
             *
             * @var \Exception $exception
             */
            $exception = $eventParams ['exception'];
            //pr($exception,1);
            if ($configuration ['errors'] ['show_exceptions'] ['trace']) {
                $vars ['error-trace'] = $exception->getTrace();
            }
            $statusCode = $exception->getCode() ? $exception->getCode() : $statusCode;
            
            if ($configuration ['errors'] ['show_exceptions'] ['message']) 
            {
                if($statusCode==2002)
                {
                    $reasonPhrase = "Mysql: Either Invalid host name or Server is stopped. Please check and correct";
                }
                else if ( $statusCode == 1045)
                {
                    $reasonPhrase = "Mysql: Invalid password";
                }
                else
                {  
                    $reasonPhrase = $exception->getMessage();
                }
                
                $vars ['error'] = $reasonPhrase ;
            }
        }
        if (empty($vars)) {
            $vars ['error'] = 'Invalid request please check the api request';
        }

        /**
         *
         * @var PostProcessor\AbstractPostProcessor $postProcessor
         */
        $routeMatch = $sl->get('Application')->getMvcEvent()->getRouteMatch();
        $config = $sl->get('config');
        try {
            if (isset($config ['api_standards'])) {
                // Get api standards decided
                $apiStandards = $config ['api_standards'];

                // Get default formatter text or set it to "formatter"
                $formatterText = isset($apiStandards ['formatter_text']) ? $apiStandards ['formatter_text'] : "formatter";

                // Set default formatter type from api_standards or set it default to JSON
                $defaultFormatter = isset($apiStandards ['default_formatter']) ? $apiStandards ['default_formatter'] : "json";

                // Get the formatter from query
                $params = $sl->get('request')->getQuery()->getArrayCopy();
                $formatter = isset($params [$formatterText]) ? $params [$formatterText] : $defaultFormatter;
            } else {
                throw new \Exception("Invalid Parameters");
            }
        } catch (\Exception $ex) {
            // On any exception set the formatter to the json
            $formatter = "json";
        }

        if ($eventParams ['error'] === \Zend\Mvc\Application::ERROR_CONTROLLER_NOT_FOUND || $eventParams ['error'] === \Zend\Mvc\Application::ERROR_ROUTER_NO_MATCH) {
            $statusCode = \Zend\Http\PhpEnvironment\Response::STATUS_CODE_405;
            $e->getResponse()->setStatusCode($statusCode);
        } else {
            $e->getResponse()->setCustomStatusCode($statusCode);
        }

        $request = $sl->get('request');
        $requestType = (bool) $request->getQuery('mob', false) ? 'mobile' : 'web';
        
        $this->addServiceLocator($e);
        $vars = StaticFunctions::formatResponse($vars, $statusCode, $vars ['error'], $requestType);
        

        $postProcessor = $di->get($formatter . "_processor", array(
            'vars' => $vars,
            'response' => $e->getResponse($statusCode)
        ));

        $postProcessor->process();

        $e->stopPropagation();
        
        /* ---------------  To Mongo [Starts]---------------------- */
        $env            =   getenv('APPLICATION_ENV');
        $mongoConf      =   $config['mongo'][$env];
        $objHost        =   new \MongoDB\Client($mongoConf['host']);
        $objDatabase    =   $objHost->selectDatabase($mongoConf['database']);
        $objCollection  =   $objDatabase->selectCollection("exceptions");
        //pr($vars);
        $data   =   array();
        $data['original_msg']   =   $exception->getMessage() ; 
        $data['msg_shown']      =   $vars['message'] ;
        $data['statusCode']     =   $statusCode;
        //$data['stack_trace']    =   $exception->getTrace();
        $data['file']           =   $exception->getFile();
        $data['line']           =   $exception->getLine();
        
        $data['exception_time'] =   date('Y-m-d H:i:s',time());
        $data['url']            =   $e->getRequest()->getUriString();
                
        $objCollection->insertOne($data);
        /* ---------------  To Mongo [Ends]------------------------ */
        
        return $postProcessor->getResponse();
    }

//    public function initConstants(MvcEvent $e) {
//        $sl = $e->getApplication()->getServiceManager();
//        $config = $sl->get('Config');
//        $constants = function ($search_term) use($config) {
//            $currTarget = $config ['constants'];
//            $kArr = explode(":", $search_term);
//
//            foreach ($kArr as $key => $value) {
//                if (isset($currTarget [$value])) {
//                    $currTarget = $currTarget [$value];
//                } else {
//                    throw new \Exception('Invalid Configuration Path: ' . $search_term . ' in $config["constants"]');
//                }
//            }
//            return $currTarget;
//        };
//        if (($konstants = realpath(BASE_DIR . DS . 'config' . DS . 'konstants.php')) !== false) {
//            return require_once $konstants;
//        }
//        return false;
//    }
//$this->_request->getHeaders()->get('User-Agent')
    public function getUserAgent(MvcEvent $e) {  
        return StaticFunctions::setUserAgent($e->getRequest()->getHeader('User-Agent'));
    }

}
