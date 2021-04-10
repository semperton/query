<?php

declare(strict_types=1);

namespace Semperton\Query\Type;

use Semperton\Query\Partial\Table;
use Semperton\Query\QueryFactory;
use Semperton\Query\Partial\Expression;

final class DropQuery extends Expression
{
	protected $exists = false;

	protected $tables;

	public function __construct(QueryFactory $factory)
	{
		parent::__construct($factory, 'drop');

		$this->tables = new Table($factory);
	}

	public function table(string $name): self
	{
		$this->tables->add($name);
		return $this;
	}

	public function exists(bool $flag = true): self
	{
		$this->exists = $flag;
		return $this;
	}

	public function isValid(): bool
	{
		return $this->tables->isValid();
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];

		$sql = ['drop table'];

		if ($this->exists) {
			$sql[] = 'if exists';
		}

		if ($this->tables->isValid()) {
			$sql[] = $this->tables->compile($params);
		}

		return implode(' ', $sql);
	}
}
