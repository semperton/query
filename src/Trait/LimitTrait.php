<?php

declare(strict_types=1);

namespace Semperton\Query\Trait;

trait LimitTrait
{
	protected $limit = 0;

	public function limit(int $num): self
	{
		$this->limit = $num;
		return $this;
	}
}
