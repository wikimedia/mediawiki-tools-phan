<?php

declare( strict_types=1 );

namespace MediaWikiPhanConfig\Plugin;

use Microsoft\PhpParser;
use MultipleIterator;
use Phan\AST\Parser;
use Phan\CodeBase;
use Phan\IssueInstance;
use Phan\Language\Context;
use Phan\Library\FileCacheEntry;
use Phan\Plugin\Internal\IssueFixingPlugin\FileEdit;
use Phan\Plugin\Internal\IssueFixingPlugin\FileEditSet;

class FirstClassCallableRecommendFixer {
	/**
	 * Converts `"foo\"bar\\"` to `foo"bar\`.
	 */
	private static function getStringValue( string $token ): string {
		return Parser::parseCode(
			new CodeBase( [], [], [], [], [] ),
			new Context(),
			null,
			'/tmp/unused-FirstClassCallableRecommendFixer',
			'<?php ' . $token . ';',
			true
		)->children[0];
	}

	private static function equalNodes( PhpParser\Node $a, PhpParser\Node $b, string $textA, string $textB ): bool {
		$multipleIterator = new MultipleIterator( MultipleIterator::MIT_NEED_ANY | MultipleIterator::MIT_KEYS_NUMERIC );
		$multipleIterator->attachIterator( $a->getDescendantTokens() );
		$multipleIterator->attachIterator( $b->getDescendantTokens() );
		foreach ( $multipleIterator as [ $a, $b ] ) {
			if ( !$a || !$b || $a->kind !== $b->kind ) {
				return false;
			}
			if (
				$a->kind === PhpParser\TokenKind::StringLiteralToken ?
					// Ignore differences in simple string literals, like the quote type
					self::getStringValue( $a->getText( $textA ) ) !== self::getStringValue( $b->getText( $textB ) ) :
					$a->getText( $textA ) !== $b->getText( $textB )
			) {
				return false;
			}
		}
		return true;
	}

	public static function fix( CodeBase $code_base, FileCacheEntry $contents, IssueInstance $instance ): ?FileEditSet {
		// This uses a different PHP parser from the rest of Phan. D:
		// The usual parser (nikic/php-ast) doesn't report precise source locations, only the line.
		// The parser used here (Microsoft/tolerant-php-parser) does.

		$line = $instance->getLine();
		$source = (string)$instance->getTemplateParameters()[2];
		$replacement = (string)$instance->getTemplateParameters()[0];

		$parser = new PhpParser\Parser();
		$sourceCode = "<?php\n$source;";
		$sourceNode = $parser->parseSourceFile( $sourceCode )->statementList[1]->expression;

		$edits = [];
		foreach ( $contents->getNodesAtLine( $line ) as $startNode ) {
			// Make sure we find results if the node extends over multiple lines.
			// This is probably really inefficient.
			foreach ( $startNode->getDescendantNodes() as $node ) {
				// Find a node that looks like the source we're supposed to replace.
				// This basically compares the source code, ignoring whitespace.
				if ( self::equalNodes( $node, $sourceNode, $contents->getContents(), $sourceCode ) ) {
					// @phan-suppress-next-line PhanThrowTypeAbsentForCall
					$start = $node->getStartPosition();
					// @phan-suppress-next-line PhanThrowTypeAbsentForCall
					$end = $node->getEndPosition();
					$edits[] = new FileEdit( $start, $end, $replacement );
				}
			}
		}

		// Filter out duplicates
		if ( !$edits ) {
			return null;
		}
		$edit = array_shift( $edits );
		foreach ( $edits as $otherEdit ) {
			if ( !$otherEdit->isEqualTo( $edit ) ) {
				// If we found more than 1 possible edit for this issue,
				// we don't know which one of them is correct, so fix nothing.
				// See the last test case with 'test_multiple_param' for an example.
				return null;
			}
		}

		return new FileEditSet( [ $edit ] );
	}
}
