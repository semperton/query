<?php

declare(strict_types=1);

namespace Semperton\Query\Expression;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;
use Semperton\Query\Type\SelectQuery;
use RuntimeException;

final class Table implements ExpressionInterface
{
	/** @var list<array{string|callable|ExpressionInterface, string}> */
	protected $tables = [];

	/** @var QueryFactory */
	protected $factory;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
	}

	/**
	 * @param string|callable|ExpressionInterface $table
	 */
	public function add($table, string $alias = ''): self
	{
		$this->tables[] = [$table, $alias];
		return $this;
	}

	public function isValid(): bool
	{
		return !empty($this->tables);
	}

	public function reset(): self
	{
		$this->tables = [];
		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];
		$sql = [];

		foreach ($this->tables as $entry) {

			$table = $entry[0];
			$alias = $entry[1];

			if ($table instanceof ExpressionInterface) {
				if ($table->isValid()) {
					$sql[] = '(' . $table->compile($params) . ')' . (empty($alias) ? '' : ' ' . $this->factory->quoteIdentifier($alias));
				}
			} else if (is_string($table)) {
				$table = $this->factory->quoteIdentifier($table);
				$sql[] = empty($alias) ? $table : $table . ' ' . $this->factory->quoteIdentifier($alias);
			} else {
				if (empty($alias)) {
					throw new RuntimeException('Alias is required for sub select');
				}

				$subSelect = new SelectQuery($this->factory);

				$table($subSelect);

				if ($subSelect->isValid()) {
					$sql[] = '(' . $subSelect->compile($params) . ') ' . $this->factory->quoteIdentifier($alias);
				}
			}
		}

		return implode(', ', $sql);
	}
}
