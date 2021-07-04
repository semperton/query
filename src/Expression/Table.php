<?php

declare(strict_types=1);

namespace Semperton\Query\Expression;

use Closure;
use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;
use Semperton\Query\Type\SelectQuery;
use RuntimeException;

use function is_string;
use function implode;

final class Table implements ExpressionInterface
{
	/** @var array<int, array{string|Closure|ExpressionInterface, string}> */
	protected $tables = [];

	/** @var QueryFactory */
	protected $factory;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
	}

	/**
	 * @param string|Closure|ExpressionInterface $table
	 */
	public function add($table, string $alias = ''): self
	{
		$this->tables[] = [$table, $alias];
		return $this;
	}

	public function isValid(): bool
	{
		return !!$this->tables;
	}

	public function reset(): self
	{
		$this->tables = [];
		return $this;
	}

	public function compile(array &$params = []): string
	{
		$sql = [];

		foreach ($this->tables as $entry) {

			$table = $entry[0];
			$alias = $entry[1];

			if ($table instanceof ExpressionInterface) {
				if ($table->isValid()) {
					$expr = '(' . $table->compile($params) . ')';
					if ($alias !== '') {
						$expr .= ' ' . $this->factory->quoteIdentifier($alias);
					}
					$sql[] = $expr;
				}
			} else if (is_string($table)) {
				$table = $this->factory->quoteIdentifier($table);
				if ($alias === '') {
					$sql[] = $table;
				} else {
					$sql[] = $table . ' ' . $this->factory->quoteIdentifier($alias);
				}
			} else {
				if ($alias === '') {
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
