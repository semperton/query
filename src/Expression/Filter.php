<?php

declare(strict_types=1);

namespace Semperton\Query\Expression;

use Closure;
use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;

use function implode;
use function is_array;
use function strtolower;
use function count;

final class Filter implements ExpressionInterface
{
	/** @var array<int, array{
	 * string,
	 * string|Closure|ExpressionInterface,
	 * null|string,
	 * null|scalar|array|ExpressionInterface
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

	public function valid(): bool
	{
		return !!$this->conditions;
	}

	public function reset(): self
	{
		$this->conditions = [];
		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];
		$sql = [];

		foreach ($this->conditions as $condition) {

			$bool = $condition[0];
			$column = $condition[1];
			$operator = $condition[2];
			$value = $condition[3];

			if ($column instanceof Closure) { // sub filter closure

				$subFilter = new self($this->factory);

				$column($subFilter);

				if ($subFilter->valid()) {

					$sql[] = $bool;
					$sql[] = '(' . $subFilter->compile($params) . ')';
				}

				continue;
			}

			if ($column instanceof Filter) {

				if ($column->valid()) {
					$sql[] = $bool;
					$sql[] = '(' . $column->compile($params) . ')';
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
