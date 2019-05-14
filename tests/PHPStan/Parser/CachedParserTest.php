<?php declare(strict_types = 1);

namespace PHPStan\Parser;

class CachedParserTest extends \PHPUnit\Framework\TestCase
{

	/**
	 * @dataProvider dataParseFileClearCache
	 * @param int $cachedNodesByStringCountMax
	 * @param int $cachedNodesByStringCountExpected
	 */
	public function testParseFileClearCache(
		int $cachedNodesByStringCountMax,
		int $cachedNodesByStringCountExpected
	): void
	{
		$parser = new CachedParser(
			$this->getParserMock(),
			$cachedNodesByStringCountMax
		);

		$this->assertEquals(
			$cachedNodesByStringCountMax,
			$parser->getCachedNodesByStingCountMax()
		);

		// Add strings to cache
		for ($i = 0; $i <= $cachedNodesByStringCountMax; $i++) {
			$parser->parse('string' . $i);
		}

		$this->assertEquals(
			$cachedNodesByStringCountExpected,
			$parser->getCachedNodesByStringCount()
		);

		$this->assertCount(
			$cachedNodesByStringCountExpected,
			$parser->getCachedNodesByString()
		);
	}

	public function dataParseFileClearCache(): \Generator
	{
		yield 'even' => [
			'cachedNodesByStringCountMax' => 50,
			'cachedNodesByStringCountExpected' => 50,
		];

		yield 'odd' => [
			'cachedNodesByStringCountMax' => 51,
			'cachedNodesByStringCountExpected' => 51,
		];
	}

	/**
	 * @return Parser&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getParserMock(): Parser
	{
		$mock = $this->getMockBuilder(Parser::class)
			->setMethods(
				[
					'parse',
				]
			)
			->getMock();

		$mock->method('parse')->willReturn([$this->getPhpParserNodeMock()]);

		return $mock;
	}

	/**
	 * @return \PhpParser\Node&\PHPUnit\Framework\MockObject\MockObject
	 */
	private function getPhpParserNodeMock(): \PhpParser\Node
	{
		return $this->createMock(\PhpParser\Node::class);
	}

}
