<?php

/* @phan-file-suppress PhanNoopEmpty */

function testGood( $untypedParam, array $arrayParam, string $stringParam, stdClass $stdClassParam ) {
	empty( $untypedParam['foo'] );
	empty( $arrayParam['foo'] );
	empty( $stringParam[1] );
	empty( $stdClassParam->some_prop );
	empty( $GLOBALS['x'] );
	empty( $thisVariableIsNotDefined );
	empty( $thisVariableIsNotDefined[0] );
	if ( rand() ) {
		$y = 42;
	}
	empty( $y );
}

function testBad( $untypedParam, array $arrayParam, string $stringParam, stdClass $stdClassParam ) {
	empty( $untypedParam );
	empty( $arrayParam );
	empty( $stringParam );
	empty( $stdClassParam );
	if ( rand() ) {
		$y = 42;
	} else {
		$y = 0;
	}
	empty( $y );
}

class TestClass {
	private $untypedProp;
	private string $stringProp = 'foo';
	private ?int $intOrNullProp = null;

	private static $untypedStaticProp;
	private static string $stringStaticProp = 'foo';
	private static ?int $intOrNullStaticProp = null;

	function testGood() {
		empty( $this->doesNotExist );// Note: phan will emit PhanUndeclaredProperty on its own here
		empty( self::$doesNotExistStatic );// Note: phan will emit PhanUndeclaredStaticProperty on its own here
	}

	function testBad() {
		empty( $this->untypedProp );
		empty( $this->stringProp );
		empty( $this->intOrNullProp );
		empty( self::$untypedStaticProp );
		empty( self::$stringStaticProp );
		empty( self::$intOrNullStaticProp );
	}
}

// The issue is never emitted in global scope to avoid any chance of false positives
empty( $someGlobalVar );
$myGlobal = 42;
empty( $myGlobal );