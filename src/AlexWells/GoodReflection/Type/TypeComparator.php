<?php

namespace AlexWells\GoodReflection\Type;

use AlexWells\GoodReflection\Type\Combinatorial\IntersectionType;
use AlexWells\GoodReflection\Type\Combinatorial\UnionType;
use AlexWells\GoodReflection\Type\Special\MixedType;
use AlexWells\GoodReflection\Type\Special\NeverType;
use AlexWells\GoodReflection\Type\Special\NullableType;
use AlexWells\GoodReflection\Type\Special\StaticType;
use AlexWells\GoodReflection\Type\Special\VoidType;
use InvalidArgumentException;
use Tests\Integration\Type\TypeComparatorTest;

/**
 * @see TypeComparatorTest
 */
class TypeComparator
{
	public function __construct(
		private readonly NamedTypeComparator $namedTypeComparator
	) {
	}

	public function accepts(Type $a, Type $b): bool
	{
		return match (true) {
			$a instanceof NeverType || $b instanceof NeverType => false,
			$a instanceof VoidType                             => true,
			$b instanceof VoidType                             => false,
			$a instanceof MixedType                            => true,
			$b instanceof MixedType                            => false,
			$a instanceof IntersectionType                     => $a->types->every(fn (Type $type)                     => $this->accepts($type, $b)),
			$b instanceof IntersectionType                     => $b->types->some(fn (Type $type)                     => $this->accepts($a, $type)),
			$a instanceof UnionType                            => $a->types->some(fn (Type $type)                            => $this->accepts($type, $b)),
			$b instanceof UnionType                            => $b->types->every(fn (Type $type)                            => $this->accepts($a, $type)),
			$a instanceof NullableType                         => $this->accepts($a->innerType, $b instanceof NullableType ? $b->innerType : $b),
			$b instanceof NullableType                         => false,
			// This operates under the assumption that static types should only exist in a scope of a single class,
			// the one that declares a function with that type. If other class extends the one that declares a static,
			// baseType of static types should be changed similarly to how it's done with template types.
			$a instanceof StaticType                           => $this->accepts($a->baseType, $b),
			$b instanceof StaticType                           => $this->accepts($a, $b->baseType),
			$a instanceof NamedType && $b instanceof NamedType => $this->namedTypeComparator->accepts($a, $b),
			default                                            => throw new InvalidArgumentException("Unsupported types given: {$a} (" . $a::class . ") and {$b} (" . $b::class . ')')
		};
	}
}
