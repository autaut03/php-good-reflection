<?php

namespace AlexWells\GoodReflection\Reflector\Reflection;

use AlexWells\GoodReflection\Definition\TypeDefinition\InterfaceTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\MethodDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasAttributes;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasNativeAttributes;
use AlexWells\GoodReflection\Type\Template\TypeParameterMap;
use AlexWells\GoodReflection\Type\Type;
use AlexWells\GoodReflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionClass;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

class InterfaceReflection extends TypeReflection implements HasAttributes
{
	/** @var Lazy<Collection<int, MethodReflection<$this>>> */
	private Lazy $methods;

	/** @var Lazy<Collection<int, Type>> */
	private Lazy $extends;

	/** @var ReflectionClass<object> */
	private readonly ReflectionClass $nativeReflection;

	private readonly HasNativeAttributes $nativeAttributes;

	public function __construct(
		private readonly InterfaceTypeDefinition $definition,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->methods = lazy(
			fn () => $this->definition
				->methods
				->map(fn (MethodDefinition $method) => new MethodReflection($method, $this, $resolvedTypeParameterMap))
		);
		$this->extends = lazy(
			fn () => $this->definition
				->extends
				->map(fn (Type $type) => TypeProjector::templateTypes(
					$type,
					$resolvedTypeParameterMap
				))
		);
		$this->nativeReflection = new ReflectionClass($this->definition->qualifiedName);
		$this->nativeAttributes = new HasNativeAttributes(fn () => $this->nativeReflection->getAttributes());
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
	}

	public function fileName(): ?string
	{
		return $this->definition->fileName;
	}

	/**
	 * @return Collection<int, object>
	 */
	public function attributes(): Collection
	{
		return $this->nativeAttributes->attributes();
	}

	/**
	 * @return Collection<int, TypeParameterDefinition>
	 */
	public function typeParameters(): Collection
	{
		return $this->definition->typeParameters;
	}

	/**
	 * @return Collection<int, Type>
	 */
	public function extends(): Collection
	{
		return $this->extends->value();
	}

	/**
	 * @return Collection<int, MethodReflection<$this>>
	 */
	public function methods(): Collection
	{
		return $this->methods->value();
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}
}
