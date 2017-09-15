<?php

namespace User\Form;

use Zend\Form\Form;

class LoginForm extends Form {
	public function __construct($name = null) {
		$name = $name == null ? "user_login" : $name;
		parent::__construct ( $name );
		$this->add ( array (
				'name' => 'email' 
		) );
		
		$this->add ( array (
				'name' => 'password' 
		) );
	}
}