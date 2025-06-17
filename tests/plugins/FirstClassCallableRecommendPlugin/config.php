<?php

use Phan\Config;

$baseCfg = require __DIR__ . '/../base-plugin-test-config.php';

$baseCfg['plugins'][] = Config::projectPath( __DIR__ . '/../../../src/Plugin/FirstClassCallableRecommendPlugin.php' );

$baseCfg['whitelist_issue_types'] = [
	'MediaWikiUseFirstClassCallable',
	'MediaWikiUseFirstClassCallableInternalFunc',
];

return $baseCfg;
