<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler;

interface Parser extends \PhpParser\Parser
{

	/**
	 * @return \PhpParser\Node[]
	 */
	public function parse(string $code, ?ErrorHandler $errorHandler = null): array;

}
