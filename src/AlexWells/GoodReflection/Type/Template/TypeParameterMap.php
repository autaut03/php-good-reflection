<?php

namespace AlexWells\GoodReflection\Type\Template;

use AlexWells\GoodReflection\Reflector\Old\Reflection\TypeParameters\TypeParameterReflection;
use AlexWells\GoodReflection\Type\Type;
use Illuminate\Support\Collection;

final class TypeParameterMap
{
	/**
	 * @param array<string, Type> $types
	 */
	public function __construct(
		public readonly array $types
	) {
	}

	/**
	 * @param Type[]                    $types
	 * @param TypeParameterReflection[] $typeParameters
	 */
	public static function fromConsecutiveTypes(array $types, iterable $typeParameters): self
	{
		$map = [];
		$i = 0;

		foreach ($typeParameters as $parameter) {
			$map[$parameter->name()] = $types[$i] ?? $parameter->type()->upperBound;
			$i++;
		}

		return new self($map);
	}

	public function toList(iterable $typeParameters): Collection
	{
		return Collection::wrap($typeParameters)
			->map(fn (TypeParameterReflection $parameter) => $this->types[$parameter->name()] ?? null);
	}
}
