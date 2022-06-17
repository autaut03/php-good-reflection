<?php

namespace AlexWells\GoodReflection\Type\Template;

use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Type\Type;
use Illuminate\Support\Collection;

final class TypeParameterMap
{
	/**
	 * @param array<string, Type|array<int, Type>|null> $types
	 */
	public function __construct(
		public readonly array $types
	) {
	}

	/**
	 * @param Type[]                            $arguments
	 * @param iterable<TypeParameterDefinition> $typeParameters
	 */
	public static function fromArguments(array $arguments, iterable $typeParameters): self
	{
		$map = [];
		$i = 0;

		foreach ($typeParameters as $parameter) {
			if ($parameter->variadic) {
				$map[$parameter->name] = array_slice($arguments, $i);

				break;
			}

			$map[$parameter->name] = $arguments[$i] ?? $parameter->upperBound;
			$i++;
		}

		return new self($map);
	}

	public static function empty(): self
	{
		static $map;

		if (!$map) {
			$map = new self([]);
		}

		return $map;
	}

	public function toList(iterable $typeParameters): Collection
	{
		return Collection::wrap($typeParameters)
			->map(fn (TypeParameterDefinition $parameter) => $this->types[$parameter->name] ?? null);
	}
}
