<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Provider;

use Nette\Loaders\RobotLoader;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\PrettyPrinter\Standard;
use ReflectionClass;
use ReflectionFunction;

class NativeReflectionProvider implements ReflectionProvider
{

	/** @var \PhpParser\PrettyPrinter\Standard */
	private $printer;

	/** @var string[] */
	private $fileExtensions;

	/** @var string */
	private $tmpDir;

	/**
	 * @param string[] $fileExtensions
	 */
	public function __construct(Standard $printer, array $fileExtensions, string $tmpDir)
	{
		$this->printer = $printer;
		$this->fileExtensions = $fileExtensions;
		$this->tmpDir = $tmpDir;
	}

	public function hasClass(string $name): bool
	{
		spl_autoload_register($autoloader = function (string $autoloadedClassName) use ($name): void {
			if ($autoloadedClassName !== $name && !$this->isExistsCheckCall()) {
				throw new \PHPStan\Broker\ClassAutoloadingException($autoloadedClassName);
			}
		});

		try {
			return $this->classExists($name) || $this->interfaceExists($name) || $this->traitExists($name);
		} catch (\PHPStan\Broker\ClassAutoloadingException $e) {
			throw $e;
		} catch (\Throwable $t) {
			throw new \PHPStan\Broker\ClassAutoloadingException(
				$name,
				$t
			);
		} finally {
			spl_autoload_unregister($autoloader);
		}
	}

	public function createReflectionClass(string $class): ReflectionClass
	{
		return new ReflectionClass($class);
	}

	public function createReflectionFunction(string $function): ReflectionFunction
	{
		return new ReflectionFunction($function);
	}

	public function requireFile(string $file): void
	{
		(static function (string $file): void {
			require_once $file;
		})($file);
	}

	/**
	 * @param string[] $directories
	 * @param string[] $excludeDirectories
	 */
	public function requireDirectories(array $directories, array $excludeDirectories): void
	{
		$robotLoader = new RobotLoader();
		$robotLoader->acceptFiles = array_map(static function (string $extension): string {
			return sprintf('*.%s', $extension);
		}, $this->fileExtensions);

		$robotLoader->setTempDirectory($this->tmpDir);
		foreach ($directories as $directory) {
			$robotLoader->addDirectory($directory);
		}

		foreach ($excludeDirectories as $directory) {
			$robotLoader->excludeDirectory($directory);
		}

		$robotLoader->register();
	}

	public function createAnonymousClassReflection(ClassLike $node, string $file): ReflectionClass
	{
		$identifier = $node->name;
		if ($identifier === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		eval($this->printer->prettyPrint([$node]));
		return $this->createReflectionClass('\\' . $identifier->name);
	}

	public function classExists(string $name): bool
	{
		return class_exists($name);
	}

	public function functionExists(string $name): bool
	{
		return function_exists($name);
	}

	public function interfaceExists(string $name): bool
	{
		return interface_exists($name);
	}

	public function traitExists(string $name): bool
	{
		return trait_exists($name);
	}

	/**
	 * @param string[] $files
	 */
	public function setAnalysedFiles(array $files): void
	{
		// noop
	}

	private function isExistsCheckCall(): bool
	{
		$debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$existsCallTypes = [
			'class_exists' => true,
			'interface_exists' => true,
			'trait_exists' => true,
		];

		foreach ($debugBacktrace as $traceStep) {
			if (
				isset($traceStep['function'])
				&& isset($existsCallTypes[$traceStep['function']])
				// We must ignore the self::hasClass calls
				&& (!isset($traceStep['file']) || $traceStep['file'] !== __FILE__)
			) {
				return true;
			}
		}

		return false;
	}

}
