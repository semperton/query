<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Query\QueryFactory;

final class QueryTest extends TestCase
{
	public function testInsert(): void
	{
		$factory = new QueryFactory();
		$q = $factory->insert('product')->values(['name' => 'Shirt', 'price' => 2000]);

		$q2 = $factory->insert('product')->values(['name' => 'Shirt', 'price' => 2000]);

		$this->assertEquals($q->debug(), $q2->debug());

		$expected = "insert into product (name, price) values ('Shirt', 2000)";
		$this->assertEquals($expected, $q2->debug());
	}

	public function testSelect(): void
	{
		$factory = new QueryFactory();
		$q = $factory->select('user');

		$q->fields([
			'firstname',
			'username' => 'name',
			$q->func('json_extract', $q->ident('data'), '$.stars')
		])
			->where('id', '>', 3);

		$expected = "select firstname, name username, json_extract(data, '$.stars') from user where id > 3";
		$this->assertEquals($expected, $q->debug());

		$q->reset();

		$exp = $q->raw(':num');
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

	public function testJoin(): void
	{
		$factory = new QueryFactory();
		$q = $factory->select('user', 'u');
		$q->join('billing', 'b')
			->on('u.id', '=', 'b.user_id')
			->where('u.id', '>', 5);

		$expected = 'select * from user u inner join billing b on u.id = b.user_id where u.id > 5';
		$this->assertEquals($expected, $q->debug());

		$q->reset()
			->from('user', 'u')
			->fields(['id'])
			->rightJoin('billing', 'b')
			->on('u.id', '=', 'b.user_id')
			->andOn('u.name', 'like', 'b.user_name')
			->where('id');

		$expected = 'select id from user u right join billing b on u.id = b.user_id and u.name like b.user_name where id';
		$this->assertEquals($expected, $q->debug());
	}
}
