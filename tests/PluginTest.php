<?php

// phpcs:disable MediaWiki.NamingConventions.ValidGlobalName

use Phan\CLIBuilder;
use Phan\CodeBase;
use Phan\Config;
use Phan\Language\Type;
use Phan\Output\Printer\PlainTextPrinter;
use Phan\Phan;
use Phan\Plugin\ConfigPluginSet;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Taken from taint-check's SecurityCheckTest
 * @coversNothing
 */
class PluginTest extends TestCase {
	private ?CodeBase $codeBase = null;

	/**
	 * Taken from phan's BaseTest class
	 * @inheritDoc
	 */
	protected $backupStaticAttributesExcludeList = [
		'Phan\AST\PhanAnnotationAdder' => [
			'closures_for_kind',
		],
		'Phan\AST\ASTReverter' => [
			'closure_map',
			'noop',
		],
		'Phan\Language\Type' => [
			'canonical_object_map',
			'internal_fn_cache',
		],
		'Phan\Language\Type\LiteralFloatType' => [
			'nullable_float_type',
			'non_nullable_float_type',
		],
		'Phan\Language\Type\LiteralIntType' => [
			'nullable_int_type',
			'non_nullable_int_type',
		],
		'Phan\Language\Type\LiteralStringType' => [
			'nullable_string_type',
			'non_nullable_string_type',
		],
		'Phan\Language\UnionType' => [
			'empty_instance',
		],
		'SecurityCheckPlugin' => [
			'pluginInstance'
		],
		// Back this up to avoid loading plugins multiple times (which is slow, and most importantly fails because
		// the class would be re-declared every time).
		'Phan\Plugin\ConfigPluginSet' => [
			'plugin_instances_cache'
		]
	];

	/**
	 * Copied from phan's {@see \Phan\Tests\CodeBaseAwareTest}
	 */
	public function setUp(): void {
		static $code_base = null;
		if ( !$code_base ) {
			global $internal_class_name_list;
			global $internal_interface_name_list;
			global $internal_trait_name_list;
			global $internal_function_name_list;
			if ( !isset( $internal_class_name_list ) ) {
				require_once __DIR__ . '/../vendor/phan/phan/src/codebase.php';
			}

			$code_base = new CodeBase(
				$internal_class_name_list,
				$internal_interface_name_list,
				$internal_trait_name_list,
				CodeBase::getPHPInternalConstantNameList(),
				$internal_function_name_list
			);
		}

		Type::clearAllMemoizations();
		$this->codeBase = $code_base->shallowClone();
	}

	/**
	 * @param string $plugin
	 * @param string $cfgFile
	 * @param bool $usePolyfill Whether to force the polyfill parser
	 * @return string|null
	 */
	private function runPhan( string $plugin, string $cfgFile, bool $usePolyfill ): string {
		if ( !$usePolyfill && !extension_loaded( 'ast' ) ) {
			$this->markTestSkipped( 'This test requires PHP extension \'ast\' loaded' );
		}

		Config::reset();
		Type::clearAllMemoizations();
		$cliBuilder = new CLIBuilder();
		$cliBuilder->setOption( 'project-root-directory', __DIR__ );
		$cliBuilder->setOption( 'config-file', $cfgFile );
		$cliBuilder->setOption( 'directory', "./plugins/$plugin" );
		$cliBuilder->setOption( 'no-progress-bar', true );
		if ( $usePolyfill ) {
			$cliBuilder->setOption( 'force-polyfill-parser', true );
		}
		$cli = $cliBuilder->build();

		// Reset the plugin config so that things from previous tests do not persist.
		// This is not handled by PHPUnit because the singleton is stored in a local static variable.
		// And it can't be done earlier, because reset() recomputes the list of plugins, for which we need the
		// config to be loaded.
		ConfigPluginSet::reset();
		$stream = new BufferedOutput();
		$printer = new PlainTextPrinter();
		$printer->configureOutput( $stream );
		Phan::setPrinter( $printer );

		Phan::analyzeFileList( $this->codeBase, static function () use ( $cli ) {
			return $cli->getFileList();
		} );

		return $stream->fetch();
	}

	/**
	 * @param string $plugin
	 * @return Generator
	 */
	private static function extractTestCases( string $plugin ): Generator {
		$iterator = new DirectoryIterator( __DIR__ . "/plugins/$plugin" );

		foreach ( $iterator as $dir ) {
			if ( $dir->isDot() || !$dir->isDir() ) {
				continue;
			}
			$folder = $dir->getPathname();
			$testName = basename( $folder );
			$expected = file_get_contents( $folder . '/expectedResults.txt' );

			yield "$plugin/$testName" => [
				"$plugin/$testName",
				"./plugins/$plugin/config.php",
				$expected
			];
		}
	}

	/**
	 * @return Generator
	 */
	public static function provideTestCases(): Generator {
		$iterator = new DirectoryIterator( __DIR__ . '/plugins' );

		foreach ( $iterator as $plugin ) {
			if ( $plugin->isDot() || !$plugin->isDir() ) {
				continue;
			}

			yield from self::extractTestCases( $plugin->getBasename() );
		}
	}

	/**
	 * @dataProvider provideTestCases
	 *
	 * @param string $path
	 * @param string $configFile
	 * @param string $expected
	 */
	public function testPlugins( string $path, string $configFile, string $expected ): void {
		// Replace backslashes with slashes and replace CRLF with LF, both appear when running on Windows.
		$actual = str_replace(
			[ "\r", '\\' ],
			[ '', '/' ],
			$this->runPhan( $path, $configFile, false )
		);

		static::assertSame( $expected, $actual );
	}

	/**
	 * @dataProvider provideTestCases
	 *
	 * @param string $path
	 * @param string $configFile
	 * @param string $expected
	 */
	public function testPlugins_Polyfill( string $path, string $configFile, string $expected ): void {
		// Replace backslashes with slashes and replace CRLF with LF, both appear when running on Windows.
		$actual = str_replace(
			[ "\r", '\\' ],
			[ '', '/' ],
			$this->runPhan( $path, $configFile, true )
		);

		static::assertSame( $expected, $actual );
	}
}
