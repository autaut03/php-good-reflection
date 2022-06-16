<?php

namespace AlexWells\GoodReflection\Definition\TypeDefinition;

use AlexWells\GoodReflection\Type\Type;
use Illuminate\Support\Collection;

final class MethodDefinition
{
	/**
	 * @param Collection<int, TypeParameterDefinition>     $typeParameters
	 * @param Collection<int, FunctionParameterDefinition> $parameters
	 */
	public function __construct(
		public readonly string $name,
		public readonly Collection $typeParameters,
		public readonly Collection $parameters,
		public readonly ?Type $returnType,
	) {
	}
}
