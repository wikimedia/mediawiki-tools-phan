<?php

use MediaWikiPhanConfig\ConfigBuilder;

function setBaseOptions( string $curDir, ConfigBuilder $configBuilder ): void {
	// TODO: Do we need to explicitly set these? If so, move to ConfigBuilder. Remove otherwise.
	$baseOptions = [
		'backward_compatibility_checks' => false,

		'parent_constructor_required' => [
		],

		'quick_mode' => false,
		'analyze_signature_compatibility' => true,
		'ignore_undeclared_variables_in_global_scope' => false,
		'read_type_annotations' => true,
		'disable_suppression' => false,
		'dump_ast' => false,
		'dump_signatures_file' => null,
		'processes' => 1,
		'whitelist_issue_types' => [],
		'markdown_issue_messages' => false,
		'generic_types_enabled' => true,
		'plugin_config' => [],
		'warn_about_undocumented_throw_statements' => true,
		'exception_classes_with_optional_throws_phpdoc' => [
			\LogicException::class,
			\RuntimeException::class,
			\Error::class,
			\DOMException::class,
			\Wikimedia\NormalizedException\NormalizedException::class,
		],
		'unused_variable_detection' => true,
		// BC for repos not checking whether these are set
		'file_list' => [],
		'exclude_file_list' => [],
	];
	$configBuilder->setRawOptions( $baseOptions );

	$configBuilder
		->excludeDirectories( $curDir . '/stubs' )
		->setExcludeFileRegex(
			'@vendor/(' .
			'(' . implode( '|', [
				// Exclude known dev dependencies
				'composer/installers',
				'php-parallel-lint/php-console-color',
				'php-parallel-lint/php-console-highlighter',
				'php-parallel-lint/php-parallel-lint',
				'mediawiki/mediawiki-codesniffer',
				'microsoft/tolerant-php-parser',
				'phan/phan',
				'phpunit/php-code-coverage',
				'squizlabs/php_codesniffer',
				// Exclude stubs used in libraries
				'[^/]+/[^/]+/\.phan',
				'symfony/polyfill-php80',
			] ) . ')' .
			'|' .
			// Also exclude tests folder from dependencies
			'.*/[Tt]ests?' .
			')/@'
		)
		->setMinimumSeverity( 0 )
		->allowMissingProperties( false )
		->allowNullCastsAsAnyType( false )
		->allowScalarImplicitCasts( false )
		->enableDeadCodeDetection( false )
		->shouldDeadCodeDetectionPreferFalseNegatives( true )
		// TODO Enable by default
		->setProgressBarMode( ConfigBuilder::PROGRESS_BAR_DISABLED )
		->readClassAliases( true )
		->enableRedundantConditionDetection( true )
		->setTargetPHPVersion( '8.4' )
		->setSuppressedIssuesList( [
			// Covered by codesniffer
			'PhanUnreferencedUseNormal',
			'PhanUnreferencedUseFunction',
			'PhanUnreferencedUseConstant',
			'PhanDuplicateUseNormal',
			'PhanDuplicateUseFunction',
			'PhanDuplicateUseConstant',
			'PhanUseNormalNoEffect',
			'PhanUseNormalNamespacedNoEffect',
			'PhanUseFunctionNoEffect',
			'PhanUseConstantNoEffect',
			'PhanDeprecatedCaseInsensitiveDefine',
			'PhanDeprecatedImplicitNullableParam',

			// We have several parameters named "unused" in public interfaces
			'PhanParamNameIndicatingUnused',
			'PhanParamNameIndicatingUnusedInClosure',

			// Consider unsuppressing when we formalize named parameter adoption
			'PhanProvidingUnusedParameter',

			// Would probably have many false positives
			'PhanPluginMixedKeyNoKey',

			// Too many false positives. Redundant variable detection is enabled for T396815 (non-capturing `catch`)
			// and other issues with low false positive rates.
			// Intentionally enabled, as of phan 5.4.6: PhanUnusedPrivateMethodParameter, PhanUnusedVariableStatic,
			// PhanUnusedClosureUseVariable, PhanUnusedVariableCaughtException
			'PhanUnusedPublicMethodParameter',
			'PhanUnusedPublicNoOverrideMethodParameter',
			'PhanUnusedPublicFinalMethodParameter',
			'PhanUnusedProtectedMethodParameter',
			'PhanUnusedProtectedNoOverrideMethodParameter',
			'PhanUnusedProtectedFinalMethodParameter',
			'PhanUnusedGlobalFunctionParameter',
			'PhanUnusedClosureParameter',
			'PhanUnusedVariable',
			'PhanUnusedVariableGlobal',
			'PhanUnusedVariableValueOfForeachWithKey',
			'PhanPossiblyInfiniteLoop',
			'PhanSideEffectFreeWhileBody',
			'PhanPossiblyInfiniteRecursionSameParams',
		] )
		->addPlugins( [
			'PregRegexCheckerPlugin',
			'UnusedSuppressionPlugin',
			'DuplicateExpressionPlugin',
			'LoopVariableReusePlugin',
			'RedundantAssignmentPlugin',
			'UnreachableCodePlugin',
			'SimplifyExpressionPlugin',
			'DuplicateArrayKeyPlugin',
			'UseReturnValuePlugin',
			'AddNeverReturnTypePlugin',
		] )
		->addCustomPlugins( [
			'RedundantExistenceChecksPlugin',
			'NoBaseExceptionPlugin',
		] );

	if ( !defined( 'MSG_EOR' ) ) {
		$configBuilder->addFiles( $curDir . '/stubs/sockets.windows.php' );
	}
}

/**
 * Internal helper used to filter dirs. This is used so that we can include commonly-used dir
 * names without phan complaining about "directory not found". It should NOT be used in
 * repo-specific config files.
 */
function filterDirs( array $dirs ): array {
	return array_filter( $dirs, 'file_exists' );
}
