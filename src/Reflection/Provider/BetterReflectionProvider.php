<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Provider;

use PhpParser\Node\Stmt\ClassLike;
use ReflectionClass;
use ReflectionFunction;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass as AdapterReflectionClass;
use Roave\BetterReflection\Reflection\Adapter\ReflectionFunction as AdapterReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJson;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class BetterReflectionProvider implements ReflectionProvider
{

	/** @var \Roave\BetterReflection\SourceLocator\Ast\Locator */
	private $astLocator;

	/** @var \Roave\BetterReflection\Reflector\ClassReflector|null */
	private $classReflector;

	/** @var \Roave\BetterReflection\Reflector\FunctionReflector|null */
	private $functionReflector;

	/** @var \Roave\BetterReflection\SourceLocator\Type\SourceLocator|null */
	private $sourceLocator;

	/** @var \Roave\BetterReflection\SourceLocator\Type\SourceLocator[] */
	private $fileSourceLocators = [];

	/** @var string */
	private $currentWorkingDirectory;

	public function __construct(Locator $astLocator, string $currentWorkingDirectory)
	{
		$this->astLocator = $astLocator;
		$this->currentWorkingDirectory = $currentWorkingDirectory;
	}

	public function hasClass(string $name): bool
	{
		return $this->classExists($name);
	}

	public function createReflectionClass(string $class): ReflectionClass
	{
		return new AdapterReflectionClass($this->getClassReflector()->reflect($class));
	}

	public function createReflectionFunction(string $function): ReflectionFunction
	{
		return new AdapterReflectionFunction($this->getFunctionReflector()->reflect($function));
	}

	public function requireFile(string $file): void
	{
		$this->classReflector = null;
		$this->functionReflector = null;
		$this->sourceLocator = null;
		$this->fileSourceLocators[$file] = new SingleFileSourceLocator($file, $this->astLocator);
	}

	/**
	 * @param string[] $directories
	 * @param string[] $excludeDirectories
	 */
	public function requireDirectories(array $directories, array $excludeDirectories): void
	{
		// todo
	}

	public function createAnonymousClassReflection(ClassLike $node, string $file): ReflectionClass
	{
		$source = file_get_contents($file);
		if ($source === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return new AdapterReflectionClass(BetterReflectionClass::createFromNode(
			$this->getClassReflector(),
			$node,
			new LocatedSource($source, $file)
		));
	}

	public function classExists(string $name): bool
	{
		try {
			$this->createReflectionClass($name);
			return true;
		} catch (IdentifierNotFound $e) {
			return false;
		}
	}

	public function functionExists(string $name): bool
	{
		try {
			$this->createReflectionFunction($name);
			return true;
		} catch (IdentifierNotFound $e) {
			return false;
		}
	}

	public function interfaceExists(string $name): bool
	{
		return $this->classExists($name);
	}

	public function traitExists(string $name): bool
	{
		return $this->classExists($name);
	}

	/**
	 * @param string[] $files
	 */
	public function setAnalysedFiles(array $files): void
	{
		foreach ($files as $file) {
			$this->requireFile($file);
		}
	}

	private function getClassReflector(): ClassReflector
	{
		if ($this->classReflector === null) {
			$this->classReflector = new ClassReflector($this->getSourceLocator());
		}
		return $this->classReflector;
	}

	private function getFunctionReflector(): FunctionReflector
	{
		if ($this->functionReflector === null) {
			$this->functionReflector = new FunctionReflector($this->getSourceLocator(), $this->getClassReflector());
		}
		return $this->functionReflector;
	}

	private function getSourceLocator(): SourceLocator
	{
		if ($this->sourceLocator === null) {
			$this->sourceLocator = new MemoizingSourceLocator(new AggregateSourceLocator(array_merge(
				[
					(new MakeLocatorForComposerJson())($this->currentWorkingDirectory, $this->astLocator),
					new PhpInternalSourceLocator($this->astLocator),
				],
				array_values($this->fileSourceLocators)
			)));
		}
		return $this->sourceLocator;
	}

}
