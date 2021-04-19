<?php

declare(strict_types=1);

namespace Semperton\Query\Traits;

trait LimitTrait
{
	/** @var int */
	protected $limit = 0;

	/**
	 * @return static
	 */
	public function limit(int $num): self
	{
		$this->limit = $num;
		return $this;
	}
}
