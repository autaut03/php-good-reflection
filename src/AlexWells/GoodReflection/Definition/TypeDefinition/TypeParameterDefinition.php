<?php

namespace AlexWells\GoodReflection\Definition\TypeDefinition;

use AlexWells\GoodReflection\Type\Template\TemplateTypeVariance;
use AlexWells\GoodReflection\Type\Type;

final class TypeParameterDefinition
{
	public function __construct(
		public readonly string $name,
		public readonly bool $variadic,
		public readonly ?Type $upperBound,
		public readonly TemplateTypeVariance $variance,
	) {
	}
}
