<?php

use Phan\Config;

$baseCfg = require __DIR__ . '/../base-plugin-test-config.php';

$baseCfg['plugins'][] = Config::projectPath( __DIR__ . '/../../../src/Plugin/NoBaseExceptionPlugin.php' );

$baseCfg['whitelist_issue_types'] = [
	// Fun fact: we can't use NoBaseExceptionPlugin::ISSUE_TYPE as that would trigger the composer autoloader
	// and cause a PHP fatal error due to duplicated class definition when phan tries to `require` the plugin file.
	'MediaWikiNoBaseException',
];

return $baseCfg;
