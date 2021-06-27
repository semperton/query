<?php

declare(strict_types=1);

namespace Semperton\Query\Type;

use Semperton\Query\ExpressionInterface;
use Semperton\Query\Expression\Table;
use Semperton\Query\QueryFactory;
use Semperton\Query\Traits\ExpressionTrait;

use function implode;
use function array_merge;

final class DropQuery implements ExpressionInterface
{
	use ExpressionTrait;

	/** @var bool */
	protected $exists = false;

	/** @var Table */
	protected $tables;

	public function __construct(QueryFactory $factory)
	{
		$this->factory = $factory;
		$this->tables = new Table($factory);
	}

	public function table(string $name): self
	{
		$this->tables->add($name);
		return $this;
	}

	public function exists(bool $flag = true): self
	{
		$this->exists = $flag;
		return $this;
	}

	public function isValid(): bool
	{
		return $this->tables->isValid();
	}

	public function reset(): self
	{
		$this->params = [];
		$this->exists = false;
		$this->tables->reset();

		return $this;
	}

	public function compile(?array &$params = null): string
	{
		$params = $params ?? [];

		$sql = ['drop table'];

		if ($this->exists) {
			$sql[] = 'if exists';
		}

		if ($this->tables->isValid()) {
			$sql[] = $this->tables->compile($params);
		}

		/**
		 * @psalm-suppress PossiblyNullArgument
		 */
		$params = array_merge($params, $this->params);

		return implode(' ', $sql);
	}
}
