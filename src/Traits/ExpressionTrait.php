<?php

declare(strict_types=1);

namespace Semperton\Query\Traits;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\Expression\Func;
use Semperton\Query\Expression\Identifier;
use Semperton\Query\Expression\Raw;
use Semperton\Query\QueryFactory;

trait ExpressionTrait
{
	/** @var array<string, scalar> */
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

	/**
	 * @psalm-suppress all
	 */
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
		$param = ':' . ltrim($param, ':');
		$this->params[$param] = $value;
		return $this;
	}
}
