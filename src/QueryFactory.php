<?php

declare(strict_types=1);

namespace Semperton\Query;

use Semperton\Query\Partial\Expression;
use Semperton\Query\Partial\Func;
use Semperton\Query\Partial\Identifier;
use Semperton\Query\Type\DeleteQuery;
use Semperton\Query\Type\DropQuery;
use Semperton\Query\Type\InsertQuery;
use Semperton\Query\Type\SelectQuery;
use Semperton\Query\Type\UpdateQuery;

class QueryFactory
{
	protected $parameterCount = 0;

	protected $parameterName = 'p';

	protected $quoting;

	protected $quoteStr;

	protected $escapeStr;

	public function __construct(
		bool $quoting = false,
		string $quoteStr = '"',
		string $escapeStr = "'"
	) {
		$this->quoting = $quoting;
		$this->quoteStr = $quoteStr;
		$this->escapeStr = $escapeStr;
	}

	public function insert(array $values = []): InsertQuery
	{
		$query = new InsertQuery($this);
		return $query->values($values);
	}

	public function select(array $fields = []): SelectQuery
	{
		$query = new SelectQuery($this);
		return $query->fields($fields);
	}

	public function update(array $values = []): UpdateQuery
	{
		$query = new UpdateQuery($this);
		return $query->set($values);
	}

	public function delete(): DeleteQuery
	{
		return new DeleteQuery($this);
	}

	public function drop(?string $table = null): DropQuery
	{
		$query = new DropQuery($this);
		if ($table !== null) {
			$query->table($table);
		}
		return $query;
	}

	public function expr(string $value): Expression
	{
		return new Expression($this, $value);
	}

	public function ident(string $value): Identifier
	{
		return new Identifier($this, $value);
	}

	public function func(string $name, ...$args): Func
	{
		return new Func($this, $name, ...$args);
	}

	public function newParameter(): string
	{
		$num = ++$this->parameterCount;
		return ':' . $this->parameterName . $num;
	}

	public function quoteIdentifier(string $field): string
	{
		if (!$this->quoting) {
			return $field;
		}

		$parts = explode('.', $field);

		foreach ($parts as &$part) {

			if ($part === '*') {
				continue;
			}

			$part = $this->sqlQuote($part);
		}

		return implode('.', $parts);
	}

	public function escapeString(string $str): string
	{
		return $this->sqlEscape($str);
	}

	protected function sqlQuote(string $str): string
	{
		$quote = $this->quoteStr;
		$str = str_replace($quote, $quote . $quote, $str);
		return $quote . $str . $quote;
	}

	protected function sqlEscape(string $str): string
	{
		$escape = $this->escapeStr;
		return str_replace($escape, $escape . $escape, $str);
	}
}
