<?php

declare(strict_types=1);

namespace Semperton\Query\Expression;

use Closure;
use Countable;
use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;

use function implode;
use function is_array;
use function strtolower;
use function array_shift;
use function count;

final class Filter implements ExpressionInterface, Countable
{
	/** @var array<int, array{
	 * 0: string,
	 * 1: string|Closure|ExpressionInterface,
	 * 2: null|string,
	 * 3: null|scalar|array|ExpressionInterface
	 * }> */
	protected $conditions = [];

	/** @var QueryFactory */
	protected $factory;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
	}

	/**
	 * @param string|Closure|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 */
	public function and($col, ?string $op = null, $val = null): self
	{
		$this->conditions[] = ['and', $col, $op, $val];
		return $this;
	}

	/**
	 * @param string|Closure|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 */
	public function or($col, ?string $op = null, $val = null): self
	{
		$this->conditions[] = ['or', $col, $op, $val];
		return $this;
	}

	public function count(): int
	{
		return count($this->conditions);
	}

	public function valid(): bool
	{
		return !!$this->conditions;
	}

	public function reset(): self
	{
		$this->conditions = [];
		return $this;
	}

	protected function addSearchFilter(Filter $filter, \Semperton\Search\Filter $searchFilter): void
	{
		foreach ($searchFilter as $connection => $entry) {

			$connect = $connection === $searchFilter::CONNECTION_AND ? 'and' : 'or';

			if ($entry instanceof \Semperton\Search\Filter) {

				$subFilter = new self($this->factory);

				$this->addSearchFilter($subFilter, $entry);

				if ($subFilter->valid()) {
					$filter->$connect($subFilter);
				}
			} else { // condition
				$filter->$connect($entry->getField(), $entry->getOperator(), $entry->getValue());
			}
		}
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];
		$sql = [];
		$count = $this->count();

		foreach ($this->conditions as $condition) {

			$bool = $condition[0];
			$column = $condition[1];
			$operator = $condition[2];
			$value = $condition[3];

			if (
				$column instanceof Closure ||
				$column instanceof Filter ||
				$column instanceof \Semperton\Search\Filter
			) {
				if ($column instanceof Closure) {

					$filter = new self($this->factory);
					$column($filter, $this->factory);
				} else if ($column instanceof \Semperton\Search\Filter) {

					$filter = new self($this->factory);
					$this->addSearchFilter($filter, $column);
				} else {
					$filter = $column;
				}

				if ($filter->valid()) {

					$sql[] = $bool;
					$compiled = $filter->compile($params);
					// add parentheses if necessary
					if ($count > 1 && $filter->count() > 1) {
						$compiled = '(' . $compiled . ')';
					}
					$sql[] = $compiled;
				}

				continue;
			}

			if ($column instanceof ExpressionInterface) {

				if (!$column->valid()) {
					continue;
				}

				$sql[] = $bool;
				$sql[] = $column->compile($params);
			} else {
				$sql[] = $bool;
				$sql[] = $this->factory->maybeQuote($column);
			}

			if ($operator === null) {
				continue;
			}

			$operator = strtolower($operator);
			$sql[] = $operator;

			if ($value instanceof ExpressionInterface) {
				$sql[] = $value->compile($params);
			} else if (is_array($value)) {

				$subParams = [];

				/** @var mixed */
				foreach ($value as $val) {

					if ($val instanceof ExpressionInterface) {
						$subParams[] = $val->compile($params);
					} else {
						$param = $this->factory->nextParam();
						/** @var mixed */
						$params[$param] = $val;
						$subParams[] = $param;
					}
				}

				if ($operator === 'between' && count($value) === 2) {
					$sql[] = implode(' and ', $subParams);
				} else {
					$sql[] = '(' . implode(', ', $subParams) . ')';
				}
			} else {
				$param = $this->factory->nextParam();
				$params[$param] = $value;
				$sql[] = $param;
			}
		}

		// remove leading and / or
		array_shift($sql);

		return implode(' ', $sql);
	}
}
