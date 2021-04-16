<?php

declare(strict_types=1);

namespace Semperton\Query\Partial;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;
use RuntimeException;

final class Table implements ExpressionInterface
{
	protected $tables = [];

	protected $factory;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
	}

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

			if (is_string($table)) {

				$table = $this->factory->quoteIdentifier($table);
				$sql[] = empty($alias) ? $table : $table . ' ' . $this->factory->quoteIdentifier($alias);
			} else if ($table instanceof ExpressionInterface) {
				if ($table->isValid()) {
					$sql[] = '(' . $table->compile($params) . ')' . (empty($alias) ? '' : ' ' . $this->factory->quoteIdentifier($alias));
				}
			} else if (is_callable($table)) {

				if (empty($alias)) {
					throw new RuntimeException('Alias is required for sub select');
				}

				$subSelect = $this->factory->select();

				$table($subSelect);

				if ($subSelect->isValid()) {
					$sql[] = '(' . $subSelect->compile($params) . ') ' . $this->factory->quoteIdentifier($alias);
				}
			} else {
				throw new RuntimeException('Invalid table argument');
			}
		}

		return implode(', ', $sql);
	}
}
