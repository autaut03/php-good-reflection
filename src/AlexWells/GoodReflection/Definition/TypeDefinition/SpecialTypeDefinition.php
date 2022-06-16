<?php

namespace AlexWells\GoodReflection\Definition\TypeDefinition;

use AlexWells\GoodReflection\Definition\TypeDefinition;
use Illuminate\Support\Collection;

final class SpecialTypeDefinition extends TypeDefinition
{
	public function __construct(
		string $qualifiedName,
		public readonly Collection $typeParameters = new Collection(),
		public readonly Collection $superTypes = new Collection(),
	) {
		parent::__construct(
			$qualifiedName,
			null,
		);
	}
}
