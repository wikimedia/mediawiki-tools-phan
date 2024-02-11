<?php

declare( strict_types=1 );

namespace MediaWikiPhanConfig\Plugin;

use Phan\PluginV3;
use Phan\PluginV3\PostAnalyzeNodeCapability;

class RedundantExistenceChecksPlugin extends PluginV3 implements PostAnalyzeNodeCapability {
	public const EMPTY_ISSUE_TYPE = 'MediaWikiNoEmptyIfDefined';
	public const ISSET_ISSUE_TYPE = 'MediaWikiNoIssetIfDefined';

	/**
	 * @inheritDoc
	 */
	public static function getPostAnalyzeNodeVisitorClassName(): string {
		return RedundantExistenceChecksVisitor::class;
	}
}

return new RedundantExistenceChecksPlugin();
