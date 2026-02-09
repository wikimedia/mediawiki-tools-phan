<?php

/* @phan-file-suppress PhanNoopIsset */

function testGood( $untypedParam, array $arrayParam, string $stringParam, stdClass $stdClassParam ) {
	isset( $untypedParam['foo'] );
	isset( $arrayParam['foo'] );
	isset( $stringParam[1] );
	isset( $stdClassParam->some_prop );
	isset( $GLOBALS['x'] );
	isset( $thisVariableIsNotDefined );
	isset( $thisVariableIsNotDefined[0] );
	if ( rand() ) {
		$y = 42;
	}
	isset( $y );
}

function testBad( $untypedParam, array $arrayParam, string $stringParam, stdClass $stdClassParam ) {
	isset( $untypedParam );
	isset( $arrayParam );
	isset( $stringParam );
	isset( $stdClassParam );
	if ( rand() ) {
		$y = 42;
	} else {
		$y = 0;
	}
	isset( $y );
}

class TestClass {
	private $untypedProp;
	private string $stringProp = 'foo';
	private ?int $intOrNullProp = null;

	private static $untypedStaticProp;
	private static string $stringStaticProp = 'foo';
	private static ?int $intOrNullStaticProp = null;

	function testGood() {
		isset( $this->doesNotExist );
		isset( self::$doesNotExistStatic );
	}

	function testBad() {
		isset( $this->untypedProp );
		isset( $this->stringProp );
		isset( $this->intOrNullProp );
		isset( self::$untypedStaticProp );
		isset( self::$stringStaticProp );
		isset( self::$intOrNullStaticProp );
	}
}

// The issue is never emitted in global scope to avoid any chance of false positives
isset( $someGlobalVar );
$myGlobal = 42;
isset( $myGlobal );