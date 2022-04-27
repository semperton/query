<?php

declare(strict_types=1);

namespace Semperton\Query\Expression;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;

use function implode;

final class Order implements ExpressionInterface
{
	/** @var array<int, array{0: string|ExpressionInterface, 1: string}> */
	protected $orders = [];

	/** @var QueryFactory */
	protected $factory;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
	}

	/**
	 * @param string|ExpressionInterface $field
	 */
	public function asc($field): self
	{
		$this->orders[] = [$field, 'asc'];
		return $this;
	}

	/**
	 * @param string|ExpressionInterface $field
	 */
	public function desc($field): self
	{
		$this->orders[] = [$field, 'desc'];
		return $this;
	}

	public function valid(): bool
	{
		return !!$this->orders;
	}

	public function reset(): self
	{
		$this->orders = [];
		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];
		$sql = [];

		foreach ($this->orders as $order) {

			$field = $order[0];
			$dir = $order[1];

			if ($field instanceof ExpressionInterface) {
				$field = $field->compile($params);
			} else {
				$field = $this->factory->maybeQuote($field);
			}

			$sql[] = "$field $dir";
		}

		return implode(', ', $sql);
	}
}
