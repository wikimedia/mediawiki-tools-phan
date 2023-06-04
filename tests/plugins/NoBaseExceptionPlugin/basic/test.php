<?php

function testGood() {
	$x = new RuntimeException();
	throw new RuntimeException();
	$class = RuntimeException::class;
	throw new $class;
	$randClass = rand() ? RuntimeException::class : LogicException::class;
	throw new $randClass;
	$potentiallyAnyException = getSomeException();
	throw new $potentiallyAnyException;
}

function testBad() {
	$x = new Exception();
	throw new Exception();
}

function testIdeallyBad() {
	// Things that should trigger a warning, but aren't handled yet.
	$class = Exception::class;
	throw new $class;
	$randClass = rand() ? RuntimeException::class : Exception::class;
	throw new $randClass;
}

function getSomeException(): Exception {
	return $GLOBALS['this-could-be-a-subclass'];
}

