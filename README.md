# Yii2 Data DB

Adapter for using `yii\db\QueryInterface` as a [`yiisoft/data`](https://github.com/yiisoft/data) data reader.

Russian documentation: [README.ru.md](README.ru.md).

```text
Yii2 Query / ActiveQuery -> Yiisoft\Data\Reader\DataReaderInterface
```

## Installation

```bash
composer require mheads/yii2-data-db
```

## Usage

```php
use Mheads\Yii2DataDb\QueryDataReader;
use yii\db\Query;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Sort;

$reader = new QueryDataReader(
	query: (new Query())->from('product'),
	db: Yii::$app->db,
	sort: Sort::only(['id'])->withOrderString('-id'),
	filter: new Equals('category', 'mobile'),
);

$rows = iterator_to_array($reader->read(), false);
$count = $reader->count();
```

`yii\db\ActiveQuery` is supported too:

```php
$reader = new QueryDataReader(
	query: Product::find(),
);
```

## Included

- `QueryDataReader` - data reader for Yii2 queries.
- `QueryDataReaderInterface` - data reader contract.
- `QueryFilterCompiler` - converts `yiisoft/data` filters into Yii2 conditions.
- `ConditionFilter` - existing Yii2 condition as `FilterInterface`.

`QueryDataReader` supports filtering, sorting, limit, offset, count, `HAVING`, and batched reading.

## Field Map

Use `fieldMap` when public field names differ from SQL field names:

```php
$reader = new QueryDataReader(
	query: $query,
	db: Yii::$app->db,
	fieldMap: [
		'createdAt' => 'created_at',
		'total' => new yii\db\Expression('COUNT(*)'),
	],
);
```

## Raw Condition

```php
use Mheads\Yii2DataDb\ConditionFilter;

$reader = $reader->withFilter(
	new ConditionFilter(['or', ['status' => 'new'], ['status' => 'paid']]),
);
```
