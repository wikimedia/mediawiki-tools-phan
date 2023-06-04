<?php

class MyException extends Exception {
	public function __construct() {
		// Calling the parent constructor from Exception is fine.
		parent::__construct( 'Some predefined message' );
	}
}
