<?php

declare(strict_types=1);

namespace Semperton\Query\Type;

use Semperton\Query\Partial\Filter;
use Semperton\Query\Partial\Order;
use Semperton\Query\Partial\Table;
use Semperton\Query\QueryFactory;
use Semperton\Query\Partial\Expression;
use Semperton\Query\Trait\LimitTrait;
use Semperton\Query\Trait\OrderByTrait;
use Semperton\Query\Trait\WhereTrait;

final class DeleteQuery extends Expression
{
	use WhereTrait;
	use OrderByTrait;
	use LimitTrait;

	protected $tables;

	public function __construct(QueryFactory $factory)
	{
		parent::__construct($factory, 'delete');

		$this->tables = new Table($factory);
		$this->where = new Filter($factory);
		$this->orderBy = new Order($factory);
	}

	public function from($table, string $alias = ''): self
	{
		$this->tables->add($table, $alias);
		return $this;
	}

	public function isValid(): bool
	{
		return $this->tables->isValid();
	}

	public function reset(): self
	{
		parent::reset();

		$this->limit = 0;
		$this->tables->reset();
		$this->where->reset();
		$this->orderBy->reset();

		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];

		$sql = ['delete'];

		if ($this->tables->isValid()) {

			$sql[] = 'from';
			$sql[] = $this->tables->compile($params);
		}

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

		// add user params
		$params = array_merge($params, $this->params);

		return implode(' ', $sql);
	}
}
