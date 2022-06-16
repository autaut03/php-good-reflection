<?php

namespace AlexWells\GoodReflection\Reflector\Reflection\Attributes;

use Illuminate\Support\Collection;
use ReflectionAttribute;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

class HasNativeAttributes implements HasAttributes
{
	/** @var Lazy<Collection<int, object>> */
	private Lazy $attributes;

	/**
	 * @param callable(): iterable<ReflectionAttribute> $nativeAttributes
	 */
	public function __construct(callable $nativeAttributes)
	{
		$this->attributes = lazy(
			fn ()                                            => Collection::make($nativeAttributes())
				->map(fn (ReflectionAttribute $nativeAttribute) => $nativeAttribute->newInstance())
		);
	}

	public function attributes(): Collection
	{
		return $this->attributes->value();
	}
}
