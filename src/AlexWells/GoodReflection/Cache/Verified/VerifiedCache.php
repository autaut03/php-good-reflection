<?php

namespace AlexWells\GoodReflection\Cache\Verified;

use AlexWells\GoodReflection\Cache\Verified\Storage\CacheStorage;

final class VerifiedCache
{
	public function __construct(
		private readonly CacheStorage $cacheStorage,
	) {
	}

	/**
	 * @template ItemType
	 *
	 * @param callable(ItemType): string $verificationKey
	 *
	 * @return ItemType|null
	 */
	public function remember(string $key, callable $verificationKey, callable $delegate): mixed
	{
		if ($cacheItem = $this->cacheStorage->get($key)) {
			/** @var CacheItem $cacheItem */
			if ($cacheItem->verificationKey !== $verificationKey($cacheItem->value)) {
				$this->cacheStorage->remove($key);

				return $this->remember($key, $verificationKey, $delegate);
			}

			return $cacheItem->value;
		}

		$item = $delegate();

		if (!$item) {
			return $item;
		}

		$itemVerificationKey = $verificationKey($item);

		if (!$itemVerificationKey) {
			return $item;
		}

		$this->cacheStorage->set($key, new CacheItem(
			value: $item,
			verificationKey: $itemVerificationKey
		));

		return $item;
	}
}
