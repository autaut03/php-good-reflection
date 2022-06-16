<?php

namespace AlexWells\GoodReflection\Definition\TypeDefinition;

use AlexWells\GoodReflection\Definition\TypeDefinition;
use Illuminate\Support\Collection;

final class TraitTypeDefinition extends TypeDefinition
{
	public function __construct(
		string $qualifiedName,
		string $fileName,
		public readonly bool $builtIn,
		public readonly Collection $typeParameters,
		public readonly Collection $uses,
		public readonly Collection $properties,
		public readonly Collection $methods,
	) {
		parent::__construct(
			$qualifiedName,
			$fileName,
		);
	}
}
