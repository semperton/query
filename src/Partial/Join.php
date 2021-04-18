<?php

declare(strict_types=1);

namespace Semperton\Query\Partial;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\QueryFactory;

final class Join implements ExpressionInterface
{
	const TYPE_INNER = 'inner';
	const TYPE_LEFT = 'left';
	const TYPET_RIGHT = 'right';

	protected $type;

	protected $tables;

	protected $filter;

	protected $factory;

	public function __construct(QueryFactory $factory, string $type = self::TYPE_INNER)
	{
		$this->factory = $factory;
		$this->tables = new Table($factory);
		$this->filter = new Filter($factory);
		$this->type = $type;
	}

	/**
	 * @param string|callable|ExpressionInterface $table
	 */
	public function table($table, string $alias = ''): self
	{
		$this->tables->add($table, $alias);
		return $this;
	}

	/**
	 * @param string|callable|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 */
	public function on($col, ?string $op = null, $val = null): self
	{
		return $this->andOn($col, $op, $val);
	}

	/**
	 * @param string|callable|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 */
	public function andOn($col, ?string $op = null, $val = null): self
	{
		if (is_string($val)) {
			$val = new Identifier($this->factory, $val);
		}

		$this->filter->and($col, $op, $val);
		return $this;
	}

	/**
	 * @param string|callable|ExpressionInterface $col
	 * @param null|scalar|array|ExpressionInterface $val
	 */
	public function orOn($col, ?string $op = null, $val = null): self
	{
		if (is_string($val)) {
			$val = new Identifier($this->factory, $val);
		}

		$this->filter->or($col, $op, $val);
		return $this;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function isValid(): bool
	{
		return $this->tables->isValid() && $this->filter->isValid();
	}

	public function reset(): self
	{
		$this->tables->reset();
		$this->filter->reset();
		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];
		return $this->type . ' join ' . $this->tables->compile($params) . ' on ' . $this->filter->compile($params);
	}
}
