<?php

declare(strict_types=1);

namespace Semperton\Query\Traits;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\Expression\Func;
use Semperton\Query\Expression\Identifier;
use Semperton\Query\Expression\Raw;
use Semperton\Query\QueryFactory;

use function is_string;
use function is_bool;
use function array_keys;
use function array_values;
use function str_replace;
use function ltrim;

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

	public function debug(): string
	{
		$params = [];
		$sql = $this->compile($params);

		/** @var array<array-key, scalar> $params */

		$escapeStr = $this->factory->getEscapeString();

		foreach ($params as &$value) {
			if (is_string($value)) {
				$value = $escapeStr . $this->factory->escapeString($value) . $escapeStr;
			} else if (is_bool($value)) {
				$value = (int)$value;
			}
		}

		/** @var array<array-key, float|int|string> $params */

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
