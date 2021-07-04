<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Query\Expression\Field;
use Semperton\Query\Expression\Filter;
use Semperton\Query\QueryFactory;

final class ExpressionTest extends TestCase
{
	public function testField(): void
	{
		$factory = new QueryFactory();
		$field = new Field($factory);

		$params = [];
		$field->add('a.time', 'time');
		$this->assertTrue($field->isValid());
		$field->reset();
		$this->assertFalse($field->isValid());
		$field->add('u.name', 'name');
		$sql = $field->compile($params);
		$this->assertEquals('u.name name', $sql);
		$this->assertEmpty($params);

		$field->reset();
		$func = $factory->func('json_extract', $factory->ident('user'), '$.name');
		$field->add($func, 'name');
		$sql = $field->compile($params);
		$this->assertEquals('json_extract(user, :p1) name', $sql);
		$this->assertSame([':p1' => '$.name'], $params);
	}

	public function testFilter(): void
	{
		$params = [];
		$factory = new QueryFactory();
		$filter = new Filter($factory);
		$this->assertFalse($filter->isValid());
		$filter->and('u.name', 'like', '%John%')->and('u.age', '=', 22);
		$this->assertTrue($filter->isValid());
		$sql = $filter->compile($params);
		$this->assertEquals('u.name like :p1 and u.age = :p2', $sql);
		$this->assertSame([':p1' => '%John%', ':p2' => 22], $params);

		$filter->reset();
		$params = [];
		$filter->and('u.name', '=', 'John')->and(function (Filter $sub) {
			$sub->and('u.age', '=', 22)->or('u.age', '=', 23);
		});

		$sql = $filter->compile($params);
		$this->assertEquals('u.name = :p3 and (u.age = :p4 or u.age = :p5)', $sql);
		$this->assertSame([':p3' => 'John', ':p4' => 22, ':p5' => 23], $params);

		$filter->reset();
		$params = [];
		$func = $factory->func('json_extract', $factory->ident('user'), '$.age');
		$filter->and($func, 'between', [22, 26]);
		$sql = $filter->compile($params);
		$this->assertEquals('json_extract(user, :p6) between :p7 and :p8', $sql);
		$this->assertSame([':p6' => '$.age', ':p7' => 22, ':p8' => 26], $params);

		$filter->reset();
		$params = [];
		$raw = $factory->raw('(select 20 + 2)');
		$filter->or('u.age', '>', $raw);
		$sql = $filter->compile($params);
		$this->assertEquals('u.age > (select 20 + 2)', $sql);
		$this->assertSame([], $params);
	}

	public function testExpression(): void
	{
		$factory = new QueryFactory();
		$prepare = 'select :firstname, :lastname';

		$params = [];
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
		$params = [];
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
