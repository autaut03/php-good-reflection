<?php

namespace AlexWells\GoodReflection\Type\Special;

use AlexWells\GoodReflection\Type\Combinatorial\IntersectionType;
use AlexWells\GoodReflection\Type\Combinatorial\UnionType;
use AlexWells\GoodReflection\Type\Type;
use AlexWells\GoodReflection\Type\TypeExtensions;
use Tests\Unit\TenantCloud\BetterReflection\Type\Special\NullableTypeTest;

/**
 * @template-covariant T of Type
 *
 * PHPStan handles this with UnionType(delegate, NullType). We've decided not to do this:
 *  - null can't be a standalone type. Not much sense to have it as a standalone then.
 *  - to check if type is nullable just do instanceof. Much better than checking if union contains null.
 *  - represents null better - the way it's intended in PHP with "?" symbol prefixing the type.
 *
 * @see NullableTypeTest
 */
class NullableType implements Type
{
	use TypeExtensions;

	public function __construct(
		public readonly Type $innerType
	) {
	}

	public function __toString()
	{
		if ($this->innerType instanceof UnionType) {
			return $this->innerType . '|null';
		}

		if ($this->innerType instanceof IntersectionType) {
			return "?({$this->innerType})";
		}

		return "?{$this->innerType}";
	}

	public function equals(Type $other): bool
	{
		return $other instanceof self &&
			$other->innerType->equals($this->innerType);
	}

	public function traverse(callable $callback): Type
	{
		$newInnerType = $callback($this->innerType);

		if ($this->innerType !== $newInnerType) {
			return new self($newInnerType);
		}

		return $this;
	}
}
