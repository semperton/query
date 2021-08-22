<?php

declare(strict_types=1);

namespace Semperton\Query\Expression;

use Semperton\Query\QueryFactory;
use Semperton\Query\ExpressionInterface;

use function implode;

final class Func implements ExpressionInterface
{
	/** @var string */
	protected $name;

	/** @var array<scalar|ExpressionInterface> */
	protected $args;

	/** @var QueryFactory */
	protected $factory;

	/**
	 * @param scalar|ExpressionInterface $args
	 */
	public function __construct(QueryFactory $factory, string $name, ...$args)
	{
		$this->factory = $factory;
		$this->name = $name;
		$this->args = $args;
	}

	public function isValid(): bool
	{
		return $this->name !== '';
	}

	public function reset(): self
	{
		return $this;
	}

	public function compile(array &$params = []): string
	{
		$sql = [];

		foreach ($this->args as $arg) {

			if ($arg instanceof ExpressionInterface) {
				$sql[] = $arg->compile($params);
			} else {
				$param = $this->factory->nextParam();
				$params[$param] = $arg;
				$sql[] = $param;
			}
		}

		return $this->name . '(' . implode(', ', $sql) . ')';
	}
}
