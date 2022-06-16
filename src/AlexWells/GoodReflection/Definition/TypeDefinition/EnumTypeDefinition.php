<?php

namespace AlexWells\GoodReflection\Definition\TypeDefinition;

use AlexWells\GoodReflection\Definition\TypeDefinition;
use Illuminate\Support\Collection;

final class EnumTypeDefinition extends TypeDefinition
{
	public function __construct(
		string $qualifiedName,
		string $fileName,
		public readonly bool $builtIn,
		public readonly Collection $implements,
		public readonly Collection $uses,
		public readonly Collection $cases,
		public readonly Collection $methods,
	) {
		parent::__construct(
			$qualifiedName,
			$fileName,
		);
	}
}
