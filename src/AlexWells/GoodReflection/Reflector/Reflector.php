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
use AlexWells\GoodReflection\Type\Type;
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
	 * @param NamedType<T> $type
	 *
	 * @return TypeReflection<T>
	 */
	public function forNamedType(NamedType $type): TypeReflection
	{
		$definition = $this->definitionProvider->forType($type->name) ??
			throw new UnknownTypeException($type->name);

		$resolvedTypeParameterMap = match (true) {
			$definition instanceof ClassTypeDefinition ||
			$definition instanceof InterfaceTypeDefinition ||
			$definition instanceof TraitTypeDefinition ||
			$definition instanceof SpecialTypeDefinition => TypeParameterMap::fromArguments($type->arguments->all(), $definition->typeParameters),
			default                                      => TypeParameterMap::empty()
		};

		return match (true) {
			$definition instanceof ClassTypeDefinition     => new Reflection\ClassReflection($definition, $resolvedTypeParameterMap, $this),
			$definition instanceof InterfaceTypeDefinition => new Reflection\InterfaceReflection($definition, $resolvedTypeParameterMap, $this),
			$definition instanceof TraitTypeDefinition     => new Reflection\TraitReflection($definition, $resolvedTypeParameterMap, $this),
			$definition instanceof EnumTypeDefinition      => new Reflection\EnumReflection($definition, $this),
			$definition instanceof SpecialTypeDefinition   => new Reflection\SpecialTypeReflection($definition, $resolvedTypeParameterMap),
			default                                        => throw new InvalidArgumentException('Unsupported definition of type ' . $definition::class . ' given.')
		};
	}

	/**
	 * @template T
	 *
	 * @param class-string<T>       $name
	 * @param Collection<int, Type> $arguments
	 *
	 * @return TypeReflection<T>
	 */
	public function forType(string $name, Collection $arguments = new Collection()): TypeReflection
	{
		return $this->forNamedType(new NamedType($name, $arguments));
	}
}
