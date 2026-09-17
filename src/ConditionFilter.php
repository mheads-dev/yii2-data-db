<?php

declare(strict_types=1);

namespace Mheads\Yii2DataDb;

use yii\db\ExpressionInterface;
use Yiisoft\Data\Reader\FilterInterface;

final readonly class ConditionFilter implements FilterInterface
{
	public function __construct(
		public array|string|ExpressionInterface $condition,
	) {}
}
