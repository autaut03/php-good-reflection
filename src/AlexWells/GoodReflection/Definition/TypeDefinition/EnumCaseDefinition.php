<?php

namespace AlexWells\GoodReflection\Definition\TypeDefinition;

use AlexWells\GoodReflection\Type\Type;
use Illuminate\Support\Collection;

final class EnumCaseDefinition
{
	public function __construct(
		public readonly string $name,
		public readonly string|int|null $backingValue,
	) {
	}
}
