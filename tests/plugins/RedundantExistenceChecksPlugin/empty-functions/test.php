<?php

function mayReturnNull(): ?int {
	return rand() ? 42 : null;
}

class TestNoEmptyOnMethodCalls {
	public function nonStaticMethod(): ?int {
		return rand() ? 42 : null;
	}

	public static function staticMethod(): ?int {
		return rand() ? 42 : null;
	}
}

function testFunctionCalls() {
	if ( empty( mayReturnNull() ) ) {
		echo "hello";
	}

	$class = new TestNoEmptyOnMethodCalls();
	if ( empty( $class->nonStaticMethod() ) ) {
		echo "hello";
	}
	if ( empty( TestNoEmptyOnMethodCalls::staticMethod() ) ) {
		echo "hello";
	}
}
