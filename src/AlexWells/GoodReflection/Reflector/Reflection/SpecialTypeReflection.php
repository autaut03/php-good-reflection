<?php

namespace AlexWells\GoodReflection\Reflector\Reflection;

use AlexWells\GoodReflection\Definition\TypeDefinition\SpecialTypeDefinition;
use AlexWells\GoodReflection\Type\Template\TypeParameterMap;
use Illuminate\Support\Collection;

class SpecialTypeReflection extends TypeReflection
{
	public function __construct(
		private readonly SpecialTypeDefinition $definition,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
	}

	public function fileName(): string
	{
		return $this->definition->fileName;
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
	}

	public function typeParameters(): Collection
	{
		return $this->definition->typeParameters;
	}

	public function superTypes(): Collection
	{
		return $this->definition->superTypes;
	}
}
