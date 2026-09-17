<?php

declare(strict_types=1);

namespace Mheads\Yii2DataDb\Tests\Unit;

use InvalidArgumentException;
use Mheads\Yii2DataDb\QueryDataReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use yii\db\Expression;
use yii\db\Query;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Sort;

/**
 * @internal
 */
final class QueryDataReaderTest extends TestCase
{
	#[Test]
	public function preparesQueryWithFilterHavingLimitOffsetAndSort(): void
	{
		$query = (new Query())->from('product')->groupBy('category_id');
		$reader = new QueryDataReader(
			query: $query,
			sort: Sort::only(['id'])->withOrderString('-id'),
			offset: 10,
			limit: 5,
			filter: new Equals('name', 'Tablet'),
			having: new Equals('total', 2),
			fieldMap: [
				'total' => new Expression('COUNT(*)'),
			],
		);

		$prepared = $reader->getPreparedQuery();

		self::assertNotSame($query, $prepared);
		self::assertSame(['=', 'name', 'Tablet'], $prepared->where);
		self::assertEquals(['=', new Expression('COUNT(*)'), 2], $prepared->having);
		self::assertSame(['id' => SORT_DESC], $prepared->orderBy);
		self::assertSame(10, $prepared->offset);
		self::assertSame(5, $prepared->limit);
	}

	#[Test]
	public function withMethodsReturnClones(): void
	{
		$reader = new QueryDataReader(new Query());

		$changed = $reader
			->withOffset(3)
			->withLimit(7)
			->withSort(Sort::any()->withOrderString('id'))
			->withFilter(new Equals('status', 'active'))
			->withBatchSize(100)
			->withCountParam('id');

		self::assertNotSame($reader, $changed);
		self::assertSame(0, $reader->getOffset());
		self::assertNull($reader->getLimit());
		self::assertNull($reader->getSort());
		self::assertSame(3, $changed->getOffset());
		self::assertSame(7, $changed->getLimit());
		self::assertNotNull($changed->getSort());
	}

	#[Test]
	public function validatesLimitOffsetAndBatchSize(): void
	{
		$reader = new QueryDataReader(new Query());

		$this->expectException(InvalidArgumentException::class);
		$reader->withLimit(-1);
	}

	#[Test]
	public function validatesOffset(): void
	{
		$reader = new QueryDataReader(new Query());

		$this->expectException(InvalidArgumentException::class);
		$reader->withOffset(-1);
	}

	#[Test]
	public function validatesBatchSize(): void
	{
		$reader = new QueryDataReader(new Query());

		$this->expectException(InvalidArgumentException::class);
		$reader->withBatchSize(0);
	}
}
