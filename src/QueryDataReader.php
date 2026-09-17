<?php

declare(strict_types=1);

namespace Mheads\Yii2DataDb;

use BadMethodCallException;
use Generator;
use InvalidArgumentException;
use Override;
use RuntimeException;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\ExpressionInterface;
use yii\db\QueryInterface;
use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\FilterInterface;
use Yiisoft\Data\Reader\Sort;

use function count;
use function is_array;
use function is_string;
use function method_exists;
use function sprintf;

/**
 * @template TKey as array-key
 * @template TValue as array|object
 *
 * @implements QueryDataReaderInterface<TKey, TValue>
 */
final class QueryDataReader implements QueryDataReaderInterface
{
	/**
	 * @psalm-var non-negative-int|null
	 */
	private ?int $count = null;

	/**
	 * @psalm-var array<TKey, TValue>|null
	 */
	private ?array $cache = null;

	/**
	 * @param non-negative-int $offset
	 * @param non-negative-int|null $limit
	 * @param positive-int|null $batchSize
	 * @param array<string, ExpressionInterface|string> $fieldMap
	 */
	public function __construct(
		private readonly QueryInterface $query,
		private readonly ?Connection $db = null,
		private ?Sort $sort = null,
		private int $offset = 0,
		private ?int $limit = null,
		private ?string $countParam = null,
		private FilterInterface $filter = new All(),
		private FilterInterface $having = new All(),
		private ?int $batchSize = null,
		private readonly array $fieldMap = [],
	) {}

	/**
	 * @psalm-return Generator<TKey, TValue, mixed, void>
	 */
	#[Override]
	public function getIterator(): Generator
	{
		if ($this->batchSize !== null)
		{
			yield from $this->readBatch($this->getPreparedQuery());
			return;
		}

		if (is_array($this->cache))
		{
			yield from $this->cache;
			return;
		}

		/** @psalm-var array<TKey, TValue> */
		$this->cache = $this->getPreparedQuery()->all($this->db);
		yield from $this->cache;
	}

	/**
	 * @psalm-return Generator<TKey, TValue, mixed, void>
	 */
	#[Override]
	public function read(): Generator
	{
		return $this->getIterator();
	}

	/**
	 * @psalm-return TValue|null
	 */
	#[Override]
	public function readOne(): array|object|null
	{
		if (is_array($this->cache))
		{
			$key = array_key_first($this->cache);
			return $key === null ? null : $this->cache[$key];
		}

		return $this->withLimit(1)->getIterator()->current();
	}

	/**
	 * @psalm-return non-negative-int
	 */
	#[Override]
	public function count(): int
	{
		if ($this->count === null)
		{
			$q = $this->countParam ?? '*';

			if ($q === '*' && is_array($this->cache) && $this->limit === null && $this->offset === 0)
			{
				$this->count = count($this->cache);
			}
			else
			{
				$query = $this->getPreparedQuery();
				$query->offset(null);
				$query->limit(null);
				$query->orderBy('');

				$count = $query->count($q, $this->db);
				if (is_string($count))
				{
					throw new RuntimeException(
						sprintf(
							'Number of records is too large to fit into a PHP integer. Got %s.',
							$count,
						),
					);
				}

				$count = (int)$count;
				if ($count < 0)
				{
					throw new RuntimeException(
						sprintf(
							'Number of records must not be less than 0. Got %d.',
							$count,
						),
					);
				}

				$this->count = $count;
			}
		}

		return $this->count;
	}

	public function getPreparedQuery(): QueryInterface
	{
		$query = clone $this->query;

		$this->applyFilter($query);
		$this->applyHaving($query);

		if ($this->limit !== null)
		{
			$query->limit($this->limit);
		}

		$query->offset($this->offset);

		if ($this->sort !== null)
		{
			$query->addOrderBy(
				$this->convertSortToOrderBy($this->sort),
			);
		}

		return $query;
	}

	/**
	 * @return static The new instance with the specified offset.
	 *
	 * @psalm-mutation-free
	 * @psalm-return static<TKey, TValue>
	 */
	#[Override]
	public function withOffset(int $offset): static
	{
		if ($offset < 0)
		{
			throw new InvalidArgumentException('$offset must not be less than 0.');
		}

		$new = clone $this;
		$new->cache = null;
		$new->offset = $offset;
		return $new;
	}

	/**
	 * @return static The new instance with the specified limit.
	 *
	 * @psalm-mutation-free
	 * @psalm-return static<TKey, TValue>
	 */
	#[Override]
	public function withLimit(?int $limit): static
	{
		if ($limit !== null && $limit < 0)
		{
			throw new InvalidArgumentException('$limit must not be less than 0.');
		}

		$new = clone $this;
		$new->cache = null;
		$new->limit = $limit;
		return $new;
	}

	/**
	 * @return static The new instance with the specified count parameter.
	 *
	 * @psalm-mutation-free
	 * @psalm-return static<TKey, TValue>
	 */
	#[Override]
	public function withCountParam(?string $countParam): static
	{
		if ($this->countParam === $countParam)
		{
			return $this;
		}

		$new = clone $this;
		$new->count = null;
		$new->countParam = $countParam;
		return $new;
	}

	/**
	 * @return static The new instance with the specified sort.
	 *
	 * @psalm-mutation-free
	 * @psalm-return static<TKey, TValue>
	 */
	#[Override]
	public function withSort(?Sort $sort): static
	{
		$new = clone $this;
		$new->cache = null;
		$new->sort = $sort;
		return $new;
	}

	/**
	 * @return static The new instance with the specified filter.
	 *
	 * @psalm-mutation-free
	 * @psalm-return static<TKey, TValue>
	 */
	#[Override]
	public function withFilter(FilterInterface $filter): static
	{
		$new = clone $this;
		$new->filter = $filter;
		$new->count = $new->cache = null;
		return $new;
	}

	/**
	 * @return static The new instance with the specified having condition.
	 *
	 * @psalm-mutation-free
	 * @psalm-return static<TKey, TValue>
	 */
	#[Override]
	public function withHaving(FilterInterface $having): static
	{
		$new = clone $this;
		$new->having = $having;
		$new->count = $new->cache = null;
		return $new;
	}

	/**
	 * @return static The new instance with the specified batch size.
	 *
	 * @psalm-mutation-free
	 * @psalm-return static<TKey, TValue>
	 */
	#[Override]
	public function withBatchSize(?int $batchSize): static
	{
		if ($batchSize !== null && $batchSize < 1)
		{
			throw new InvalidArgumentException('$batchSize cannot be less than 1.');
		}

		$new = clone $this;
		$new->batchSize = $batchSize;
		return $new;
	}

	#[Override]
	public function getSort(): ?Sort
	{
		return $this->sort;
	}

	#[Override]
	public function getFilter(): FilterInterface
	{
		return $this->filter;
	}

	#[Override]
	public function getLimit(): ?int
	{
		return $this->limit;
	}

	#[Override]
	public function getOffset(): int
	{
		return $this->offset;
	}

	private function convertSortToOrderBy(Sort $sort): array
	{
		$result = [];

		foreach ($sort->getCriteria() as $field => $direction)
		{
			$field = $this->mapField($field);

			if (is_string($field))
			{
				$result[$field] = $direction;
			}
			else
			{
				$result[] = $this->buildOrderExpression($field, $direction);
			}
		}

		return $result;
	}

	private function applyFilter(QueryInterface $query): void
	{
		$condition = (new QueryFilterCompiler($this->fieldMap))->compile($this->filter);

		if ($condition === [])
		{
			return;
		}

		/** @psalm-suppress PossiblyInvalidArgument Yii2 supports array, string and ExpressionInterface conditions. */
		$query->andWhere($condition);
	}

	private function applyHaving(QueryInterface $query): void
	{
		$condition = (new QueryFilterCompiler($this->fieldMap))->compile($this->having);

		if ($condition === [])
		{
			return;
		}

		if (!method_exists($query, 'andHaving'))
		{
			throw new BadMethodCallException(
				sprintf('Query "%s" does not support having conditions.', $query::class),
			);
		}

		$query->andHaving($condition);
	}

	private function mapField(string $field): string|ExpressionInterface
	{
		return $this->fieldMap[$field] ?? $field;
	}

	private function buildOrderExpression(ExpressionInterface $field, int $direction): Expression
	{
		if (!method_exists($field, '__toString'))
		{
			throw new InvalidArgumentException(
				sprintf('Sort expression "%s" must be stringable.', $field::class),
			);
		}

		return new Expression((string)$field . ($direction === SORT_ASC ? ' ASC' : ' DESC'));
	}

	/**
	 * @return Generator<TKey, TValue, mixed, void>
	 */
	private function readBatch(QueryInterface $query): Generator
	{
		if (!method_exists($query, 'batch'))
		{
			throw new BadMethodCallException(
				sprintf('Query "%s" does not support batch reading.', $query::class),
			);
		}

		foreach ($query->batch($this->batchSize, $this->db) as $data)
		{
			/** @psalm-var array<TKey, TValue> $data */
			yield from $data;
		}
	}
}
