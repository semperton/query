<?php

declare(strict_types=1);

namespace Semperton\Query\Partial;

use RuntimeException;
use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;

final class Filter implements ExpressionInterface
{
	protected $conditions = [];

	protected $factory;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
	}

	public function and($col, ?string $op = null, $val = null): self
	{
		$this->conditions[] = ['and', $col, $op, $val];
		return $this;
	}

	public function or($col, ?string $op = null, $val = null): self
	{
		$this->conditions[] = ['or', $col, $op, $val];
		return $this;
	}

	public function isValid(): bool
	{
		return !empty($this->conditions);
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];

		$sql = [];
		$first = true;

		foreach ($this->conditions as $condition) {

			$bool = $condition[0];
			$column = $condition[1];
			$operator = $condition[2];
			$value = $condition[3];

			if (is_callable($column)) { // sub filter

				$subFilter = new self($this->factory);

				$column($subFilter);

				if ($subFilter->isValid()) {

					if (!$first) {
						$sql[] = $bool;
					}

					$sql[] = '(' . $subFilter->compile($params) . ')';
				}
			} else {

				if (!$first) {
					$sql[] = $bool;
				}

				if(is_string($column)){
					$sql[] = $this->factory->quoteIdentifier($column);
				}
				else if($value instanceof ExpressionInterface){
					$sql[] = $column->compile($params);
				}
				else{
					throw new RuntimeException('Invalid filter argument');
				}

				if(empty($operator)){
					continue;
				}

				$operator = strtolower($operator);
				$sql[] = $operator;

				if ($value instanceof ExpressionInterface) {
					$sql[] = $value->compile($params);
				} else if (is_array($value)) {

					$subParams = [];
					foreach ($value as $val) {

						if ($val instanceof ExpressionInterface) {
							$subParams[] = $val->compile($params);
						} else {
							$param = $this->factory->newParameter();
							$params[$param] = $val;
							$subParams[] = $param;
						}
					}

					if ($operator === 'between' && count($value) == 2) {
						$sql[] = implode(' and ', $subParams);
					} else {
						$sql[] = '(' . implode(', ', $subParams) . ')';
					}
				} else {
					$param = $this->factory->newParameter();
					$params[$param] = $value;
					$sql[] = $param;
				}
			}

			$first = false;
		}

		return implode(' ', $sql);
	}
}
