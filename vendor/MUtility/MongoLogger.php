<?php
namespace MUtility;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class MongoLogger
{
    private $__objHost;
    private $__objDatabase;
    private $__objCollection;
    
    public function __construct() {
        $env            = getenv('APPLICATION_ENV');
        $objConfig      = \MCommons\StaticFunctions::getServiceLocator()->get('Config');
        $mongoConf      = $objConfig['mongo'][$env];
        
        $this->__objHost        =   new \MongoDB\Client($mongoConf['host']);
        $this->__objDatabase    =   $this->__objHost->selectDatabase($mongoConf['database']);
        
        $this->setCollection($mongoConf['collection']);
    }
    
    public function setCollection($collectionName = 'logs')
    {
        $this->__objCollection  =   $this->__objDatabase->selectCollection($collectionName);
    }
    
    public function insert($data = array())
    {
        /*
         * Argument $data should be an associative array 
         */
        return $this->__objCollection->insertOne($data);
    }
}