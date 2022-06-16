<?php

namespace AlexWells\GoodReflection\Reflector\Reflection;

use AlexWells\GoodReflection\Definition\TypeDefinition\MethodDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\PropertyDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\TraitTypeDefinition;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasAttributes;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasNativeAttributes;
use AlexWells\GoodReflection\Type\Template\TypeParameterMap;
use AlexWells\GoodReflection\Type\Type;
use AlexWells\GoodReflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionClass;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

class TraitReflection extends TypeReflection implements HasAttributes
{
	private Lazy $methods;

	private Lazy $properties;

	private Lazy $uses;

	private readonly ReflectionClass $nativeReflection;

	private readonly HasNativeAttributes $nativeAttributes;

	public function __construct(
		private readonly TraitTypeDefinition $definition,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->methods = lazy(
			fn () => $this->definition
				->methods
				->map(fn (MethodDefinition $method) => new MethodReflection($method, $this, $resolvedTypeParameterMap))
		);
		$this->properties = lazy(
			fn () => $this->definition
				->properties
				->map(fn (PropertyDefinition $property) => new PropertyReflection($property, $this, $resolvedTypeParameterMap))
		);
		$this->uses = lazy(
			fn () => $this->definition
				->uses
				->map(fn (Type $type) => TypeProjector::templateTypes(
					$type,
					$resolvedTypeParameterMap
				))
		);
		$this->nativeReflection = new ReflectionClass($this->definition->qualifiedName);
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

	public function typeParameters(): Collection
	{
		return $this->definition->typeParameters;
	}

	public function uses(): Collection
	{
		return $this->uses->value();
	}

	public function properties(): Collection
	{
		return $this->properties->value();
	}

	public function methods(): Collection
	{
		return $this->methods->value();
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}
}
