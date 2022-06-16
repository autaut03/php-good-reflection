<?php

namespace AlexWells\GoodReflection\Reflector\Reflection;

use AlexWells\GoodReflection\Definition\TypeDefinition\PropertyDefinition;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasAttributes;
use AlexWells\GoodReflection\Reflector\Reflection\Attributes\HasNativeAttributes;
use AlexWells\GoodReflection\Type\Template\TypeParameterMap;
use AlexWells\GoodReflection\Type\Type;
use AlexWells\GoodReflection\Type\TypeProjector;
use Illuminate\Support\Collection;
use ReflectionProperty;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

/**
 * @template-covariant OwnerType of ClassReflection|InterfaceReflection|TraitReflection|EnumReflection
 */
class PropertyReflection implements HasAttributes
{
	/** @var Lazy<Type|null> */
	private readonly Lazy $type;

	private readonly ReflectionProperty $nativeReflection;

	private readonly HasNativeAttributes $nativeAttributes;

	/**
	 * @param OwnerType $owner
	 */
	public function __construct(
		private readonly PropertyDefinition $definition,
		public readonly ClassReflection|InterfaceReflection|TraitReflection|EnumReflection $owner,
		public readonly TypeParameterMap $resolvedTypeParameterMap,
	) {
		$this->type = lazy(
			fn () => $this->definition->type ?
				TypeProjector::templateTypes(
					$this->definition->type,
					$resolvedTypeParameterMap
				) :
				null
		);
		$this->nativeReflection = new ReflectionProperty($this->owner->qualifiedName(), $this->definition->name);
		$this->nativeAttributes = new HasNativeAttributes(fn () => $this->nativeReflection->getAttributes());
	}

	public function name(): string
	{
		return $this->definition->name;
	}

	public function type(): ?Type
	{
		return $this->type->value();
	}

	/**
	 * @return Collection<int, object>
	 */
	public function attributes(): Collection
	{
		return $this->nativeAttributes->attributes();
	}

	public function get(object $receiver)
	{
		return $this->nativeReflection->getValue($receiver);
	}

	public function set(object $receiver, mixed $value): void
	{
		$this->nativeReflection->setValue($receiver, $value);
	}
}
