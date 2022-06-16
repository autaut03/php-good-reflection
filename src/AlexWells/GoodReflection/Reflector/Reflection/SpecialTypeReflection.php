<?php

namespace AlexWells\GoodReflection\Reflector\Reflection;

use AlexWells\GoodReflection\Definition\TypeDefinition\SpecialTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Type\Template\TypeParameterMap;
use AlexWells\GoodReflection\Type\Type;
use Illuminate\Support\Collection;

class SpecialTypeReflection extends TypeReflection
{
	public function __construct(
		private readonly SpecialTypeDefinition $definition,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
	}

	public function fileName(): ?string
	{
		return $this->definition->fileName;
	}

	/**
	 * @return Collection<int, TypeParameterDefinition>
	 */
	public function typeParameters(): Collection
	{
		return $this->definition->typeParameters;
	}

	/**
	 * @return Collection<int, Type>
	 */
	public function superTypes(): Collection
	{
		return $this->definition->superTypes;
	}
}
