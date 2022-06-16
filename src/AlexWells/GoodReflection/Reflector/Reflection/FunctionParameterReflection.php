<?php

namespace AlexWells\GoodReflection\Reflector\Reflection;

use AlexWells\GoodReflection\Definition\TypeDefinition\FunctionParameterDefinition;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasAttributes;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasNativeAttributes;
use AlexWells\GoodReflection\Type\Template\TypeParameterMap;
use AlexWells\GoodReflection\Type\Type;
use AlexWells\GoodReflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionParameter;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

/**
 * @template-covariant OwnerType of MethodReflection
 */
class FunctionParameterReflection implements HasAttributes
{
	private Lazy $type;

	private readonly ReflectionParameter $nativeReflection;

	private readonly HasNativeAttributes $nativeAttributes;

	/**
	 * @param OwnerType $owner
	 */
	public function __construct(
		private readonly FunctionParameterDefinition $definition,
		public readonly MethodReflection $owner,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->type = lazy(
			fn () => TypeProjector::templateTypes(
				$this->definition->type,
				$resolvedTypeParameterMap
			)
		);
		$this->nativeReflection = new ReflectionParameter([$this->owner->owner->qualifiedName(), $this->owner->name()], $this->definition->name);
		$this->nativeAttributes = new HasNativeAttributes(fn () => $this->nativeReflection->getAttributes());
	}

	public function name(): string
	{
		return $this->definition->name;
	}

	public function type(): Type
	{
		return $this->type->value();
	}

	public function attributes(): Collection
	{
		return $this->nativeAttributes->attributes();
	}
}
