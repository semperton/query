<?php

declare(strict_types=1);

namespace Semperton\Query\Type;

use Semperton\Query\Partial\Field;
use Semperton\Query\Partial\Filter;
use Semperton\Query\Partial\Join;
use Semperton\Query\Partial\Order;
use Semperton\Query\Partial\Table;
use Semperton\Query\QueryFactory;
use Semperton\Query\Partial\Expression;
use Semperton\Query\Trait\LimitTrait;
use Semperton\Query\Trait\OrderByTrait;
use Semperton\Query\Trait\WhereTrait;

final class SelectQuery extends Expression
{
	protected $distinct = false;

	protected $fields;

	protected $tables;

	protected $offset = 0;

	protected $groupBy = [];

	protected $having;

	protected $joins = [];

	/** @var Join */
	protected $lastJoin;

	use WhereTrait;
	use OrderByTrait;
	use LimitTrait;

	public function __construct(QueryFactory $factory)
	{
		parent::__construct($factory, 'select');

		$this->fields = new Field($factory);
		$this->tables = new Table($factory);
		$this->where = new Filter($factory);
		$this->having = new Filter($factory);
		$this->orderBy = new Order($factory);
	}

	public function from($table, string $alias = ''): self
	{
		$this->tables->add($table, $alias);
		return $this;
	}

	public function fields(array $fields): self
	{
		foreach ($fields as $alias => $field) {
			$this->fields->add($field, is_int($alias) ? '' : $alias);
		}
		return $this;
	}

	public function distinct(bool $flag = true): self
	{
		$this->distinct = $flag;
		return $this;
	}

	public function join($table, string $alias = ''): self
	{
		return $this->innerJoin($table, $alias);
	}

	public function innerJoin($table, string $alias = ''): self
	{
		$join = new Join($this->factory, Join::TYPE_INNER);
		$join->table($table, $alias);

		$this->joins[] = $join;
		$this->lastJoin = $join;

		return $this;
	}

	public function leftJoin(): self
	{
		return $this;
	}

	public function rightJoin(): self
	{
		return $this;
	}

	public function on($col, ?string $op = null, $value = null): self
	{
		return $this->andOn($col, $op, $value);
	}

	public function andOn($col, ?string $op = null, $value = null): self
	{
		if ($this->lastJoin !== null) {
			$this->lastJoin->andOn($col, $op, $value);
		}
		return $this;
	}

	public function orOn($col, ?string $op = null, $value = null): self
	{
		if ($this->lastJoin !== null) {
			$this->lastJoin->orOn($col, $op, $value);
		}
		return $this;
	}

	public function groupBy(string $field): self
	{
		$this->groupBy[] = $field;
		return $this;
	}

	public function having($col, ?string $op = null, $value = null): self
	{
		return $this->andHaving($col, $op, $value);
	}

	public function andHaving($col, ?string $op = null, $value = null): self
	{
		$this->having->and($col, $op, $value);
		return $this;
	}

	public function orHaving($col, ?string $op = null, $value = null): self
	{
		$this->having->or($col, $op, $value);
		return $this;
	}

	public function offset(int $num): self
	{
		$this->offset = $num;
		return $this;
	}

	public function isValid(): bool
	{
		return $this->tables->isValid();
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];

		$sql = ['select'];

		if ($this->distinct) {
			$sql[] = 'distinct';
		}

		if ($this->fields->isValid()) {
			$sql[] = $this->fields->compile($params);
		} else {
			$sql[] = '*';
		}

		if ($this->tables->isValid()) {
			$sql[] = 'from';
			$sql[] = $this->tables->compile($params);
		}

		foreach ($this->joins as $join) {
			$sql[] = $join->compile($params);
		}

		if ($this->where->isValid()) {
			$sql[] = 'where';
			$sql[] =  $this->where->compile($params);
		}

		if (!empty($this->groupBy)) {

			$sql[] = 'group by';
			$sql[] = implode(', ', $this->groupBy);
		}

		if ($this->having->isValid()) {
			$sql[] = 'having';
			$sql[] = $this->having->compile($params);
		}

		if ($this->orderBy->isValid()) {
			$sql[] = 'order by';
			$sql[] = $this->orderBy->compile($params);
		}

		if ($this->limit > 0) {

			$param = $this->factory->newParameter();
			$params[$param] = $this->limit;
			$sql[] = 'limit ' . $param;

			if ($this->offset > 0) {

				$param = $this->factory->newParameter();
				$params[$param] = $this->offset;
				$sql[] = 'offset ' . $param;
			}
		}

		return implode(' ', $sql);
	}
}
