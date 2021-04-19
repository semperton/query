<?php

declare(strict_types=1);

namespace Semperton\Query\Traits;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\Expression\Order;

trait OrderByTrait
{
	/** @var Order */
	protected $orderBy;

	/**
	 * @param string|ExpressionInterface $field
	 * @return static
	 */
	public function orderAsc($field): self
	{
		$this->orderBy->asc($field);
		return $this;
	}

	/**
	 * @param string|ExpressionInterface $field
	 * @return static
	 */
	public function orderDesc($field): self
	{
		$this->orderBy->desc($field);
		return $this;
	}
}
