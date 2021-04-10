<?php

declare(strict_types=1);

namespace Semperton\Query\Trait;

use Semperton\Query\Partial\Order;

trait OrderByTrait
{
	/** @var Order */
	protected $orderBy;

	public function orderAsc($field): self
	{
		$this->orderBy->asc($field);
		return $this;
	}

	public function orderDesc($field): self
	{
		$this->orderBy->desc($field);
		return $this;
	}
}
