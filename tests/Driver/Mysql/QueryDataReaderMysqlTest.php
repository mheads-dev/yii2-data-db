<?php

declare(strict_types=1);

namespace Mheads\Yii2DataDb\Tests\Driver\Mysql;

use Mheads\Yii2DataDb\QueryDataReader;
use Mheads\Yii2DataDb\Tests\Stubs\ActiveRecord\Product;
use Mheads\Yii2DataDb\Tests\Support\MysqlTestCase;
use PHPUnit\Framework\Attributes\Test;
use yii\db\Expression;
use yii\db\Query;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Sort;

/**
 * @internal
 */
final class QueryDataReaderMysqlTest extends MysqlTestCase
{
	#[Test]
	public function readsFilteredAndSortedRows(): void
	{
		$query = (new Query())->from('product');
		$reader = new QueryDataReader(
			query: $query,
			db: self::db(),
			sort: Sort::only(['id'])->withOrderString('-id'),
			filter: new Equals('name', 'Tablet'),
		);

		$rows = iterator_to_array($reader->read(), false);

		self::assertCount(1, $rows);
		self::assertSame('2', (string)$rows[0]['id']);
		self::assertSame('Tablet', $rows[0]['name']);
	}

	#[Test]
	public function paginatesRowsWithOffsetPaginator(): void
	{
		$query = (new Query())->from('product');
		$reader = new QueryDataReader(
			query: $query,
			db: self::db(),
			sort: Sort::only(['id'])->withOrderString('id'),
		);

		$paginator = (new OffsetPaginator($reader))
			->withPageSize(1)
			->withCurrentPage(2);

		$rows = iterator_to_array($paginator->read(), false);

		self::assertCount(1, $rows);
		self::assertSame('2', (string)$rows[0]['id']);
		self::assertSame('Tablet', $rows[0]['name']);
		self::assertSame(7, $paginator->getTotalPages());
	}

	#[Test]
	public function readsRowsInBatches(): void
	{
		$query = (new Query())->from('product');
		$reader = (new QueryDataReader(
			query: $query,
			db: self::db(),
			sort: Sort::only(['id'])->withOrderString('id'),
		))->withBatchSize(2);

		$rows = iterator_to_array($reader->read(), false);

		self::assertCount(7, $rows);
		self::assertSame('1', (string)$rows[0]['id']);
		self::assertSame('7', (string)$rows[6]['id']);
	}

	#[Test]
	public function appliesHavingWithFieldMap(): void
	{
		$query = (new Query())
			->select(['category', 'total' => new Expression('COUNT(*)')])
			->from('product')
			->groupBy('category');
		$reader = (new QueryDataReader(
			query: $query,
			db: self::db(),
			fieldMap: ['total' => new Expression('COUNT(*)')],
		))->withHaving(new Equals('total', 3));

		$rows = iterator_to_array($reader->read(), false);

		self::assertCount(1, $rows);
		self::assertSame('accessory', $rows[0]['category']);
		self::assertSame('3', (string)$rows[0]['total']);
	}

	#[Test]
	public function readsActiveRecordObjects(): void
	{
		$reader = new QueryDataReader(
			query: Product::find(),
			sort: Sort::only(['id'])->withOrderString('-id'),
			filter: new Equals('category', 'mobile'),
		);

		$rows = iterator_to_array($reader->read(), false);
		$first = $reader->readOne();

		self::assertCount(2, $rows);
		self::assertContainsOnlyInstancesOf(Product::class, $rows);
		self::assertSame(2, $rows[0]->id);
		self::assertSame('Tablet', $rows[0]->name);
		self::assertInstanceOf(Product::class, $first);
		self::assertSame('Tablet', $first->name);
		self::assertSame(2, $reader->count());
	}
}
