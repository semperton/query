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

	public function isValid(): bool
	{
		return !!$this->conditions;
	}

	public function reset(): self
	{
		$this->conditions = [];
		return $this;
	}

	public function compile(array &$params = []): string
	{
		$sql = [];
		$first = true;

		foreach ($this->conditions as $condition) {

			$bool = $condition[0];
			$column = $condition[1];
			$operator = $condition[2];
			$value = $condition[3];

			if ($column instanceof Closure) { // sub filter closure

				$subFilter = new self($this->factory);

				$column($subFilter);

				if ($subFilter->isValid()) {

					if (!$first) {
						$sql[] = $bool;
					}

					$sql[] = '(' . $subFilter->compile($params) . ')';
				}
			} else if ($column instanceof Filter) { // sub filter expression

				if ($column->isValid()) {

					if (!$first) {
						$sql[] = $bool;
					}

					$sql[] = '(' . $column->compile($params) . ')';
				}
			} else {

				if (!$first) {
					$sql[] = $bool;
				}

				if ($column instanceof ExpressionInterface) {
					$sql[] = $column->compile($params);
				} else {
					$sql[] = $this->factory->maybeQuote($column);
				}
				// else {
				// 	throw new RuntimeException('Invalid filter argument');
				// }

				if (!empty($operator)) {

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
			}

			$first = false;
		}

		return implode(' ', $sql);
	}
}
