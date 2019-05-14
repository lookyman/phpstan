<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\NodeTraverser;

class DirectParser implements Parser
{

	/** @var \PhpParser\Parser */
	private $parser;

	/** @var \PhpParser\NodeTraverser */
	private $traverser;

	public function __construct(\PhpParser\Parser $parser, NodeTraverser $traverser)
	{
		$this->parser = $parser;
		$this->traverser = $traverser;
	}

	/**
	 * @param string $code
	 * @return \PhpParser\Node[]
	 */
	public function parse(string $code, ?ErrorHandler $errorHandler = null): array
	{
		$errorHandler = new Collecting();
		$nodes = $this->parser->parse($code, $errorHandler);
		if ($errorHandler->hasErrors()) {
			throw new \PHPStan\Parser\ParserErrorsException($errorHandler->getErrors());
		}
		if ($nodes === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		return $this->traverser->traverse($nodes);
	}

}
