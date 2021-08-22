<?php

declare(strict_types=1);

namespace Semperton\Query\Expression;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;

final class Identifier implements ExpressionInterface
{
	/** @var string */
	protected $value;

	/** @var QueryFactory */
	protected $factory;

	public function __construct(QueryFactory $factory, string $value)
	{
		$this->factory = $factory;
		$this->value = $value;
	}

	public function isValid(): bool
	{
		return $this->value !== '';
	}

	public function reset(): self
	{
		return $this;
	}

	public function compile(array &$params = []): string
	{
		return $this->factory->maybeQuote($this->value);
	}
}
