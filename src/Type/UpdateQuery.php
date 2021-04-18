<?php

declare(strict_types=1);

namespace Semperton\Query\Type;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\Partial\Filter;
use Semperton\Query\Partial\Order;
use Semperton\Query\Partial\Table;
use Semperton\Query\QueryFactory;
use Semperton\Query\Trait\ExpressionTrait;
use Semperton\Query\Trait\LimitTrait;
use Semperton\Query\Trait\OrderByTrait;
use Semperton\Query\Trait\WhereTrait;

final class UpdateQuery implements ExpressionInterface
{
	use ExpressionTrait;
	use OrderByTrait;
	use WhereTrait;
	use LimitTrait;

	protected $values = [];

	protected $tables;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
		$this->tables = new Table($factory);
		$this->orderBy = new Order($factory);
		$this->where = new Filter($factory);
	}

	/**
	 * @param string|callable|ExpressionInterface $table
	 */
	public function table($table, string $alias = ''): self
	{
		$this->tables->add($table, $alias);
		return $this;
	}

	/**
	 * @param string|array $field
	 * @param null|scalar|ExpressionInterface $value
	 */
	public function set($field, $value = null): self
	{
		if (!is_array($field)) {
			$field = [$field => $value];
		}

		$this->values = array_merge($this->values, $field);

		return $this;
	}

	public function isValid(): bool
	{
		return !empty($this->values) && $this->tables->isValid();
	}

	public function reset(): self
	{
		$this->params = [];
		$this->limit = 0;
		$this->values = [];
		$this->tables->reset();
		$this->orderBy->reset();
		$this->where->reset();

		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];

		$sql = ['update'];

		if ($this->tables->isValid()) {
			$sql[] = $this->tables->compile($params);
		}

		$sql[] = 'set';

		foreach ($this->values as $field => $value) {

			if ($value instanceof ExpressionInterface) {
				$assign[] = $field . ' = ' . $value->compile($params);
			} else {
				$param = $this->factory->newParameter();
				$assign[] = $field . ' = ' . $param;
				$params[$param] = $value;
			}
		}

		$sql[] = implode(', ', $assign);

		if ($this->where->isValid()) {
			$sql[] = 'where';
			$sql[] = $this->where->compile($params);
		}

		if ($this->orderBy->isValid()) {
			$sql[] = 'order by';
			$sql[] = $this->orderBy->compile($params);
		}

		if ($this->limit > 0) {
			$param = $this->factory->newParameter();
			$params[$param] = $this->limit;
			$sql[] = 'limit ' . $param;
		}

		// merge user params
		$params = array_merge($params, $this->params);

		return implode(' ', $sql);
	}
}
