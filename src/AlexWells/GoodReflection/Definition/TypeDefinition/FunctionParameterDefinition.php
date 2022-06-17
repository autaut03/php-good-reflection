<?php

namespace AlexWells\GoodReflection\Definition\TypeDefinition;

use AlexWells\GoodReflection\Type\Type;

final class FunctionParameterDefinition
{
	public function __construct(
		public readonly string $name,
		public readonly ?Type $type,
	) {
	}
}
