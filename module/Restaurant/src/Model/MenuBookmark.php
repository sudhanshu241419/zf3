<?php

namespace Restaurant\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use MCommons\StaticFunctions;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

class MenuBookmark extends AbstractModel 
{
    public $id;
    public $menu_id;
    public $restaurant_id;
    public $menu_name;
    public $user_id;
    public $type;
    public $created_on;
    const LOVEIT = 'lo';
    const TRIEDIT = 'ti';
    const WANTIT = 'wi';


    protected $__table_name = 'menu_bookmarks';
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) 
    {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }
        
    public function menuBookmarksCounts(array $options = array()) 
    {
        $select = new Select ();
        $select->from ( $this->_tableGateway->getTable() );
        
        $select->columns( array (
                                'total_count' => new Expression ( 'COUNT(menu_id)' ),
                                'type' 
                                 ) 
                        );
        
        $select->where ( array ( 'menu_id' => $options ['columns'] ['menu_id'] ) );

        $select->group ( array ( 'type' ) );

        $menubookmarks = $this->_tableGateway->selectWith ( $select );
        //echo $select->getSqlString($this->getPlatform());
        return $menubookmarks;
    }
    
    
    public function getMenuBookmarksByUserId($menu_id, $user_id) 
    {
        $select         = new Select ();
        $bookmarkType   = StaticFunctions::$book_mark_types;
        $bkmark_detail  = array ();
        
        $select->from ( $this->_tableGateway->getTable() );
        $select->columns ( array (
                        'total_count' => new Expression ( 'COUNT(menu_id)' ),
                        'type' 
        ) );
        
        $select->where ( array (
                        'menu_id' => $menu_id,
                        'user_id' => $user_id 
        ) );

        $select->group ( array ( 'type' ) );

        $menubookmarks = $this->_tableGateway->selectWith( $select )->toArray ();

        if (! empty ( $menubookmarks )) 
        {
            foreach ( $menubookmarks as $b ) 
            {
                $key = $b ['type'];
                $bkmark_detail [$key] = $b ['total_count'];
            }

            foreach ( $bookmarkType as $type ) 
            {
                if (! array_key_exists ( $type, $bkmark_detail ))
                        $bkmark_detail [$type] = 0;
            }

            return $bkmark_detail;
        } 
        else 
        {
            return array (
                            self::LOVEIT => 0,
                            self::TRIEDIT => 0,
                            self::WANTIT => 0 
            );
        }
    }
	
	

}
