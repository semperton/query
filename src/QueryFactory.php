<?php

declare(strict_types=1);

namespace Semperton\Query;

use Semperton\Query\Expression\Func;
use Semperton\Query\Expression\Identifier;
use Semperton\Query\Expression\Raw;
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

	public function insert(string $table): InsertQuery
	{
		return (new InsertQuery($this))->into($table);
	}

	public function select(string $table, string $alias = ''): SelectQuery
	{
		return (new SelectQuery($this))->from($table, $alias);
	}

	public function update(string $table, string $alias = ''): UpdateQuery
	{
		return (new UpdateQuery($this))->table($table, $alias);
	}

	public function delete(string $table, string $alias = ''): DeleteQuery
	{
		return (new DeleteQuery($this))->from($table, $alias);
	}

	public function drop(string $table): DropQuery
	{
		return (new DropQuery($this))->table($table);
	}

	public function raw(string $value): Raw
	{
		return new Raw($this, $value);
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
