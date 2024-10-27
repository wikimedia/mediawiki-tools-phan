<?php

class TestItem {
	public $prop;
	public static $staticProp;
}

class TestClass {
	private array $refs = [];
	/** @return array<string|int,TestItem> */
	public function getGroupRefs( string $group ): array {
		return $this->refs[$group] ?? [];
	}
}
function doTest() {
	$t = new TestClass();
	$val = $t->getGroupRefs( 'test' );
	print_r( isset( $val['key1'] ) ); // No issue (dim node)
	print_r( isset( $val['key1']->prop ) ); // No issue (property of dim node)
	print_r( isset( $val['key1']::$staticProp ) ); // No issue (property of dim node)
	print_r( isset( $val['key2'] ) && isset( $val['key2']->prop ) ); // No issue (skipped unconditionally)
	print_r( isset( $val['key2'] ) && isset( $val['key2']::$staticProp ) ); // No issue (skipped unconditionally)
}

doTest();
