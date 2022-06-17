<?php

namespace AlexWells\GoodReflection\Definition\NativePHPDoc\Native;

use AlexWells\GoodReflection\Definition\NativePHPDoc\TypeContext;
use AlexWells\GoodReflection\Type\Combinatorial\IntersectionType;
use AlexWells\GoodReflection\Type\Combinatorial\UnionType;
use AlexWells\GoodReflection\Type\NamedType;
use AlexWells\GoodReflection\Type\PrimitiveType;
use AlexWells\GoodReflection\Type\Special\ErrorType;
use AlexWells\GoodReflection\Type\Special\MixedType;
use AlexWells\GoodReflection\Type\Special\NeverType;
use AlexWells\GoodReflection\Type\Special\NullableType;
use AlexWells\GoodReflection\Type\Special\StaticType;
use AlexWells\GoodReflection\Type\Special\VoidType;
use AlexWells\GoodReflection\Type\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

class NativeTypeMapper
{
	/**
	 * @param ReflectionType|string|iterable<ReflectionType|string> $type
	 *
	 * @return ($type is iterable ? Collection<int, Type> : Type)
	 */
	public function map(ReflectionType|string|iterable $type, TypeContext $context): Type|Collection
	{
		if (is_iterable($type)) {
			return Collection::wrap($type)->map(fn ($type) => $this->map($type, $context));
		}

		$isNull = fn (ReflectionType $isNullType) => $isNullType instanceof ReflectionNamedType && $isNullType->getName() === 'null';

		$mappedType = match (true) {
			$type instanceof ReflectionIntersectionType => new IntersectionType(
				$this->map($type->getTypes(), $context)
			),
			$type instanceof ReflectionUnionType => new UnionType(
				$this->map(
					array_filter(
						$type->getTypes(),
						fn (ReflectionType $type) => !$isNull($type)
					),
					$context
				)
			),
			$type instanceof ReflectionNamedType => $this->mapNamed($type->getName(), $context),
			is_string($type)                     => $this->mapNamed($type, $context),
			default                              => new ErrorType((string) $type),
		};

		if ($type instanceof ReflectionType && $type->allowsNull() && !($type instanceof ReflectionNamedType && $type->getName() === 'mixed')) {
			return new NullableType($mappedType);
		}

		if ($type instanceof ReflectionUnionType && Arr::first($type->getTypes(), fn (ReflectionType $type) => $isNull($type))) {
			return new NullableType($mappedType);
		}

		return $mappedType;
	}

	private function mapNamed(string $name, TypeContext $context): Type
	{
		return match (mb_strtolower($name)) {
			'mixed' => MixedType::get(),
			'never' => NeverType::get(),
			'void'  => VoidType::get(),
			'true', 'false' => PrimitiveType::boolean(),
			'self'   => $context->definingType,
			'static' => new StaticType($context->definingType),
			default  => new NamedType($name),
		};
	}
}
