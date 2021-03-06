<?php

declare(strict_types=1);

namespace Semperton\Query;

interface ExpressionInterface
{
	public function valid(): bool;

	/**
	 * @return self
	 */
	public function reset();

	public function compile(?array &$params): string;
}
