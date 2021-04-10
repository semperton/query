<?php

declare(strict_types=1);

namespace Semperton\Query\Type;

use Semperton\Query\Partial\Expression;
use Semperton\Query\Partial\Table;
use Semperton\Query\QueryFactory;

final class InsertQuery extends Expression
{
	protected $values = [];

	protected $tables;

	protected $ignore = false;

	public function __construct(QueryFactory $factory)
	{
		parent::__construct($factory, 'insert');

		$this->tables = new Table($factory);
	}

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

		foreach ($this->values as $col => $value) {

			if ($value instanceof Expression) {
				$values[] = $value->compile($params);
			} else {
				$param = $this->factory->newParameter();
				$params[$param] = $value;
				$values[] = $param;
			}
		}

		$sql[] = 'values';
		$sql[] = '(' . implode(', ', $values) . ')';

		return implode(' ', $sql);
	}
}
