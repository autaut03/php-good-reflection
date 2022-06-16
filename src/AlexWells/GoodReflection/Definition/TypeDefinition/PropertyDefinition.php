<?php

namespace AlexWells\GoodReflection\Definition\TypeDefinition;

use AlexWells\GoodReflection\Type\Type;

final class PropertyDefinition
{
	public function __construct(
		public readonly string $name,
		public readonly Type $type,
	) {
	}
}
