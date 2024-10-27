<?php

class TestTypedProperty {
	private stdClass $obj1;
	private string $str;
	private string $strWithDefault = 'foo';
	/** @var string */
	private $untypedWithDoc;

	function doTest() {
		print_r( isset( $this->obj1 ) ); // No issue (to avoid false positives)
		print_r( isset( $this->str ) ); // No issue (to avoid false positives)
		print_r( isset( $this->strWithDefault ) ); // Redundant isset
		print_r( isset( $this->untypedWithDoc ) ); // Redundant isset

		$this->str = 'XcQ';
		print_r( isset( $this->str ) ); // No issue (due to upstream limitation)
	}
}
