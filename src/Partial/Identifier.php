<?php

declare(strict_types=1);

namespace Semperton\Query\Partial;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;

final class Identifier implements ExpressionInterface
{
	protected $value;

	protected $factory;

	public function __construct(QueryFactory $factory, string $value)
	{
		$this->factory = $factory;
		$this->value = $value;
	}

	public function isValid(): bool
	{
		return !empty($this->value);
	}

	public function reset(): self
	{
		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];
		return $this->factory->quoteIdentifier($this->value);
	}
}
