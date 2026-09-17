<?php

declare(strict_types=1);

namespace Mheads\Yii2DataDb\Tests\Support;

use RuntimeException;
use yii\db\Connection;

final class YiiDbProvider
{
	private static ?Connection $db = null;

	public static function set(Connection $db): void
	{
		self::$db = $db;
	}

	public static function remove(): void
	{
		self::$db = null;
	}

	public static function get(): Connection
	{
		return self::$db ?? throw new RuntimeException('Test DB connection is not configured.');
	}
}
