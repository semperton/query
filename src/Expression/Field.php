<?php

declare(strict_types=1);

namespace Semperton\Query\Expression;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;

use function implode;

final class Field implements ExpressionInterface
{
	/** @var array<int, array{string|ExpressionInterface, string}> */
	protected $fields = [];

	/** @var QueryFactory */
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
		return !!$this->fields;
	}

	public function reset(): self
	{
		$this->fields = [];
		return $this;
	}

	public function compile(array &$params = []): string
	{
		$sql = [];

		foreach ($this->fields as $entry) {

			$field = $entry[0];
			$alias = $entry[1];

			if ($field instanceof ExpressionInterface) {
				$field = $field->compile($params);
			} else {
				$field = $this->factory->maybeQuote($field);
			}

			if ($alias === '') {
				$sql[] = $field;
			} else {
				$sql[] = $field . ' ' . $this->factory->maybeQuote($alias);
			}
		}

		return implode(', ', $sql);
	}
}
