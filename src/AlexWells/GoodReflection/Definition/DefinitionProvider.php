<?php

namespace AlexWells\GoodReflection\Definition;

interface DefinitionProvider
{
	public function forType(string $type): ?TypeDefinition;
}
