<?php

declare(strict_types=1);

namespace Semperton\Query\Type;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\Expression\Field;
use Semperton\Query\Expression\Filter;
use Semperton\Query\Expression\Join;
use Semperton\Query\Expression\Order;
use Semperton\Query\Expression\Table;
use Semperton\Query\QueryFactory;
use Semperton\Query\Traits\ExpressionTrait;
use Semperton\Query\Traits\LimitTrait;
use Semperton\Query\Traits\OrderByTrait;
use Semperton\Query\Traits\WhereTrait;

final class SelectQuery implements ExpressionInterface
{
	use ExpressionTrait;
	use WhereTrait;
	use OrderByTrait;
	use LimitTrait;

	/** @var bool */
	protected $distinct = false;

	/** @var Field */
	protected $fields;

	/** @var Table */
	protected $tables;

	/** @var int */
	protected $offset = 0;

	/** @var string[] */
	protected $groupBy = [];

	/** @var Filter */
	protected $having;

	/** @var Join[] */
	protected $joins = [];

	/** @var null|Join */
	protected $lastJoin;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
		$this->fields = new Field($factory);
		$this->tables = new Table($factory);
		$this->where = new Filter($factory);
		$this->having = new Filter($factory);
		$this->orderBy = new Order($factory);
	}

	/**
	 * @param string|callable|ExpressionInterface $table
	 */
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

	/**
	 * @param string|callable|ExpressionInterface $table
	 */
	public function join($table, string $alias = ''): self
	{
		return $this->innerJoin($table, $alias);
	}

	/**
	 * @param string|callable|ExpressionInterface $table
	 */
	public function innerJoin($table, string $alias = ''): self
	{
		$this->addJoin($table, $alias, Join::TYPE_INNER);
		return $this;
	}

	/**
	 * @param string|callable|ExpressionInterface $table
	 */
	public function leftJoin($table, string $alias = ''): self
	{
		$this->addJoin($table, $alias, Join::TYPE_LEFT);
		return $this;
	}

	/**
	 * @param string|callable|ExpressionInterface $table
	 */
	public function rightJoin($table, string $alias = ''): self
	{
		$this->addJoin($table, $alias, Join::TYPET_RIGHT);
		return $this;
	}

	/**
	 * @param string|callable|ExpressionInterface $table
	 */
	protected function addJoin($table, string $alias, string $type): void
	{
		$join = new Join($this->factory, $type);
		$join->table($table, $alias);

		$this->joins[] = $join;
		$this->lastJoin = $join;
	}

	/**
	 * @param string|callable|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 */
	public function on($col, ?string $op = null, $val = null): self
	{
		return $this->andOn($col, $op, $val);
	}

	/**
	 * @param string|callable|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 */
	public function andOn($col, ?string $op = null, $val = null): self
	{
		if ($this->lastJoin !== null) {
			$this->lastJoin->andOn($col, $op, $val);
		}
		return $this;
	}

	/**
	 * @param string|callable|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 */
	public function orOn($col, ?string $op = null, $val = null): self
	{
		if ($this->lastJoin !== null) {
			$this->lastJoin->orOn($col, $op, $val);
		}
		return $this;
	}

	public function groupBy(string $field): self
	{
		$this->groupBy[] = $field;
		return $this;
	}

	/**
	 * @param string|callable|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 */
	public function having($col, ?string $op = null, $val = null): self
	{
		return $this->andHaving($col, $op, $val);
	}

	/**
	 * @param string|callable|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 */
	public function andHaving($col, ?string $op = null, $val = null): self
	{
		$this->having->and($col, $op, $val);
		return $this;
	}

	/**
	 * @param string|callable|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 */
	public function orHaving($col, ?string $op = null, $val = null): self
	{
		$this->having->or($col, $op, $val);
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

	public function reset(): self
	{
		$this->params = [];
		$this->distinct = false;
		$this->limit = 0;
		$this->offset = 0;
		$this->fields->reset();
		$this->tables->reset();
		$this->orderBy->reset();
		$this->groupBy = [];
		$this->where->reset();
		$this->having->reset();
		$this->joins = [];
		$this->lastJoin = null;

		return $this;
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

		/**
		 * @psalm-suppress PossiblyNullArgument
		 */
		$params = array_merge($params, $this->params);

		return implode(' ', $sql);
	}
}
