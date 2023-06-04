<?php

declare( strict_types=1 );

namespace MediaWikiPhanConfig\Plugin;

use Phan\PluginV3;
use Phan\PluginV3\PostAnalyzeNodeCapability;

// HACK: Avoid redeclaring the class if phan `require`s this file multiple times (e.g., in tests, where
// we reset the plugin list)
if ( !class_exists( NoEmptyIfDefinedPlugin::class ) ) {
	class NoEmptyIfDefinedPlugin extends PluginV3 implements PostAnalyzeNodeCapability {

		public const ISSUE_TYPE = 'MediaWikiNoEmptyIfDefined';

		/**
		 * @inheritDoc
		 */
		public static function getPostAnalyzeNodeVisitorClassName(): string {
			return NoEmptyIfDefinedVisitor::class;
		}

	}
}

return new NoEmptyIfDefinedPlugin();