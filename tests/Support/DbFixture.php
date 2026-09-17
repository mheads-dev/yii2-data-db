<?php

declare(strict_types=1);

namespace Mheads\Yii2DataDb\Tests\Support;

use yii\db\Connection;

use function explode;
use function file_get_contents;
use function trim;

final class DbFixture
{
	public static function loadMysql(Connection $db): void
	{
		if ($db->isActive)
		{
			$db->close();
		}

		$db->open();

		$content = file_get_contents(dirname(__DIR__) . '/data/mysql.sql');
		if ($content === false)
		{
			return;
		}

		foreach (explode(';', $content) as $statement)
		{
			if (trim($statement) !== '')
			{
				$db->pdo->exec($statement);
			}
		}
	}
}
