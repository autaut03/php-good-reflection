<?php

namespace AlexWells\GoodReflection\Type\Combinatorial;

use AlexWells\GoodReflection\Type\Special\NullableType;
use AlexWells\GoodReflection\Type\Type;
use AlexWells\GoodReflection\Type\TypeExtensions;
use AlexWells\GoodReflection\Type\TypeUtil;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

class UnionType implements Type
{
	use TypeExtensions;

	/**
	 * @param Collection<int, Type> $types
	 */
	public function __construct(
		public Collection $types,
	) {
		// Transform A|(B|C) into A|B|C
		$this->types = $types->reduce(function (Collection $accumulator, Type $type) {
			$types = $type instanceof self ? $type->types : [$type];

			return $accumulator->concat($types);
		}, new Collection());

		Assert::minCount($this->types, 2);
	}

	public function __toString()
	{
		return $this->types
			->map(fn (Type $type) => $type instanceof self || $type instanceof IntersectionType || $type instanceof NullableType ? "({$type})" : (string) $type)
			->join('|');
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
