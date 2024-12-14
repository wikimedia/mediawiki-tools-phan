<?php

// @phan-file-suppress PhanUndeclaredProperty,PhanNoopIsset
// @phan-file-suppress PhanUndeclaredClassAttribute,UnusedPluginSuppression,UnusedPluginFileSuppression
// Different suppressions are needed in different PHP versions (7.4 does not "see" the attribute at all, 8.0 and 8.1
// see it but can't find the class, 8.2 sees it and knows what it is).

class ClassForDynamicPropWithoutAttribute {
	public $thisPropertyIsDefined;
}

#[AllowDynamicProperties]
class ClassForDynamicPropWithAttribute {
	public $thisPropertyIsDefined; // isset() on this property is always wrong, even if the class allows dynamic properties
}

function testWithoutAttrib( ClassForDynamicPropWithoutAttribute $param ) {
	$withoutAttrib = new ClassForDynamicPropWithoutAttribute();
	isset( $withoutAttrib->dynamicProp );
	$withoutAttrib->dynamicProp2 = 'Hello';
	isset( $withoutAttrib->dynamicProp2 );
	isset( $withoutAttrib->thisPropertyIsDefined );

	isset( $param->dynamicProp );
	isset( $param->dynamicProp2 );
	isset( $param->dynamicProp3 );
	isset( $param->thisPropertyIsDefined );
}

function testWithAttrib( ClassForDynamicPropWithAttribute $param ) {
	$withAttrib = new ClassForDynamicPropWithAttribute();
	isset( $withAttrib->dynamicProp );
	$withAttrib->dynamicProp2 = 'Hello';
	isset( $withAttrib->dynamicProp2 );
	isset( $withAttrib->thisPropertyIsDefined );

	isset( $param->dynamicProp );
	isset( $param->dynamicProp2 );
	isset( $param->dynamicProp3 );
	isset( $param->thisPropertyIsDefined );
}

#[AllowDynamicProperties]
class TestAttributeInheritanceBase {}

class TestAttributeInheritanceChild extends TestAttributeInheritanceBase {}

class ExtendsStdClass extends stdClass {}

function testInheritance() {
	$base = new TestAttributeInheritanceBase();
	isset( $base->x );

	$child = new TestAttributeInheritanceChild();
	isset( $child->y );

	$extendsStdClass = new ExtendsStdClass();
	isset( $extendsStdClass->z );
}

/**
 * @property stdClass $docCommentProperty
 */
class ClassWithDocCommentPropertyAndNoAttribute {
}

/**
 * @property stdClass $docCommentProperty
 */
#[AllowDynamicProperties]
class ClassWithDocCommentPropertyAndAttribute {
}

function testDocComment() {
	$withoutAttribute = new ClassWithDocCommentPropertyAndNoAttribute();
	isset( $withoutAttribute->docCommentProperty );

	$withAttribute = new ClassWithDocCommentPropertyAndAttribute();
	isset( $withAttribute->docCommentProperty );
}
