<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Query\QueryFactory;

final class QueryTest extends TestCase
{
	public function testInsert(): void
	{
		$factory = new QueryFactory();
		$q = $factory->insert(['name' => 'Shirt', 'price' => 2000])->into('product');

		$q2 = $factory->insert();
		$q2->into('product')->values(['name' => 'Shirt', 'price' => 2000]);

		$this->assertEquals($q->debug(), $q2->debug());

		$expected = "insert into product (name, price) values ('Shirt', 2000)";
		$this->assertEquals($expected, $q2->debug());
	}

	public function testSelect(): void
	{
		$factory = new QueryFactory();
		$q = $factory->select();

		$q->fields([
			'firstname',
			'username' => 'name',
			$q->func('json_extract', $q->ident('data'), '$.stars')
		])
			->from('user')
			->where('id', '>', 3);

		$expected = "select firstname, name username, json_extract(data, '$.stars') from user where id > 3";
		$this->assertEquals($expected, $q->debug());

		$q->reset();

		$exp = $q->expr(':num');
		$q->fields(['id', 'username', 'stars' => $exp])->from('user')->where('id')->bind('num', 55);

		$expected = "select id, username, 55 stars from user where id";
		$this->assertEquals($expected, $q->debug());
	}

	// public function testUpdate(): void
	// {
	// }

	// public function testDelete(): void
	// {
	// }
}
