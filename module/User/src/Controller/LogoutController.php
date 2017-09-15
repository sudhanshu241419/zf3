<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;

class LogoutController extends AbstractRestfulController {
	public function getList() {
		$session = $this->getUserSession ();
		if ($session) {           
			$session->setUserId ( null );
			$session->save ();
			return array ('success' => true);
		} else {
			throw new \Exception ( 'Something went wrong. User not logged out.' );
		}
	}
}