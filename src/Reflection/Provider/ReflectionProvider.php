<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Provider;

use ReflectionClass;
use ReflectionFunction;

interface ReflectionProvider
{

	public function createReflectionClass(string $class): ReflectionClass;

	public function createReflectionFunction(string $function): ReflectionFunction;

	public function requireFile(string $file): void;

	/**
	 * @param string[] $directories
	 * @param string[] $excludeDirectories
	 */
	public function requireDirectories(array $directories, array $excludeDirectories): void;

	public function evalSource(string $source): void;

	public function classExists(string $name): bool;

	public function functionExists(string $name): bool;

	public function interfaceExists(string $name): bool;

	public function traitExists(string $name): bool;

}
