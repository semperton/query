<?php

declare(strict_types=1);

namespace Semperton\Query\Expression;

use Semperton\Query\QueryFactory;
use Semperton\Query\ExpressionInterface;
use Semperton\Query\Traits\ExpressionTrait;

use function array_merge;

final class Raw implements ExpressionInterface
{
	use ExpressionTrait;

	/** @var string */
	protected $value;

	public function __construct(QueryFactory $factory, string $value)
	{
		$this->factory = $factory;
		$this->value = $value;
	}

	public function valid(): bool
	{
		return $this->value !== '';
	}

	public function reset(): self
	{
		$this->params = [];
		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];
		$params = array_merge($params, $this->params);

		return $this->value;
	}
}
