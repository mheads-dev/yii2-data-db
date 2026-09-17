# Yii2 Data DB

Адаптер `yii\db\QueryInterface` для использования как источник данных [`yiisoft/data`](https://github.com/yiisoft/data).

```text
Yii2 Query / ActiveQuery -> Yiisoft\Data\Reader\DataReaderInterface
```

## Установка

```bash
composer require mheads/yii2-data-db
```

## Использование

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

`yii\db\ActiveQuery` тоже подходит:

```php
$reader = new QueryDataReader(
	query: Product::find(),
);
```

## Что есть

- `QueryDataReader` - источник данных для запросов Yii2.
- `QueryDataReaderInterface` - контракт источника данных.
- `QueryFilterCompiler` - преобразование фильтров из `yiisoft/data` в условия Yii2.
- `ConditionFilter` - готовое условие Yii2 как `FilterInterface`.

`QueryDataReader` поддерживает фильтрацию, сортировку, ограничение выборки, смещение, подсчет, `HAVING` и чтение порциями.

## Карта полей

Если публичное поле отличается от SQL-поля:

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

## Готовое условие

```php
use Mheads\Yii2DataDb\ConditionFilter;

$reader = $reader->withFilter(
	new ConditionFilter(['or', ['status' => 'new'], ['status' => 'paid']]),
);
```
