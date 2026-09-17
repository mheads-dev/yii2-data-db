<?php

declare(strict_types=1);

namespace Mheads\Yii2DataDb\Tests\Unit;

use DateTimeImmutable;
use InvalidArgumentException;
use Mheads\Yii2DataDb\ConditionFilter;
use Mheads\Yii2DataDb\QueryFilterCompiler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use yii\db\Expression;
use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\Filter\AndX;
use Yiisoft\Data\Reader\Filter\Between;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Filter\GreaterThanOrEqual;
use Yiisoft\Data\Reader\Filter\Like;
use Yiisoft\Data\Reader\Filter\LikeMode;
use Yiisoft\Data\Reader\Filter\None;
use Yiisoft\Data\Reader\Filter\Not;
use Yiisoft\Data\Reader\Filter\OrX;

/**
 * @internal
 */
final class QueryFilterCompilerTest extends TestCase
{
	#[Test]
	public function compileCommonFilters(): void
	{
		$compiler = new QueryFilterCompiler([
			'createdAt' => 'created_at',
		]);

		self::assertSame([], $compiler->compile(new All()));
		self::assertSame('0=1', $compiler->compile(new None()));
		self::assertSame(['=', 'name', 'Tablet'], $compiler->compile(new Equals('name', 'Tablet')));
		self::assertSame(
			['>=', 'created_at', '2026-09-16 12:34:56'],
			$compiler->compile(new GreaterThanOrEqual('createdAt', new DateTimeImmutable('2026-09-16 12:34:56'))),
		);
		self::assertSame(['between', 'price', 10, 20], $compiler->compile(new Between('price', 10, 20)));
	}

	#[Test]
	public function compileGroupsAndRawConditions(): void
	{
		$condition = ['status' => 'active'];
		$compiler = new QueryFilterCompiler();

		self::assertSame($condition, $compiler->compile(new ConditionFilter($condition)));
		self::assertSame(
			[
				'and',
				['=', 'status', 'active'],
				[
					'or',
					['=', 'category', 'tablet'],
					['not', ['=', 'hidden', true]],
				],
			],
			$compiler->compile(
				new AndX(
					new Equals('status', 'active'),
					new OrX(
						new Equals('category', 'tablet'),
						new Not(new Equals('hidden', true)),
					),
				),
			),
		);
	}

	#[Test]
	public function compileLikeModes(): void
	{
		$compiler = new QueryFilterCompiler();

		self::assertSame(['like', 'name', 'tab'], $compiler->compile(new Like('name', 'tab')));
		self::assertSame(['like', 'name', 'tab\%%', false], $compiler->compile(new Like('name', 'tab%', mode: LikeMode::StartsWith)));
		self::assertSame(['like', 'name', '%\_tab', false], $compiler->compile(new Like('name', '_tab', mode: LikeMode::EndsWith)));
	}

	#[Test]
	public function rejectsCaseInsensitiveLike(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Case-insensitive LIKE is not supported by Yii2 query condition compiler.');

		(new QueryFilterCompiler())->compile(new Like('name', 'tab', caseSensitive: false));
	}

	#[Test]
	public function mapsFieldToExpression(): void
	{
		$expression = new Expression('LOWER(name)');
		$compiler = new QueryFilterCompiler(['name' => $expression]);

		self::assertSame(['=', $expression, 'tablet'], $compiler->compile(new Equals('name', 'tablet')));
	}
}
