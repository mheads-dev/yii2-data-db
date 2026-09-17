<?php

declare(strict_types=1);

namespace Mheads\Yii2DataDb;

use DateTimeInterface;
use InvalidArgumentException;
use Stringable;
use yii\db\ExpressionInterface;
use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\Filter\AndX;
use Yiisoft\Data\Reader\Filter\Between;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Filter\EqualsNull;
use Yiisoft\Data\Reader\Filter\GreaterThan;
use Yiisoft\Data\Reader\Filter\GreaterThanOrEqual;
use Yiisoft\Data\Reader\Filter\In;
use Yiisoft\Data\Reader\Filter\LessThan;
use Yiisoft\Data\Reader\Filter\LessThanOrEqual;
use Yiisoft\Data\Reader\Filter\Like;
use Yiisoft\Data\Reader\Filter\LikeMode;
use Yiisoft\Data\Reader\Filter\None;
use Yiisoft\Data\Reader\Filter\Not;
use Yiisoft\Data\Reader\Filter\OrX;
use Yiisoft\Data\Reader\FilterInterface;

use function array_map;
use function count;
use function get_debug_type;
use function sprintf;
use function strtr;

final readonly class QueryFilterCompiler
{
	private const array LIKE_ESCAPING_REPLACEMENTS = [
		'%'  => '\%',
		'_'  => '\_',
		'\\' => '\\\\',
	];

	/**
	 * @param array<string, ExpressionInterface|string> $fieldMap
	 */
	public function __construct(
		private array $fieldMap = [],
	) {}

	public function compile(FilterInterface $filter): array|string|ExpressionInterface
	{
		return match (true)
		{
			$filter instanceof All                => [],
			$filter instanceof ConditionFilter    => $filter->condition,
			$filter instanceof None               => '0=1',
			$filter instanceof AndX               => $this->compileGroup('and', $filter->filters),
			$filter instanceof OrX                => $this->compileGroup('or', $filter->filters),
			$filter instanceof Not                => ['not', $this->compile($filter->filter)],
			$filter instanceof Equals             => ['=', $this->mapField($filter->field), $this->normalizeValue($filter->value)],
			$filter instanceof EqualsNull         => ['IS', $this->mapField($filter->field), null],
			$filter instanceof In                 => ['in', $this->mapField($filter->field), array_map($this->normalizeValue(...), $filter->values)],
			$filter instanceof Like               => $this->compileLike($filter),
			$filter instanceof GreaterThan        => ['>', $this->mapField($filter->field), $this->normalizeValue($filter->value)],
			$filter instanceof GreaterThanOrEqual => ['>=', $this->mapField($filter->field), $this->normalizeValue($filter->value)],
			$filter instanceof LessThan           => ['<', $this->mapField($filter->field), $this->normalizeValue($filter->value)],
			$filter instanceof LessThanOrEqual    => ['<=', $this->mapField($filter->field), $this->normalizeValue($filter->value)],
			$filter instanceof Between            => [
				'between',
				$this->mapField($filter->field),
				$this->normalizeValue($filter->minValue),
				$this->normalizeValue($filter->maxValue),
			],
			default => throw new InvalidArgumentException(
				sprintf('Unsupported data filter "%s".', get_debug_type($filter)),
			),
		};
	}

	/**
	 * @param array<array-key, FilterInterface> $filters
	 */
	private function compileGroup(string $operator, array $filters): array|string|ExpressionInterface
	{
		$conditions = [];

		foreach ($filters as $filter)
		{
			$condition = $this->compile($filter);

			if ($condition === [])
			{
				continue;
			}

			$conditions[] = $condition;
		}

		if ($conditions === [])
		{
			return [];
		}

		if (count($conditions) === 1)
		{
			return $conditions[0];
		}

		return [$operator, ...$conditions];
	}

	private function compileLike(Like $filter): array
	{
		if ($filter->caseSensitive === false)
		{
			throw new InvalidArgumentException('Case-insensitive LIKE is not supported by Yii2 query condition compiler.');
		}

		$value = (string)$filter->value;

		return match ($filter->mode)
		{
			LikeMode::Contains   => ['like', $this->mapField($filter->field), $value],
			LikeMode::StartsWith => ['like', $this->mapField($filter->field), $this->escapeLikeValue($value) . '%', false],
			LikeMode::EndsWith   => ['like', $this->mapField($filter->field), '%' . $this->escapeLikeValue($value), false],
		};
	}

	private function escapeLikeValue(string $value): string
	{
		return strtr($value, self::LIKE_ESCAPING_REPLACEMENTS);
	}

	private function mapField(string $field): string|ExpressionInterface
	{
		return $this->fieldMap[$field] ?? $field;
	}

	private function normalizeValue(bool|DateTimeInterface|float|int|string|Stringable $value): bool|float|int|string
	{
		if ($value instanceof DateTimeInterface)
		{
			return $value->format('Y-m-d H:i:s');
		}

		if ($value instanceof Stringable)
		{
			return (string)$value;
		}

		return $value;
	}
}
