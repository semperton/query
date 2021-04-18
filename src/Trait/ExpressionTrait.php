<?php

declare(strict_types=1);

namespace Semperton\Query\Trait;

use InvalidArgumentException;
use Semperton\Query\Partial\Func;
use Semperton\Query\Partial\Identifier;
use Semperton\Query\Partial\Raw;
use Semperton\Query\QueryFactory;

trait ExpressionTrait
{
	protected $params = [];

	/** @var QueryFactory */
	protected $factory;

	public function raw(string $value): Raw
	{
		return $this->factory->raw($value);
	}

	public function ident(string $value): Identifier
	{
		return $this->factory->ident($value);
	}

	/**
	 * @param scalar|ExpressionInterface $args
	 */
	public function func(string $name, ...$args): Func
	{
		return $this->factory->func($name, ...$args);
	}

	public function debug(): string
	{
		$params = [];
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

	/**
	 * @param scalar $value
	 * @return static
	 */
	public function bind(string $param, $value): self
	{
		if (!is_scalar($value)) {
			throw new InvalidArgumentException('Cannot bind non scalar value');
		}

		$param = ':' . ltrim($param, ':');
		$this->params[$param] = $value;
		return $this;
	}
}
