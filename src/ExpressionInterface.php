<?php

declare(strict_types=1);

namespace Semperton\Query;

interface ExpressionInterface
{
	public function isValid(): bool;
	public function compile(?array &$params = null): string;
}
