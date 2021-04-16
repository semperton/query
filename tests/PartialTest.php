<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Query\QueryFactory;

final class PartialTest extends TestCase
{
	public function testExpression(): void
	{
		$factory = new QueryFactory();
		$prepare = 'select :firstname, :lastname';

		$expr = $factory->raw($prepare)->bind(':firstname', 'John')->bind('lastname', 'Doe');
		$expected = "select 'John', 'Doe'";
		$this->assertEquals($expected, $expr->debug());

		$expr->compile($params);
		$this->assertEquals([':firstname' => 'John', ':lastname' => 'Doe'], $params);
	}

	public function testFunction(): void
	{
		$factory = new QueryFactory(true);
		$func = $factory->func('json_extract', $factory->ident('data'), '$.path');
		$sql = $func->compile($params);

		$this->assertEquals('json_extract("data", :p1)', $sql);
		$this->assertEquals([':p1' => '$.path'], $params);
	}

	public function testIndentifier(): void
	{
		$factory = new QueryFactory(true);
		$ident = $factory->ident('table.*');

		$this->assertEquals('"table".*', $ident->compile());
	}
}
