<?php

// @phan-file-suppress PhanUndeclaredProperty,PhanNoopEmpty
// @phan-file-suppress PhanUndeclaredClassAttribute,UnusedPluginSuppression,UnusedPluginFileSuppression
// Different suppressions are needed in different PHP versions (AllowDynamicProperties was introduced in PHP 8.2)

class ClassForDynamicPropWithoutAttribute {
}

#[AllowDynamicProperties]
class ClassForDynamicPropWithAttribute {
}

function testWithoutAttrib( ClassForDynamicPropWithoutAttribute $param ) {
	$withoutAttrib = new ClassForDynamicPropWithoutAttribute();
	empty( $withoutAttrib->dynamicProp );
	$withoutAttrib->dynamicProp2 = 'Hello';
	empty( $withoutAttrib->dynamicProp2 );

	empty( $param->dynamicProp );
	empty( $param->dynamicProp2 );
	empty( $param->dynamicProp3 );
}

function testWithAttrib( ClassForDynamicPropWithAttribute $param ) {
	$withAttrib = new ClassForDynamicPropWithAttribute();
	empty( $withAttrib->dynamicProp );
	$withAttrib->dynamicProp2 = 'Hello';
	empty( $withAttrib->dynamicProp2 );

	empty( $param->dynamicProp );
	empty( $param->dynamicProp2 );
	empty( $param->dynamicProp3 );
}

#[AllowDynamicProperties]
class TestAttributeInheritanceBase {}

class TestAttributeInheritanceChild extends TestAttributeInheritanceBase {}

class ExtendsStdClass extends stdClass {}

function testInheritance() {
	$base = new TestAttributeInheritanceBase();
	empty( $base->x );

	$child = new TestAttributeInheritanceChild();
	empty( $child->y );

	$extendsStdClass = new ExtendsStdClass();
	empty( $extendsStdClass->z );
}
