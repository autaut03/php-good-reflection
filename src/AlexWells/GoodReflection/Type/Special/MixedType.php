<?php

namespace AlexWells\GoodReflection\Type\Special;

use AlexWells\GoodReflection\Type\Type;
use AlexWells\GoodReflection\Type\TypeExtensions;
use AlexWells\GoodReflection\Util\IsSingleton;

class MixedType implements Type
{
	use IsSingleton;
	use TypeExtensions;

	public function __toString()
	{
		return 'mixed';
	}

	public function equals(Type $other): bool
	{
		return $other instanceof self;
	}

	public function traverse(callable $callback): Type
	{
		return $this;
	}
}
