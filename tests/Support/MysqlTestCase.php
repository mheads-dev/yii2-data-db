<?php

declare(strict_types=1);

namespace Mheads\Yii2DataDb\Tests\Support;

use PHPUnit\Framework\TestCase;
use yii\db\Connection;

use function getenv;

abstract class MysqlTestCase extends TestCase
{
	private static Connection $db;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		self::$db = self::createConnection();
		DbFixture::loadMysql(self::$db);
		YiiDbProvider::set(self::$db);
	}

	public static function tearDownAfterClass(): void
	{
		self::$db->close();
		YiiDbProvider::remove();

		parent::tearDownAfterClass();
	}

	protected static function db(): Connection
	{
		return self::$db;
	}

	private static function createConnection(): Connection
	{
		$database = getenv('YII_MYSQL_DATABASE') ?: 'mhfs-test';
		$host = getenv('YII_MYSQL_HOST') ?: '127.0.0.1';
		$port = getenv('YII_MYSQL_PORT') ?: '3306';
		$user = getenv('YII_MYSQL_USER') ?: 'yii';
		$password = getenv('YII_MYSQL_PASSWORD') ?: 'q1w2e3r4';

		return new Connection([
			'dsn'      => "mysql:host=$host;dbname=$database;port=$port",
			'username' => $user,
			'password' => $password,
			'charset'  => 'utf8mb4',
		]);
	}
}
