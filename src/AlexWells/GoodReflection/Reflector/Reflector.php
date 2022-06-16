<?php

namespace AlexWells\GoodReflection\Reflector;

use AlexWells\GoodReflection\Definition\DefinitionProvider;
use AlexWells\GoodReflection\Definition\TypeDefinition\ClassTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\EnumTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\InterfaceTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\SpecialTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\TraitTypeDefinition;
use AlexWells\GoodReflection\Reflector\Reflection\TypeReflection;
use AlexWells\GoodReflection\Type\NamedType;
use AlexWells\GoodReflection\Type\Template\TypeParameterMap;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Reflector
{
	public function __construct(
		private readonly DefinitionProvider $definitionProvider,
	) {
	}

	/**
	 * @template T
	 *
	 * @param class-string<T>|NamedType|object $type
	 *
	 * @return TypeReflection<T>
	 */
	public function forType(string|object $type, Collection|TypeParameterMap $types = null): TypeReflection
	{
		$type = $this->prepareArguments($type, $types);

		$definition = $this->definitionProvider->forType($type->name) ??
			throw new UnknownTypeException($type->name);

		$resolvedTypeParameterMap ??= TypeParameterMap::fromConsecutiveTypes($type->arguments->all(), $definition->typeParameters);

		return match (true) {
			$definition instanceof ClassTypeDefinition     => new Reflection\ClassReflection($definition, $resolvedTypeParameterMap),
			$definition instanceof InterfaceTypeDefinition => new Reflection\InterfaceReflection($definition, $resolvedTypeParameterMap),
			$definition instanceof TraitTypeDefinition     => new Reflection\TraitReflection($definition, $resolvedTypeParameterMap),
			$definition instanceof EnumTypeDefinition      => new Reflection\EnumReflection($definition),
			$definition instanceof SpecialTypeDefinition   => new Reflection\SpecialTypeReflection($definition, $resolvedTypeParameterMap),
			default                                        => throw new InvalidArgumentException('Unsupported definition of type ' . $definition::class . ' given.')
		};
	}

	private function prepareArguments(string|object $className, Collection|TypeParameterMap $resolvedTypeParameterMap = null): NamedType
	{
		if (is_string($className)) {
			$className = new NamedType($className);
		}

		if (!$className instanceof NamedType) {
			$className = new NamedType(get_class($className));
		}

		return $className;
	}
}
