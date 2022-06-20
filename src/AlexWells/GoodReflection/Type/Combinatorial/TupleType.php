<?php

namespace AlexWells\GoodReflection\Type\Combinatorial;

use AlexWells\GoodReflection\Type\Type;
use AlexWells\GoodReflection\Type\TypeExtensions;
use AlexWells\GoodReflection\Type\TypeUtil;
use Illuminate\Support\Collection;

class TupleType implements Type
{
	use TypeExtensions;

	/**
	 * @param Collection<int, Type> $types
	 */
	public function __construct(
		public Collection $types,
	) {
	}

	public function __toString()
	{
		$types = $this->types
			->map(fn (Type $type) => (string) $type)
			->join(', ');

		return "array{{$types}}";
	}

	public function equals(Type $other): bool
	{
		return $other instanceof self &&
			TypeUtil::allEqual($other->types, $this->types);
	}

	public function traverse(callable $callback): Type
	{
		$changed = false;

		$types = $this->types
			->map(function (Type $type) use ($callback, &$changed) {
				$newType = $callback($type);

				if ($type !== $newType) {
					$changed = true;
				}

				return $newType;
			});

		if ($changed) {
			return new self($types);
		}

		return $this;
	}
}
