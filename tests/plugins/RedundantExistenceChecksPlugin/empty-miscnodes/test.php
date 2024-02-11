<?php

// @phan-file-suppress PhanNoopEmpty

function doTestMisc() {
	empty( 1 );
	empty( 2 + 2 );
	empty( true );
	empty( ( 1 ) );
	empty( ( rand() ) );
	empty( $x = null );
	$arr = [];
	empty( $arr[42] = 42 );
	empty( $y = 42 );
	empty( empty( $z ) );
	empty( fn () => 42 );
	empty( true && false );
	empty( (int)$x1 ); // Redundant empty(), but not reported because an issue is emitted for the expr
	empty( isset( $x2 ) );
	empty( rand() ? $x3 : $x4 ); // Redundant empty(), but not reported because an issue is emitted for the expr
	empty( new stdClass );
	empty( $doesNotExist++ ); // Redundant empty(), but not reported because an issue is emitted for the expr
	$obj = new stdClass;
	empty( clone $obj );
	empty( require $GLOBALS['some_file'] );
}
