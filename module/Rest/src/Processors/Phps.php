<?php

namespace Rest\Processors;

/**
 * PHP Serialized Post Processor
 */
class Phps extends AbstractProcessors {
	public function process() {
		$result = serialize ( $this->_vars );
		
		$this->_response->setContent ( $result );
		
		$headers = $this->_response->getHeaders ();
		
		// Non-official content type. Unfortunately there is no official one...
		$headers->addHeaderLine ( 'Content-Type', 'application/vnd.php.serialized' );
		$this->_response->setHeaders ( $headers );
	}
}