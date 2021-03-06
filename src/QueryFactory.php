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

use function explode;
use function implode;
use function str_replace;

class QueryFactory
{
	const QUOTE_STR_DEFAULT = '"';
	const QUOTE_STR_MYSQL = '`';
	const QUOTE_STR_MSSQL = '[';

	const ESCAPE_STR_DEFAULT = "'";

	/** @var int */
	protected $parameterCount = 0;

	/** @var string */
	protected $parameterPrefix = 'p';

	/** @var bool */
	protected $quoting;

	/** @var string */
	protected $quoteStr;

	/** @var string */
	protected $escapeStr;

	public function __construct(
		bool $quoting = false,
		string $quoteStr = self::QUOTE_STR_DEFAULT,
		string $escapeStr = self::ESCAPE_STR_DEFAULT
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

	/**
	 * @param scalar|ExpressionInterface $args
	 */
	public function func(string $name, ...$args): Func
	{
		return new Func($this, $name, ...$args);
	}

	public function nextParam(): string
	{
		$num = ++$this->parameterCount;
		return ':' . $this->parameterPrefix . $num;
	}

	public function getQuoteString(): string
	{
		return $this->quoteStr;
	}

	public function getEscapeString(): string
	{
		return $this->escapeStr;
	}

	public function usingQuotes(): bool
	{
		return $this->quoting;
	}

	public function maybeQuote(string $field): string
	{
		if ($this->quoting) {
			$field = $this->quoteIdentifier($field);
		}

		return $field;
	}

	public function quoteIdentifier(string $field): string
	{
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
		$escape = $this->escapeStr;
		return str_replace($escape, $escape . $escape, $str);
	}

	protected function sqlQuote(string $str): string
	{
		$quote = $this->quoteStr;

		if ($quote === self::QUOTE_STR_MSSQL) {

			$str = str_replace(['[', ']'], ['[[', ']]'], $str);
			return '[' . $str . ']';
		}

		$str = str_replace($quote, $quote . $quote, $str);
		return $quote . $str . $quote;
	}
}
