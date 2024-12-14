<?php

declare( strict_types=1 );

namespace MediaWikiPhanConfig\Plugin;

use ast\Node;
use Phan\AST\ASTReverter;
use Phan\AST\ContextNode;
use Phan\AST\PhanAnnotationAdder;
use Phan\AST\UnionTypeVisitor;
use Phan\Exception\IssueException;
use Phan\Exception\NodeException;
use Phan\Exception\UnanalyzableException;
use Phan\PluginV3\PluginAwarePostAnalysisVisitor;
use const ast\AST_DIM;
use const ast\AST_PROP;
use const ast\AST_STATIC_PROP;

class RedundantExistenceChecksVisitor extends PluginAwarePostAnalysisVisitor {
	/**
	 * @inheritDoc
	 */
	public function visitEmpty( Node $node ): void {
		if ( $this->context->isInGlobalScope() ) {
			// Bail out immediately to avoid any chance of fun false positives.
			return;
		}
		$expr = $node->children['expr'];

		if ( !$expr instanceof Node || !$this->exprIsPossiblyUndefined( $expr ) ) {
			self::emitPluginIssue(
				$this->code_base,
				$this->context,
				RedundantExistenceChecksPlugin::EMPTY_ISSUE_TYPE,
				// Links to https://www.mediawiki.org/wiki/Manual:Coding_conventions/PHP#empty()
				'Found usage of {FUNCTIONLIKE} on expression {CODE} that appears to be always set. ' .
				'{FUNCTIONLIKE} should only be used to suppress errors. See https://w.wiki/6paE',
				[ 'empty()', ASTReverter::toShortString( $expr ), 'empty()' ]
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitIsset( Node $node ): void {
		if ( $this->context->isInGlobalScope() ) {
			// Bail out immediately to avoid any chance of fun false positives.
			return;
		}
		$expr = $node->children['var'];

		if ( !$expr instanceof Node || !$this->exprIsPossiblyUndefined( $expr ) ) {
			self::emitPluginIssue(
				$this->code_base,
				$this->context,
				RedundantExistenceChecksPlugin::ISSET_ISSUE_TYPE,
				// Links to https://www.mediawiki.org/wiki/Manual:Coding_conventions/PHP#isset()
				'Found usage of {FUNCTIONLIKE} on expression {CODE} that appears to be always set. ' .
				'{FUNCTIONLIKE} should only be used to suppress errors. ' .
				'Check whether the expression is {TYPE} instead. See https://w.wiki/98zs',
				[ 'isset()', ASTReverter::toShortString( $expr ), 'isset()', 'null' ]
			);
		}
	}

	private function exprIsPossiblyUndefined( Node $expr ): bool {
		if ( $expr->kind === AST_DIM ) {
			// Skip this case, because it's a lesser issue even if the array element is guaranteed to be set, and also,
			// it's not possible to analyze it properly given the FLAG_IGNORE_UNDEF trickery below.
			return true;
		}

		if ( $expr->kind === AST_PROP || $expr->kind === AST_STATIC_PROP ) {
			$propExpr = $expr->kind === AST_PROP ? $expr->children['expr'] : $expr->children['class'];
			if ( $propExpr instanceof Node && $propExpr->kind === AST_DIM ) {
				// The `isset` might be there for the array access, not the property access. And if phan doesn't treat
				// the array element as possibly undefined, we'd emit a false positive. So, skip (T378284).
				return true;
			}
			try {
				$property = ( new ContextNode(
					$this->code_base,
					$this->context,
					$expr
				) )->getProperty( $expr->kind === AST_STATIC_PROP );
			} catch ( IssueException | NodeException | UnanalyzableException $_ ) {
				// Bail out if the expr is a property that phan can't resolve. In this scenario the union type will
				// be empty, but not possibly undefined, yet we shouldn't emit an issue.
				return true;
			}

			if ( $property->isDynamicOrFromPHPDoc() ) {
				// Dynamic and doc-only properties are always possibly undefined.
				return true;
			}

			if ( !$property->getUnionType()->getRealUnionType()->isEmpty() ) {
				// T378286: If it's a typed property without default value, do not emit an issue. `isset()` can be used
				// to check if the property has been initialized. See also https://github.com/phan/phan/issues/4720
				$defaultType = $property->getDefaultType();
				if ( $defaultType && $defaultType->getRealUnionType()->isNull() ) {
					return true;
				}
			}
		}

		$prevIgnoreUndefFlag = $expr->flags & PhanAnnotationAdder::FLAG_IGNORE_UNDEF;
		try {
			$expr->flags &= ~PhanAnnotationAdder::FLAG_IGNORE_UNDEF;
			$type = UnionTypeVisitor::unionTypeFromNode(
				$this->code_base,
				$this->context,
				$expr,
				false
			);
		} catch ( IssueException $_ ) {
			// Ignore, because we're removing FLAG_IGNORE_UNDEF in a hacky way.
			return true;
		} finally {
			$expr->flags |= $prevIgnoreUndefFlag;
		}

		return $type->isPossiblyUndefined();
	}
}
