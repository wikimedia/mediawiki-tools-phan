<?php

function global_function() {}

class Cls {
	/** @var callable */
	public static $static_prop;
	/** @var callable */
	public $instance_prop;

	public static function static_method() {}
	public function instance_method() {}

	public function test_callable_method( callable $foo ) {}
	public function test_callable_static( callable $foo ) {}

	private function test() {
		test_callable( [ $this, 'instance_method' ] );
		test_callable( [ self::class, 'static_method' ] );
		test_callable( [ __CLASS__, 'static_method' ] );
		test_callable( [ static::class, 'static_method' ] );
	}
}

$obj = new Cls;
$dt = new DateTimeImmutable;

function test_callable( callable $foo ) {}
test_callable( 'global_function' );
test_callable( global_function( ... ) );

// This feels overly verbose, so built-in PHP global functions have a separate error code,
// so that they can be exempted.
test_callable( 'strlen' );
test_callable( strlen( ... ) );

test_callable( 'Cls::static_method' );
test_callable( [ 'Cls', 'static_method' ] );
test_callable( [ Cls::class, 'static_method' ] );
test_callable( Cls::static_method( ... ) );

test_callable( 'DateTimeImmutable::createFromFormat' );
test_callable( [ 'DateTimeImmutable', 'createFromFormat' ] );
test_callable( [ DateTimeImmutable::class, 'createFromFormat' ] );
test_callable( DateTimeImmutable::createFromFormat( ... ) );

test_callable( [ $obj, 'instance_method' ] );
test_callable( $obj->instance_method( ... ) );

test_callable( [ $dt, 'getTimestamp' ] );
test_callable( $dt->getTimestamp( ... ) );

function test_multiple_param( $a=null, $b=null, callable $foo=null ) {}
test_multiple_param( foo: 'global_function' );
test_multiple_param( foo: global_function( ... ) );

function test_param_nullable1( ?callable $foo ) {}
test_param_nullable1( 'global_function' );
test_param_nullable1( [ $obj, 'instance_method' ] );

/**
 * @param callable|null $foo
 */
function test_param_nullable2( $foo ) {}
test_param_nullable2( 'global_function' );
test_param_nullable2( [ $obj, 'instance_method' ] );

function test_param_variadic( ?callable ...$foo ) {}
test_param_variadic( 'global_function', [ $obj, 'instance_method' ] );

$obj->test_callable_method( 'global_function' );
$obj->test_callable_method( [ $obj, 'instance_method' ] );
$obj->test_callable_method( global_function( ... ) );
$obj->test_callable_method( $obj->instance_method( ... ) );

$obj?->test_callable_method( 'global_function' );
$obj?->test_callable_method( [ $obj, 'instance_method' ] );
$obj?->test_callable_method( global_function( ... ) );
$obj?->test_callable_method( $obj->instance_method( ... ) );

Cls::test_callable_static( 'global_function' );
Cls::test_callable_static( [ $obj, 'instance_method' ] );
Cls::test_callable_static( global_function( ... ) );
Cls::test_callable_static( $obj->instance_method( ... ) );

Cls::$static_prop = 'global_function';
Cls::$static_prop = [ $obj, 'instance_method' ];
Cls::$static_prop = global_function( ... );
Cls::$static_prop = $obj->instance_method( ... );

$obj->instance_prop = 'global_function';
$obj->instance_prop = [ $obj, 'instance_method' ];
$obj->instance_prop = global_function( ... );
$obj->instance_prop = $obj->instance_method( ... );

// Built-in functions
call_user_func( [ $obj, 'instance_method' ] );
preg_replace_callback('//', [ $obj, 'instance_method' ] , '');

function test_maybe_callable1( callable|string $foo ) {}
test_maybe_callable( 'global_function' ); // not an error

/**
 * @param callable&array $foo
 */
function test_maybe_callable2( $foo ) {}
test_maybe_callable( [ $obj, 'instance_method' ] ); // not an error

$cls = Cls::class;
test_callable( [ $cls, 'static_method' ] );
test_callable( [ get_class( $obj ), 'static_method' ] );

test_callable( [ unknown_method(), 'instance_method' ] ); // no fix

test_callable(
	'global_function'
);

test_param_variadic(
	'global_function',
	[ $obj, 'instance_method' ]
);

test_callable( [ &$obj, 'instance_method' ] ); // not fixable
test_callable( [ $obj, "instance_method" ] );
test_callable( "global_function" );

// Fixer can't fix these ones, because it can't distinguish the different arguments,
// because the AST doesn't track source locations closely enough.
test_multiple_param( 'global_function', null, 'global_function' );
print( 'global_function' ); test_callable( 'global_function' );
