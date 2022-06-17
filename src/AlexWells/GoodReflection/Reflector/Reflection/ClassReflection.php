<?php

namespace AlexWells\GoodReflection\Reflector\Reflection;

use AlexWells\GoodReflection\Definition\TypeDefinition\ClassTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\MethodDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\PropertyDefinition;
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

/**
 * @template-covariant T
 *
 * @extends TypeReflection<T>
 */
class ClassReflection extends TypeReflection implements HasAttributes
{
	/** @var Lazy<Collection<int, MethodReflection<$this>>> */
	private Lazy $methods;

	/** @var Lazy<Collection<int, PropertyReflection<$this>>> */
	private Lazy $properties;

	/** @var Lazy<Type|null> */
	private Lazy $extends;

	/** @var Lazy<Collection<int, Type>> */
	private Lazy $implements;

	/** @var Lazy<Collection<int, Type>> */
	private Lazy $uses;

	/** @var ReflectionClass<object> */
	private readonly ReflectionClass $nativeReflection;

	private readonly HasNativeAttributes $nativeAttributes;

	public function __construct(
		private readonly ClassTypeDefinition $definition,
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
		$this->extends = lazy(
			fn () => $this->definition->extends ?
				TypeProjector::templateTypes(
					$this->definition->extends,
					$resolvedTypeParameterMap
				) :
				null
		);
		$this->implements = lazy(
			fn () => $this->definition
				->implements
				->map(fn (Type $type) => TypeProjector::templateTypes(
					$type,
					$resolvedTypeParameterMap
				))
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

	public function fileName(): ?string
	{
		return $this->definition->fileName;
	}

	public function qualifiedName(): string
	{
		return $this->definition->qualifiedName;
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

	public function extends(): ?Type
	{
		return $this->extends->value();
	}

	/**
	 * @return Collection<int, Type>
	 */
	public function implements(): Collection
	{
		return $this->implements->value();
	}

	/**
	 * @return Collection<int, Type>
	 */
	public function uses(): Collection
	{
		return $this->uses->value();
	}

	/**
	 * @return Collection<int, PropertyReflection<$this>>
	 */
	public function properties(): Collection
	{
		return $this->properties->value();
	}

	/**
	 * @return Collection<int, MethodReflection<$this>>
	 */
	public function methods(): Collection
	{
		return $this->methods->value();
	}

	public function isAnonymous(): bool
	{
		return $this->definition->anonymous;
	}

	public function isAbstract(): bool
	{
		return $this->definition->abstract;
	}

	public function isFinal(): bool
	{
		return $this->definition->final;
	}

	public function isBuiltIn(): bool
	{
		return $this->definition->builtIn;
	}
}
