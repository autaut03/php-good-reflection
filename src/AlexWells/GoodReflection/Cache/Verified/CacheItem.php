<?php

namespace AlexWells\GoodReflection\Cache\Verified;

final class CacheItem
{
	public function __construct(
		public readonly mixed $value,
		public readonly string $verificationKey,
	) {
	}
}
