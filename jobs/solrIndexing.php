<?php
use MCommons\StaticOptions;
use Restaurant\Model\SolrIndexing;
defined ( 'APPLICATION_ENV' ) || define ( 'APPLICATION_ENV', (getenv ( 'APPLICATION_ENV' ) ? getenv ( 'APPLICATION_ENV' ) : 'local') );
require_once dirname ( __FILE__ ) . "/init.php";
StaticOptions::setServiceLocator ( $GLOBALS ['application']->getServiceManager () );
$SolrIndexModel = new SolrIndexing();
$SolrIndexModel->getDbTable ()->setArrayObjectPrototype ( 'ArrayObject' );
$options = array (
		'columns' => array (
				'id',
				'restaurant_id',
				'rest_code',
				'is_indexed'
		),
		'where' => array (
				'is_indexed' => 0
		) 
);
$IndexData = $SolrIndexModel->findRestaurant ( $options )->toArray ();
if ($IndexData) {
	$ids = array ();
	$rest_code ='';
	foreach ( $IndexData as $data ) {
		$rest_code[] = $data ['rest_code'];
		$ids [] = $data ['id'];
	}
	if(is_array($rest_code))
      $rest_codes = implode(',',$rest_code);
}


if ($ids) {
	$data = array (
			'is_indexed' => 1
	);

	
	//$dealModel->updateSolrIndexing ( $data, $ids );
}

