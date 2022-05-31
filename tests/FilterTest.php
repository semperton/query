<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Query\Expression\Filter as QueryFilter;
use Semperton\Query\QueryFactory;
use Semperton\Search\Filter as SearchFilter;

final class FilterTest extends TestCase
{
	public function testCount(): void
	{
		$queryFilter = new QueryFilter(new QueryFactory());

		$this->assertEquals(0, $queryFilter->count());

		$queryFilter->and('id', '>', 5);

		$this->assertEquals(1, $queryFilter->count());

		$queryFilter->or('id', '=', 1);

		$this->assertEquals(2, $queryFilter->count());
	}

	public function testValid(): void
	{
		$queryFilter = new QueryFilter(new QueryFactory());

		$this->assertFalse($queryFilter->valid());

		$queryFilter->and('id', '=', 1);

		$this->assertTrue($queryFilter->valid());

		$queryFilter->reset();

		$this->assertFalse($queryFilter->valid());
	}

	public function testRegularFilter(): void
	{
		$queryFilter = new QueryFilter(new QueryFactory());

		$queryFilter->and('id', '=', 1);
		$this->assertTrue($queryFilter->valid());

		$sql = $queryFilter->compile();
		$this->assertEquals('id = :p1', $sql);

		$queryFilter->or('name', 'like', 'John');

		$sql = $queryFilter->compile();
		$this->assertEquals('id = :p2 or name like :p3', $sql);

		$queryFilter->and(static function (QueryFilter $filter) {
			$filter->and('type', 'like', 'user');
		});

		$sql = $queryFilter->compile();
		$this->assertEquals('id = :p4 or name like :p5 and type like :p6', $sql);
	}

	public function testSearchFilter(): void
	{
		$queryFilter = new QueryFilter(new QueryFactory());
		$searchFilter = new SearchFilter();

		$searchFilter->like('name', 'John')->greater('age', 44);

		$queryFilter->and('id', '=', 22)->or($searchFilter);

		$this->assertTrue($queryFilter->valid());

		$sql = $queryFilter->compile();
		$this->assertEquals('id = :p1 or (name like :p2 and age > :p3)', $sql);
	}

	public function testClosureFilter(): void
	{
		$queryFilter = new QueryFilter(new QueryFactory());

		$closureFilter = static function (QueryFilter $filter) {
			$filter->and('id', '=', 55)->or('name', 'like', 'John');
		};

		$queryFilter->and($closureFilter);
		$this->assertTrue($queryFilter->valid());

		$sql = $queryFilter->compile();
		$this->assertEquals('id = :p1 or name like :p2', $sql);

		$queryFilter->and('age', '>', 22);
		$this->assertTrue($queryFilter->valid());

		$sql = $queryFilter->compile();
		$this->assertEquals('(id = :p3 or name like :p4) and age > :p5', $sql);
	}

	public function testNull(): void
	{
		$factory = new QueryFactory();
		$queryFilter = new QueryFilter($factory);
		$queryFilter->and('name', 'is null');

		$sql = $queryFilter->compile();
		$this->assertEquals('name is null', $sql);

		$queryFilter->reset();
		$queryFilter->and('number', 'is not', $factory->raw('null'));

		$this->assertEquals('number is not null', $queryFilter->compile());
	}
}
