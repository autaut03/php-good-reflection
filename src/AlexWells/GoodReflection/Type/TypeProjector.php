<?php

namespace AlexWells\GoodReflection\Type;

use AlexWells\GoodReflection\Type\Combinatorial\ExpandedType;
use AlexWells\GoodReflection\Type\Combinatorial\TupleType;
use AlexWells\GoodReflection\Type\Template\TemplateType;
use AlexWells\GoodReflection\Type\Template\TypeParameterMap;

class TypeProjector
{
	public static function templateTypes(Type $type, TypeParameterMap $typeParameterMap): Type
	{
		$mapped = TypeTraversingMapper::map($type, static function (Type $type, callable $traverse) use ($typeParameterMap): Type {
			//  && !$type->isArgument()
			if ($type instanceof TemplateType) {
				$newType = $typeParameterMap->types[$type->name] ?? null;

				if ($newType === null) {
					return $traverse($type);
				}

				return $newType;
			}

			return $traverse($type);
		});

		return TypeTraversingMapper::map($mapped, static function (Type $type, callable $traverse): Type {
			if ($type instanceof NamedType) {
				$changed = false;

				$arguments = $type->arguments
					->flatMap(function (Type $type) use (&$changed) {
						if ($type instanceof ExpandedType && $type->innerType instanceof TupleType) {
							$changed = true;

							return $type->innerType->types;
						}

						return [$type];
					});

				if ($changed) {
					$type = new NamedType($type->name, $arguments);
				}
			}

			return $traverse($type);
		});
	}
}
