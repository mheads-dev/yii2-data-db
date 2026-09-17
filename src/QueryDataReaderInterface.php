<?php

declare(strict_types=1);

namespace Mheads\Yii2DataDb;

use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\FilterInterface;

/**
 * @template TKey as array-key
 * @template TValue as array|object
 *
 * @extends DataReaderInterface<TKey, TValue>
 */
interface QueryDataReaderInterface extends DataReaderInterface
{
	/**
	 * @return static The new instance with the specified count parameter.
	 *
	 * @psalm-mutation-free
	 * @psalm-return static<TKey, TValue>
	 */
	public function withCountParam(?string $countParam): static;

	/**
	 * @return static The new instance with the specified having condition.
	 *
	 * @psalm-mutation-free
	 * @psalm-return static<TKey, TValue>
	 */
	public function withHaving(FilterInterface $having): static;

	/**
	 * @return static The new instance with the specified batch size.
	 *
	 * @psalm-mutation-free
	 * @psalm-return static<TKey, TValue>
	 */
	public function withBatchSize(?int $batchSize): static;
}
