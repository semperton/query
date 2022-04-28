<?php

declare(strict_types=1);

namespace Semperton\Query\Expression;

use Closure;
use InvalidArgumentException;
use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;
use Semperton\Query\Type\SelectQuery;

use function implode;

final class Table implements ExpressionInterface
{
	/** @var array<int, array{0: string|Closure|ExpressionInterface, 1: string}> */
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
		if ($alias === '' && ($table instanceof Closure || $table instanceof ExpressionInterface)) {
			throw new InvalidArgumentException('Alias cannot be empty for subquery');
		}

		$this->tables[] = [$table, $alias];
		return $this;
	}

	public function valid(): bool
	{
		return !!$this->tables;
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

			if ($table instanceof Closure) {

				$subSelect = new SelectQuery($this->factory);
				$table($subSelect, $this->factory);

				if ($subSelect->valid()) {
					$sql[] = '(' . $subSelect->compile($params) . ') ' . $this->factory->maybeQuote($alias);
				}
			} else if ($table instanceof ExpressionInterface) {

				if ($table->valid()) {

					$expr = '(' . $table->compile($params) . ')';
					if ($alias !== '') {
						$expr .= ' ' . $this->factory->maybeQuote($alias);
					}

					$sql[] = $expr;
				}
			} else { // string

				$table = $this->factory->maybeQuote($table);
				if ($alias !== '') {
					$table .= ' ' . $this->factory->maybeQuote($alias);
				}

				$sql[] = $table;
			}
		}

		return implode(', ', $sql);
	}
}
