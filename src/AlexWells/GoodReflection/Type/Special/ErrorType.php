<?php

namespace AlexWells\GoodReflection\Type\Special;

use AlexWells\GoodReflection\Type\Type;

class ErrorType implements Type
{
	public function __construct(
		public readonly string $type,
	) {
	}

	public function __toString(): string
	{
		return "error<{$this->type}>";
	}

	public function traverse(callable $callback): Type
	{
		return $this;
	}

	public function equals(Type $other): bool
	{
		return $other instanceof self &&
			$other->type === $this->type;
	}
}