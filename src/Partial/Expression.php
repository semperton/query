<?php

declare(strict_types=1);

namespace Semperton\Query\Partial;

use InvalidArgumentException;
use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;

class Expression implements ExpressionInterface
{
	protected $params = [];

	protected $value;

	protected $factory;

	public function __construct(QueryFactory $factory, string $value)
	{
		$this->factory = $factory;
		$this->value = $value;
	}

	public function expr(string $value): Expression
	{
		return $this->factory->expr($value);
	}

	public function ident(string $value): Identifier
	{
		return $this->factory->ident($value);
	}

	public function func(string $name, ...$args): Func
	{
		return $this->factory->func($name, ...$args);
	}

	public function debug(): string
	{
		$sql = $this->compile($params);

		foreach ($params as &$value) {
			if (is_string($value)) {
				$value = "'" . $this->factory->escapeString($value) . "'";
			} else if (is_bool($value)) {
				$value = (int)$value;
			}
		}

		$search = array_keys($params);
		$replace = array_values($params);

		return str_replace($search, $replace, $sql);
	}

	public function bind(string $param, $value): self
	{
		if (!is_scalar($value)) {
			throw new InvalidArgumentException('Cannot bind non scalar value');
		}

		$param = ':' . ltrim($param, ':');
		$this->params[$param] = $value;
		return $this;
	}

	public function isValid(): bool
	{
		return !empty($this->value);
	}

	public function reset(): self
	{
		$this->params = [];
		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];
		$params = array_merge($params, $this->params);

		return $this->value;
	}
}
