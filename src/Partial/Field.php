<?php

declare(strict_types=1);

namespace Semperton\Query\Partial;

use RuntimeException;
use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;

final class Field implements ExpressionInterface
{
	protected $fields = [];

	protected $factory;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
	}

	/**
	 * @param string|ExpressionInterface $field
	 */
	public function add($field, string $alias = ''): self
	{
		$this->fields[] = [$field, $alias];
		return $this;
	}

	public function isValid(): bool
	{
		return !empty($this->fields);
	}

	public function reset(): self
	{
		$this->fields = [];
		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];
		$sql = [];

		foreach ($this->fields as $entry) {

			$field = $entry[0];
			$alias = $entry[1];

			if (is_string($field)) {
				$field = $this->factory->quoteIdentifier($field);
			} else if ($field instanceof ExpressionInterface) {
				$field = $field->compile($params);
			} else {
				throw new RuntimeException('Invalid field argument');
			}

			$sql[] = empty($alias) ? $field : $field . ' ' . $this->factory->quoteIdentifier($alias);
		}

		return implode(', ', $sql);
	}
}
