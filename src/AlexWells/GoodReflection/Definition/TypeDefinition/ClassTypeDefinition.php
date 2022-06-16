<?php

namespace AlexWells\GoodReflection\Definition\TypeDefinition;

use AlexWells\GoodReflection\Definition\TypeDefinition;
use AlexWells\GoodReflection\Type\Type;
use Illuminate\Support\Collection;

final class ClassTypeDefinition extends TypeDefinition
{
	public function __construct(
		string $qualifiedName,
		string $fileName,
		public readonly bool $builtIn,
		public readonly bool $anonymous,
		public readonly bool $final,
		public readonly bool $abstract,
		public readonly Collection $typeParameters,
		public readonly ?Type $extends,
		public readonly Collection $implements,
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
