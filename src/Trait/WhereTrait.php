<?php

declare(strict_types=1);

namespace Semperton\Query\Trait;

use Semperton\Query\Partial\Filter;

trait WhereTrait
{
	/** @var Filter */
	protected $where;

	public function where($col, ?string $op = null, $value = null): self
	{
		return $this->andWhere($col, $op, $value);
	}

	public function andWhere($col, ?string $op = null, $value = null): self
	{
		$this->where->and($col, $op, $value);
		return $this;
	}

	public function orWhere($col, ?string $op = null, $value = null): self
	{
		$this->where->or($col, $op, $value);
		return $this;
	}
}
