<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Query\QueryFactory;
use Semperton\Query\Type\DeleteQuery;
use Semperton\Query\Type\DropQuery;
use Semperton\Query\Type\InsertQuery;
use Semperton\Query\Type\SelectQuery;
use Semperton\Query\Type\UpdateQuery;

final class FactoryTest extends TestCase
{
	public function testCreateQueries(): void
	{
		$factory = new QueryFactory();

		$this->assertInstanceOf(InsertQuery::class, $factory->insert('test'));
		$this->assertInstanceOf(SelectQuery::class, $factory->select('test'));
		$this->assertInstanceOf(UpdateQuery::class, $factory->update('test'));
		$this->assertInstanceOf(DeleteQuery::class, $factory->delete('test'));
		$this->assertInstanceOf(DropQuery::class, $factory->drop('test'));
	}

	public function testNextParam(): void
	{
		$factory = new QueryFactory();

		$param = $factory->nextParam();
		$this->assertEquals(':p1', $param);

		$param = $factory->nextParam();
		$this->assertEquals(':p2', $param);
	}

	public function testQuoting(): void
	{
		$factory = new QueryFactory(false);

		$field = $factory->quoteIdentifier('user');
		$this->assertEquals('"user"', $field);

		$field = $factory->maybeQuote('user');
		$this->assertEquals('user', $field);

		$factory = new QueryFactory(true, '`');

		$field = $factory->quoteIdentifier('user.name');
		$this->assertEquals('`user`.`name`', $field);

		$field = $factory->maybeQuote('user.*');
		$this->assertEquals('`user`.*', $field);
	}

	public function testEscaping(): void
	{
		$factory = new QueryFactory();

		$escaped = $factory->escapeString("it's");
		$this->assertEquals("it''s", $escaped);

		$factory = new QueryFactory(false, '"', '"');

		$escaped = $factory->escapeString('"hello"');
		$this->assertEquals('""hello""', $escaped);
	}
}
