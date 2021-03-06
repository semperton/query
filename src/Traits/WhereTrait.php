<?php

declare(strict_types=1);

namespace Semperton\Query\Traits;

use Closure;
use Semperton\Query\ExpressionInterface;
use Semperton\Query\Expression\Filter;
use Semperton\Search\Filter as SearchFilter;

trait WhereTrait
{
	/** @var Filter */
	protected $where;

	/**
	 * @param string|Closure|SearchFilter|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val 
	 * @return static
	 */
	public function where($col, ?string $op = null, $val = null): self
	{
		return $this->andWhere($col, $op, $val);
	}

	/**
	 * @param string|Closure|SearchFilter|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 * @return static
	 */
	public function andWhere($col, ?string $op = null, $val = null): self
	{
		$this->where->and($col, $op, $val);
		return $this;
	}

	/**
	 * @param string|Closure|SearchFilter|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 * @return static
	 */
	public function orWhere($col, ?string $op = null, $val = null): self
	{
		$this->where->or($col, $op, $val);
		return $this;
	}
}
