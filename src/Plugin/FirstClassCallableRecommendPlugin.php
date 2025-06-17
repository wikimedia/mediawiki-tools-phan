<?php

declare( strict_types=1 );

namespace MediaWikiPhanConfig\Plugin;

use Phan\PluginV3;
use Phan\PluginV3\AutomaticFixCapability;
use Phan\PluginV3\PostAnalyzeNodeCapability;

class FirstClassCallableRecommendPlugin extends PluginV3
	implements PostAnalyzeNodeCapability, AutomaticFixCapability
{
	public const OTHER_ISSUE_TYPE = 'MediaWikiUseFirstClassCallable';
	public const INTERNALFUNC_ISSUE_TYPE = 'MediaWikiUseFirstClassCallableInternalFunc';

	/**
	 * @inheritDoc
	 */
	public static function getPostAnalyzeNodeVisitorClassName(): string {
		return FirstClassCallableRecommendVisitor::class;
	}

	/**
	 * @inheritDoc
	 */
	public function getAutomaticFixers(): array {
		return [
			self::INTERNALFUNC_ISSUE_TYPE => FirstClassCallableRecommendFixer::fix( ... ),
			self::OTHER_ISSUE_TYPE => FirstClassCallableRecommendFixer::fix( ... ),
		];
	}
}

return new FirstClassCallableRecommendPlugin();
