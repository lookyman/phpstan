<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Provider;

use Nette\DI\Container;
use Nette\Loaders\RobotLoader;
use ReflectionClass;
use ReflectionFunction;

class NativeReflectionProvider implements ReflectionProvider
{

	/** @var \Nette\DI\Container */
	private $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
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
		}, $this->container->parameters['fileExtensions']);

		$robotLoader->setTempDirectory($this->container->parameters['tmpDir']);
		foreach ($directories as $directory) {
			$robotLoader->addDirectory($directory);
		}

		foreach ($excludeDirectories as $directory) {
			$robotLoader->excludeDirectory($directory);
		}

		$robotLoader->register();
	}

	public function evalSource(string $source): void
	{
		eval($source);
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

}
