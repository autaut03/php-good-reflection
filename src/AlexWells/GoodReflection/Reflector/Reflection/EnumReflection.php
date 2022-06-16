<?php

namespace AlexWells\GoodReflection\Reflector\Reflection;

use AlexWells\GoodReflection\Definition\TypeDefinition\EnumTypeDefinition;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasAttributes;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasNativeAttributes;
use Illuminate\Support\Collection;
use ReflectionEnum;

class EnumReflection extends TypeReflection implements HasAttributes
{
	private readonly ReflectionEnum $nativeReflection;

	private readonly HasNativeAttributes $nativeAttributes;

	public function __construct(
		private readonly EnumTypeDefinition $definition,
	) {
		$this->nativeReflection = new ReflectionEnum($this->definition->qualifiedName);
		$this->nativeAttributes = new HasNativeAttributes(fn () => $this->nativeReflection->getAttributes());
	}

	public function fileName(): string
	{
		return $this->definition->fileName;
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
	}

	public function attributes(): Collection
	{
		return $this->nativeAttributes->attributes();
	}

	public function implements(): Collection
	{
		return $this->definition->implements;
	}

	public function uses(): Collection
	{
		return $this->definition->uses;
	}

	public function methods(): Collection
	{
		return $this->definition->methods;
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}
}
