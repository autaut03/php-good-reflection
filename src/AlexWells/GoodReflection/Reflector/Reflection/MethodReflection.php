<?php

namespace AlexWells\GoodReflection\Reflector\Reflection;

use AlexWells\GoodReflection\Definition\TypeDefinition\FunctionParameterDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\MethodDefinition;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasAttributes;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasNativeAttributes;
use AlexWells\GoodReflection\Type\Template\TypeParameterMap;
use AlexWells\GoodReflection\Type\Type;
use AlexWells\GoodReflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionMethod;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

/**
 * @template-covariant OwnerType of ClassReflection|InterfaceReflection|TraitReflection|EnumReflection
 */
class MethodReflection implements HasAttributes
{
	private Lazy $parameters;

	private Lazy $returnType;

	private readonly ReflectionMethod $nativeReflection;

	private readonly HasNativeAttributes $nativeAttributes;

	/**
	 * @param OwnerType $owner
	 */
	public function __construct(
		private readonly MethodDefinition $definition,
		public readonly ClassReflection|InterfaceReflection|TraitReflection|EnumReflection $owner,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->parameters = lazy(
			fn () => $this->definition
				->parameters
				->map(fn (FunctionParameterDefinition $parameter) => new FunctionParameterReflection($parameter, $this, $resolvedTypeParameterMap))
		);
		$this->returnType = lazy(
			fn () => TypeProjector::templateTypes(
				$this->definition->returnType,
				$resolvedTypeParameterMap
			)
		);
		$this->nativeReflection = new ReflectionMethod($this->owner->qualifiedName(), $this->definition->name);
		$this->nativeAttributes = new HasNativeAttributes(fn () => $this->nativeReflection->getAttributes());
	}

	public function name(): string
	{
		return $this->definition->name;
	}

	public function attributes(): Collection
	{
		return $this->nativeAttributes->attributes();
	}

	public function typeParameters(): Collection
	{
		return $this->definition->typeParameters;
	}

	public function parameters(): Collection
	{
		return $this->parameters->value();
	}

	public function returnType(): Type
	{
		return $this->returnType->value();
	}

	public function invoke(object $receiver, mixed ...$args): mixed
	{
		return $this->nativeReflection->invoke($receiver, ...$args);
	}
}
