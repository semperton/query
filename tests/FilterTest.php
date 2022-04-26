<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Query\Expression\Filter as QueryFilter;
use Semperton\Query\QueryFactory;
use Semperton\Search\Filter as SearchFilter;

final class FilterTest extends TestCase
{
	public function testSearchFilter(): void
	{
		$queryFilter = new QueryFilter(new QueryFactory());
		$searchFilter = new SearchFilter();

		$searchFilter->like('name', 'John')->greater('age', 44);

		$queryFilter->and('id', '=', 22)->or($searchFilter);

		$this->assertTrue($queryFilter->valid());

		$params = [];
		$sql = $queryFilter->compile($params);
		$this->assertEquals('id = :p1 or (name like :p2 and age > :p3)', $sql);
		$this->assertEquals([':p1' => 22, ':p2' => 'John', ':p3' => 44], $params);
	}
}
