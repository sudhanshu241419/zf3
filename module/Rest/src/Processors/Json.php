<?php

namespace Rest\Processors;

class Json extends AbstractProcessors {
	public function process() {
		$result = \Zend\Json\Encoder::encode ( $this->_vars );
		$this->_response->setContent ( $result );
		$headers = $this->_response->getHeaders ();
		$headers->addHeaderLine ( 'Content-Type', 'application/json' );
		$this->_response->setHeaders ( $headers );
	}
}
