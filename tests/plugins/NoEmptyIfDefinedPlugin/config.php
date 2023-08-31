<?php

use Phan\Config;

$baseCfg = require __DIR__ . '/../base-plugin-test-config.php';

$baseCfg['plugins'] = [
	Config::projectPath( __DIR__ . '/../../../src/Plugin/NoEmptyIfDefinedPlugin.php' )
];

// Note: we're not setting whitelist_issue_types because the plugin is a bit hacky and we want to make sure that
// no builtin issue types are emitted when they shouldn't be.

return $baseCfg;
