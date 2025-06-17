<?php
declare( strict_types = 1 );
// phpcs:disable MediaWiki.NamingConventions.ValidGlobalName
// phpcs:disable MediaWiki.Commenting.MissingCovers.MissingCovers -- T363064

use MediaWikiPhanConfig\Plugin\FirstClassCallableRecommendFixer;
use Phan\CLIBuilder;
use Phan\CodeBase;
use Phan\Config;
use Phan\Language\Scope\GlobalScope;
use Phan\Language\Type;
use Phan\Library\FileCache;
use Phan\Output\Printer\PlainTextPrinter;
use Phan\Phan;
use Phan\Plugin\ConfigPluginSet;
use Phan\Plugin\Internal\IssueFixingPlugin\IssueFixer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Taken from taint-check's SecurityCheckTest
 */
#[CoversNothing]
class PluginTest extends TestCase {
	private ?CodeBase $codeBase = null;

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

	public function tearDown(): void {
		Type::clearAllMemoizations();
		// Make sure we don't keep using the polyfill parser after a single test used it.
		Config::reset();
		GlobalScope::reset();
	}

	/**
	 * @param string $plugin
	 * @param string $cfgFile
	 * @param bool $usePolyfill Whether to force the polyfill parser
	 * @return array{?string, ?string}
	 */
	private function runPhan( string $plugin, string $cfgFile, bool $usePolyfill ): array {
		if ( !$usePolyfill && !extension_loaded( 'ast' ) ) {
			$this->markTestSkipped( 'This test requires PHP extension \'ast\' loaded' );
		}

		Config::reset();
		Type::clearAllMemoizations();
		$cliBuilder = new CLIBuilder();
		$cliBuilder->setOption( 'project-root-directory', __DIR__ );
		$cliBuilder->setOption( 'config-file', $cfgFile );
		$cliBuilder->setOption( 'directory', "./plugins/$plugin" );
		$cliBuilder->setOption( 'exclude-file', "plugins/$plugin/fixed.php" );
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

		// We need to intercept the issue collector like this, because they somehow disappear
		// if you just call Phan::getIssueCollector()->getCollectedIssues() at the end.
		// There has to be a better way to do this...
		$instances = [];
		Phan::setIssueCollector( new class( $instances ) implements \Phan\Output\IssueCollectorInterface {
			private $instances;
			private $realCollector;

			public function __construct( &$instances ) {
				$this->instances =& $instances;
				$this->realCollector = new \Phan\Output\Collector\BufferingCollector;
			}

			public function collectIssue( ...$arguments ): void {
				$this->realCollector->collectIssue( ...$arguments );
			}

			public function getCollectedIssues( ...$arguments ): array {
				$this->instances = $this->realCollector->getCollectedIssues();
				return $this->realCollector->getCollectedIssues( ...$arguments );
			}

			public function removeIssuesForFiles( ...$arguments ): void {
				$this->realCollector->removeIssuesForFiles( ...$arguments );
			}

			public function reset( ...$arguments ): void {
				$this->instances = $this->realCollector->getCollectedIssues();
				$this->realCollector->reset( ...$arguments );
			}

			public function flush(): void {
				$this->instances = $this->realCollector->getCollectedIssues();
				$this->realCollector->flush();
			}
		} );

		Phan::analyzeFileList( $this->codeBase, static function () use ( $cli ) {
			return $cli->getFileList();
		} );

		$issues = $stream->fetch();
		$fixed = $this->computeFixes( $this->codeBase, $instances );

		return [ $issues, $fixed ];
	}

	private function computeFixes( $code_base, $instances ): ?string {
		// TODO This should not be hardcoded, but we only have one fixer now, so whatever
		IssueFixer::registerFixerClosure( 'MediaWikiUseFirstClassCallable',
			FirstClassCallableRecommendFixer::fix( ... ), );

		$fixers_for_files = IssueFixer::computeFixersForInstances( $instances );

		foreach ( $fixers_for_files as $file => $fixers ) {
			$entry = FileCache::getOrReadEntry( $file );
			$contents = $entry->getContents();
			$new_contents = IssueFixer::computeNewContentForFixers( $code_base, $file, $contents, $fixers );
			// Assume only one file exists
			return $new_contents;
		}
		return null;
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
			$fixed = file_exists( $folder . '/fixed.php' ) ? file_get_contents( $folder . '/fixed.php' ) : null;

			yield "$plugin/$testName" => [
				"$plugin/$testName",
				"./plugins/$plugin/config.php",
				$expected,
				$fixed
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
	 * @param ?string $fixed
	 */
	public function testPlugins( string $path, string $configFile, string $expected, ?string $fixed ): void {
		[ $actualIssues, $actualFixed ] = $this->runPhan( $path, $configFile, false );

		// Replace backslashes with slashes and replace CRLF with LF, both appear when running on Windows.
		$actualIssues = str_replace(
			[ "\r", '\\' ],
			[ '', '/' ],
			$actualIssues
		);

		static::assertSame( $expected, $actualIssues );
		static::assertSame( $fixed, $actualFixed );
	}

	/**
	 * @dataProvider provideTestCases
	 *
	 * @param string $path
	 * @param string $configFile
	 * @param string $expected
	 */
	public function testPlugins_Polyfill( string $path, string $configFile, string $expected ): void {
		if ( $path === 'FirstClassCallableRecommendPlugin/basic' ) {
			$this->markTestSkipped( "Named params don't work in polyfill parser?" );
		}

		// Replace backslashes with slashes and replace CRLF with LF, both appear when running on Windows.
		[ $actualIssues, $actualFixed ] = str_replace(
			[ "\r", '\\' ],
			[ '', '/' ],
			$this->runPhan( $path, $configFile, true )
		);

		static::assertSame( $expected, $actualIssues );
	}
}
