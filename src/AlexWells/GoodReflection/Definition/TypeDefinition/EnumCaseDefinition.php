<?php

namespace AlexWells\GoodReflection\Definition\TypeDefinition;

final class EnumCaseDefinition
{
	public function __construct(
		public readonly string $name,
		public readonly string|int|null $backingValue,
	) {
	}
}
