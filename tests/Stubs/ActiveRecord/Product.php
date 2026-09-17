<?php

declare(strict_types=1);

namespace Mheads\Yii2DataDb\Tests\Stubs\ActiveRecord;

use Mheads\Yii2DataDb\Tests\Support\YiiDbProvider;
use Override;
use yii\db\ActiveRecord;
use yii\db\Connection;

/**
 * @property int $id
 * @property string $name
 * @property string|null $category
 * @property string $created_at
 * @property string $created_at_dt
 * @property int $created_at_ts
 */
final class Product extends ActiveRecord
{
	#[Override]
	public static function tableName(): string
	{
		return 'product';
	}

	#[Override]
	public static function getDb(): Connection
	{
		return YiiDbProvider::get();
	}
}
