<?php

declare(strict_types=1);

namespace Semperton\Query\Type;

use Closure;
use Semperton\Query\ExpressionInterface;
use Semperton\Query\Expression\Filter;
use Semperton\Query\Expression\Order;
use Semperton\Query\Expression\Table;
use Semperton\Query\QueryFactory;
use Semperton\Query\Traits\ExpressionTrait;
use Semperton\Query\Traits\LimitTrait;
use Semperton\Query\Traits\OrderByTrait;
use Semperton\Query\Traits\WhereTrait;

use function is_array;
use function array_merge;
use function implode;

final class UpdateQuery implements ExpressionInterface
{
	use ExpressionTrait;
	use OrderByTrait;
	use WhereTrait;
	use LimitTrait;

	/** @var array<array-key, null|scalar|ExpressionInterface> */
	protected $values = [];

	/** @var Table */
	protected $tables;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
		$this->tables = new Table($factory);
		$this->orderBy = new Order($factory);
		$this->where = new Filter($factory);
	}

	/**
	 * @param string|Closure|ExpressionInterface $table
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

		/** @psalm-suppress MixedPropertyTypeCoercion */
		$this->values = array_merge($this->values, $field);

		return $this;
	}

	public function isValid(): bool
	{
		return !!$this->values && $this->tables->isValid();
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

		$assign = [];
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

		/**
		 * @psalm-suppress PossiblyNullArgument
		 */
		$params = array_merge($params, $this->params);

		return implode(' ', $sql);
	}
}
