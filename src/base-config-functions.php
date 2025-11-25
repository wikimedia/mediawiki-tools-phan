<?php
declare( strict_types = 1 );

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
				'phan/tolerant-php-parser',
				'phan/phan',
				'phan/var_representation_polyfill',
				'phpunit/php-code-coverage',
				'squizlabs/php_codesniffer',
				'phpcsstandards/phpcsextra',
				'phpcsstandards/phpcsutils',
				// Exclude stubs used in libraries
				'[^/]+/[^/]+/\.phan',
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
		// Note, this implies unused_variable_detection
		->enableDeadCodeDetection( true )
		->shouldDeadCodeDetectionPreferFalseNegatives( true )
		// TODO Enable by default
		->setProgressBarMode( ConfigBuilder::PROGRESS_BAR_DISABLED )
		->readClassAliases( true )
		->enableRedundantConditionDetection( true )
		->setTargetPHPVersion( '8.5' )
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

			// Issues from unused variable detection with too many false positives. For variables in particular, we
			// would need a way to annotate RAII classes, see e.g. plugin attempt in
			// Id87c4a437c0f9144fb53a576af16fc9cb23baed1.
			// Intentionally enabled, as of phan 6.0.5: PhanUnusedPrivateMethodParameter, PhanUnusedVariableStatic,
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

			// Issues from dead code detection with too many false positives. For classes, constants, functions,
			// and public/protected members, it'll be hard to lower the rate.
			// Intentionally enabled, as of phan 6.0.5: PhanReadOnlyPrivateProperty, PhanWriteOnlyPrivateProperty,
			// PhanUnreferencedPrivateProperty, PhanReadOnlyPHPDocProperty, PhanUnreferencedPrivateClassConstant,
			// PhanUnreferencedPrivateMethod
			'PhanUnreferencedClass',
			'PhanReadOnlyPublicProperty',
			'PhanWriteOnlyPublicProperty',
			'PhanReadOnlyProtectedProperty',
			'PhanWriteOnlyProtectedProperty',
			'PhanUnreferencedPublicClassConstant',
			'PhanUnreferencedProtectedClassConstant',
			'PhanUnreferencedPublicMethod',
			'PhanUnreferencedPublicProperty',
			'PhanUnreferencedProtectedMethod',
			'PhanUnreferencedProtectedProperty',
			'PhanUnreferencedConstant',
			'PhanUnreferencedFunction',
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
			'CaseMismatchPlugin',
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
