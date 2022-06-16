<?php

namespace AlexWells\GoodReflection\Definition;

abstract class TypeDefinition
{
	public function __construct(
		public readonly string $qualifiedName,
		public readonly ?string $fileName,
	) {
	}
}
