<?php
namespace User\Form;

use Zend\Form\Form;
class ChangePasswordForm extends Form{
	public function __construct($name = null){
		$name = $name == null ? "change_password" : $name;
		parent::__construct ( $name );
		$this->add ( array (
				'name' => 'current_password'
		) );
		$this->add ( array (
				'name' => 'new_password'
		) );
		$this->add ( array (
				'name' => 'new_password_confirm'
		) );
	}
}