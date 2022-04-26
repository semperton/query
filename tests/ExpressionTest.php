<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Query\Expression\Field;
use Semperton\Query\Expression\Filter;
use Semperton\Query\Expression\Table;
use Semperton\Query\QueryFactory;
use Semperton\Query\Type\SelectQuery;

final class ExpressionTest extends TestCase
{
	public function testField(): void
	{
		$factory = new QueryFactory();
		$field = new Field($factory);

		$field->add('a.time', 'time');
		$this->assertTrue($field->valid());
		$field->reset();
		$this->assertFalse($field->valid());
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
		$factory = new QueryFactory();
		$filter = new Filter($factory);
		$this->assertFalse($filter->valid());
		$filter->and('u.name', 'like', '%John%')->and('u.age', '=', 22);
		$this->assertTrue($filter->valid());
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

		$filter->reset();
		$params = [];
		$filter->and('id')->and('age', '>', 22);
		$this->assertEquals('id and age > :p9', $filter->compile());

		$filter->reset();
		$params = [];
		$sub = new Filter($factory);
		$sub->and('id', '=', 2)->or('id', '=', 5);
		$filter->and($sub)->and('age', '>', 22);
		$this->assertEquals('(id = :p10 or id = :p11) and age > :p12', $filter->compile());
	}

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

	public function testTable(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$factory = new QueryFactory();
		$table = new Table($factory);

		$table->add(function (SelectQuery $query) {
			$query->from('user')->where('id', '>', 3);
		}, 'users');

		$this->assertEquals('(select * from user where id > :p1) users', $table->compile());

		$table->reset();
		$table->add(function (SelectQuery $query) {
		});
	}
}
