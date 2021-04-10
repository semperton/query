<?php

declare(strict_types=1);

namespace Semperton\Query\Partial;

use RuntimeException;
use Semperton\Query\QueryFactory;
use Semperton\Query\ExpressionInterface;

final class Func implements ExpressionInterface
{
	protected $name;

	protected $args;

	protected $factory;

	public function __construct(QueryFactory $factory, string $name, ...$args)
	{
		$this->factory = $factory;
		$this->name = $name;
		$this->args = $args;
	}

	public function isValid(): bool
	{
		return !empty($this->name);
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];
		$sql = [];

		foreach ($this->args as $arg) {
			if ($arg instanceof ExpressionInterface) {
				$sql[] = $arg->compile($params);
			} else if (is_scalar($arg)) {
				$param = $this->factory->newParameter();
				$params[$param] = $arg;
				$sql[] = $param;
			} else {
				throw new RuntimeException('Type < ' . gettype($arg) . ' > is not a valid function argument');
			}
		}

		return $this->name . '(' . implode(', ', $sql) . ')';
	}
}
