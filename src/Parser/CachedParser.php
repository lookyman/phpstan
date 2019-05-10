<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler;

class CachedParser implements Parser
{

	/** @var \PHPStan\Parser\Parser */
	private $originalParser;

	/** @var mixed[] */
	private $cachedNodesByString = [];

	/** @var int */
	private $cachedNodesByStringCount = 0;

	/** @var int */
	private $cachedNodesByStringCountMax;

	public function __construct(
		Parser $originalParser,
		int $cachedNodesByStringCountMax
	)
	{
		$this->originalParser = $originalParser;
		$this->cachedNodesByStringCountMax = $cachedNodesByStringCountMax;
	}

	/**
	 * @return \PhpParser\Node[]
	 */
	public function parse(string $code, ?ErrorHandler $errorHandler = null): array
	{
		if ($this->cachedNodesByStringCountMax !== 0 && $this->cachedNodesByStringCount >= $this->cachedNodesByStringCountMax) {
			$this->cachedNodesByString = array_slice(
				$this->cachedNodesByString,
				1,
				null,
				true
			);

			--$this->cachedNodesByStringCount;
		}

		if (!isset($this->cachedNodesByString[$code])) {
			$this->cachedNodesByString[$code] = $this->originalParser->parse($code, $errorHandler);
			$this->cachedNodesByStringCount++;
		}

		return $this->cachedNodesByString[$code];
	}

	public function getCachedNodesByStringCount(): int
	{
		return $this->cachedNodesByStringCount;
	}

	public function getCachedNodesByStingCountMax(): int
	{
		return $this->cachedNodesByStringCountMax;
	}

	public function getCachedNodesByString(): array
	{
		return $this->cachedNodesByString;
	}

}
