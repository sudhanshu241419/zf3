<?php

namespace Rest\Processors;

class Xml extends AbstractProcessors {
	public function process() {
		$xml = new SimpleXMLExtended ( "<?xml version=\"1.0\"?><response></response>" );
		$this->createXmlNode ( $this->_vars, $xml );
		$result = $xml->asXML ();
		$this->_response->setContent ( $result );
		
		$headers = $this->_response->getHeaders ();
		$headers->addHeaderLine ( 'Content-Type', 'application/xml' );
		$this->_response->setHeaders ( $headers );
	}
	protected function createXmlNode($result, &$xml) {
		foreach ( $result as $key => $value ) {
			if (is_array ( $value )) {
				if (! is_numeric ( $key )) {
					$subnode = $xml->addChild ( "$key" );
					$this->createXmlNode ( $value, $subnode );
				} else {
					$this->createXmlNode ( $value, $xml );
				}
			} else {
				
				$xml->addCData ( "$key", "$value" );
			}
		}
	}
}
class SimpleXMLExtended extends \SimpleXMLElement {
	public function addCData($key, $cdata_text) {
		$child = $this->addChild ( $key, null );
		$node = dom_import_simplexml ( $child );
		$no = $node->ownerDocument;
		$node->appendChild ( $no->createCDATASection ( $cdata_text ) );
	}
}