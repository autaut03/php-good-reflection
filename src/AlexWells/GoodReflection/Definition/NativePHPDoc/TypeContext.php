<?php

namespace AlexWells\GoodReflection\Definition\NativePHPDoc;

use AlexWells\GoodReflection\Definition\NativePHPDoc\File\FileClassLikeContext;
use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Type\NamedType;
use Illuminate\Support\Collection;
use TenantCloud\Standard\Lazy\Lazy;

class TypeContext
{
	/**
	 * @param Collection<string, Lazy<TypeParameterDefinition>> $typeParameters
	 */
	public function __construct(
		public readonly FileClassLikeContext $fileClassLikeContext,
		public readonly NamedType            $definingType,
		public readonly Collection           $typeParameters
	) {
	}

	/**
	 * @param Collection<string, Lazy<TypeParameterDefinition>> $parameters
	 */
	public function withMergedTypeParameters(Collection $parameters): self
	{
		return new self(
			fileClassLikeContext: $this->fileClassLikeContext,
			definingType: $this->definingType,
			typeParameters: (clone $this->typeParameters)->merge($parameters)
		);
	}
}
