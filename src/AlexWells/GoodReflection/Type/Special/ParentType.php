<?php

namespace AlexWells\GoodReflection\Type\Special;

use AlexWells\GoodReflection\Type\Type;
use AlexWells\GoodReflection\Type\TypeExtensions;

class ParentType implements Type
{
	use TypeExtensions;

	public function __construct(
		public readonly Type $baseType,
	) {
	}

	public function __toString()
	{
		return "parent<{$this->baseType}>";
	}

	public function equals(Type $other): bool
	{
		return $other instanceof self &&
			$other->baseType->equals($this->baseType);
	}

	public function traverse(callable $callback): Type
	{
		$newBaseType = $callback($this->baseType);

		if ($this->baseType !== $newBaseType) {
			return new self($newBaseType);
		}

		return $this;
	}
}
