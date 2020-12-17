<?php

use Phan\CLIBuilder;
use Phan\Output\Printer\PlainTextPrinter;
use Phan\Phan;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Taken from taint-check's SecurityCheckTest
 * @coversNothing
 */
class PluginTest extends TestCase {
	/**
	 * Taken from phan's BaseTest class
	 * @inheritDoc
	 */
	protected $backupStaticAttributesBlacklist = [
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
		]
	];

	/**
	 * @param string $plugin
	 * @param string $cfgFile
	 * @param bool $usePolyfill Whether to force the polyfill parser
	 * @return string|null
	 */
	private function runPhan( string $plugin, string $cfgFile, bool $usePolyfill ) : string {
		$codeBase = require __DIR__ . '/../vendor/phan/phan/src/codebase.php';
		$cliBuilder = new CLIBuilder();
		$cliBuilder->setOption( 'project-root-directory', __DIR__ );
		$cliBuilder->setOption( 'config-file', $cfgFile );
		$cliBuilder->setOption( 'directory', "./plugins/$plugin" );
		$cliBuilder->setOption( 'no-progress-bar', true );
		if ( $usePolyfill ) {
			$cliBuilder->setOption( 'force-polyfill-parser', true );
		}
		$cli = $cliBuilder->build();

		$stream = new BufferedOutput();
		$printer = new PlainTextPrinter();
		$printer->configureOutput( $stream );
		Phan::setPrinter( $printer );

		Phan::analyzeFileList( $codeBase, function () use ( $cli ) {
			return $cli->getFileList();
		} );

		return $stream->fetch();
	}

	/**
	 * @param string $plugin
	 * @return Generator
	 */
	private function extractTestCases( string $plugin ) : Generator {
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
	public function provideTestCases() : Generator {
		$iterator = new DirectoryIterator( __DIR__ . '/plugins' );

		foreach ( $iterator as $plugin ) {
			if ( $plugin->isDot() || !$plugin->isDir() ) {
				continue;
			}

			yield from $this->extractTestCases( $plugin->getBasename() );
		}
	}

	/**
	 * @dataProvider provideTestCases
	 *
	 * @param string $path
	 * @param string $configFile
	 * @param string $expected
	 */
	public function testPlugins( string $path, string $configFile, string $expected ) : void {
		// Replace backslashes with slashes and replace CRLF with LF, both appear when running on Windows.
		$actual = str_replace(
			[ "\r", '\\' ],
			[ '', '/' ],
			$this->runPhan( $path, $configFile, false )
		);

		static::assertEquals( $expected, $actual );
	}

	/**
	 * @dataProvider provideTestCases
	 *
	 * @param string $path
	 * @param string $configFile
	 * @param string $expected
	 */
	public function testPlugins_Polyfill( string $path, string $configFile, string $expected ) : void {
		// Replace backslashes with slashes and replace CRLF with LF, both appear when running on Windows.
		$actual = str_replace(
			[ "\r", '\\' ],
			[ '', '/' ],
			$this->runPhan( $path, $configFile, true )
		);

		static::assertEquals( $expected, $actual );
	}
}
