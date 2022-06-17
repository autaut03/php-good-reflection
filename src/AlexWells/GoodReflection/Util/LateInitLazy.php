<?php

namespace AlexWells\GoodReflection\Util;

use RuntimeException;
use TenantCloud\Standard\Lazy\Lazy;

/**
 * @template-covariant  T
 */
class LateInitLazy implements Lazy
{
	private bool $isInitialized = false;

	/** @var T */
	private mixed $value;

	/**
	 * @param T $value
	 */
	public function initialize(mixed $value): void
	{
		if ($this->isInitialized()) {
			throw new RuntimeException('Attempted to initialize lazy twice.');
		}

		$this->value = $value;
		$this->isInitialized = true;
	}

	public function value()
	{
		if (!$this->isInitialized()) {
			throw new RuntimeException('Attempted to get value of uninitialized lazy.');
		}

		return $this->value;
	}

	public function isInitialized(): bool
	{
		return $this->isInitialized;
	}
}
