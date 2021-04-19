<?php

declare(strict_types=1);

namespace Semperton\Query\Expression;

use RuntimeException;
use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;

final class Order implements ExpressionInterface
{
	protected $orders = [];

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

	public function isValid(): bool
	{
		return !empty($this->orders);
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

			if (is_string($field)) {
				$field = $this->factory->quoteIdentifier($field);
			} else if ($field instanceof ExpressionInterface) {
				$field = $field->compile($params);
			} else {
				throw new RuntimeException('Invalid order argument');
			}

			$sql[] = "$field $dir";
		}

		return implode(', ', $sql);
	}
}
