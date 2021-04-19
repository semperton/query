<?php

declare(strict_types=1);

namespace Semperton\Query\Type;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\Expression\Table;
use Semperton\Query\QueryFactory;
use Semperton\Query\Traits\ExpressionTrait;

final class InsertQuery implements ExpressionInterface
{
	use ExpressionTrait;

	/** @var array */
	protected $values = [];

	/** @var Table */
	protected $tables;

	/** @var bool */
	protected $ignore = false;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
		$this->tables = new Table($factory);
	}

	/**
	 * @param string|callable|ExpressionInterface $table
	 */
	public function into($table): self
	{
		$this->tables->add($table);
		return $this;
	}

	public function ignore(bool $flag = true): self
	{
		$this->ignore = $flag;
		return $this;
	}

	public function values(array $values): self
	{
		$this->values = $values;
		return $this;
	}

	public function isValid(): bool
	{
		return !empty($this->values);
	}

	public function reset(): self
	{
		$this->params = [];
		$this->ignore = false;
		$this->values = [];
		$this->tables->reset();

		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];

		$sql = ['insert'];

		if ($this->ignore) {
			$sql[] = 'ignore';
		}

		if ($this->tables->isValid()) {
			$sql[] = 'into';
			$sql[] = $this->tables->compile($params);
		}

		$sql[] = '(' . implode(', ', array_keys($this->values)) . ')';

		$values = [];
		foreach ($this->values as $col => $value) {

			if ($value instanceof ExpressionInterface) {
				$values[] = $value->compile($params);
			} else {
				$param = $this->factory->newParameter();
				$params[$param] = $value;
				$values[] = $param;
			}
		}

		$sql[] = 'values';
		$sql[] = '(' . implode(', ', $values) . ')';

		/**
		 * @psalm-suppress PossiblyNullArgument
		 */
		$params = array_merge($params, $this->params);

		return implode(' ', $sql);
	}
}
