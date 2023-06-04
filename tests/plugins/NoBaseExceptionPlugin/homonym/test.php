<?php

namespace NoBaseExceptionPluginTestHomonym {
	class Exception extends \Exception {

	}

	function testGood() {
		throw new Exception();
	}

	function testBad() {
		throw new \Exception();
	}
}
