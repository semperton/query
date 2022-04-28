<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Query\Expression\Table;
use Semperton\Query\QueryFactory;
use Semperton\Query\Type\SelectQuery;

final class TableTest extends TestCase
{
	public function testValid(): void
	{
		$table = new Table(new QueryFactory());
		$this->assertFalse($table->valid());

		$table->add('user');

		$this->assertTrue($table->valid());
	}

	public function testReset(): void
	{
		$table = new Table(new QueryFactory());
		$table->add('user');

		$this->assertTrue($table->valid());

		$table->reset();

		$this->assertFalse($table->valid());
	}

	public function testAlias(): void
	{
		$table = new Table(new QueryFactory());
		$table->add('user', 'u');

		$sql = $table->compile();
		$this->assertEquals('user u', $sql);
	}

	public function testAliasException(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Alias cannot be empty for subquery');

		$table = new Table(new QueryFactory());
		$table->add(static function (SelectQuery $query) {
			$query->from('user')->where('id');
		});
	}

	public function testSubquery(): void
	{
		$table = new Table(new QueryFactory());
		$table->add(static function (SelectQuery $query) {
			$query->from('user')->where('id', '>', 3);
		}, 'users');

		$this->assertTrue($table->valid());

		$this->assertEquals('(select * from user where id > :p1) users', $table->compile());
	}
}
