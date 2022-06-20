<?php

namespace AlexWells\GoodReflection\Type\Template;

use AlexWells\GoodReflection\Type\Type;

class TemplateType implements Type
{
	public function __construct(
		public readonly string $name,
	) {
	}

	public function __toString(): string
	{
		return $this->name;
	}

	public function traverse(callable $callback): Type
	{
		return $this;
	}

	public function equals(Type $other): bool
	{
		return $other instanceof self &&
			$other->name === $this->name;
	}
}
