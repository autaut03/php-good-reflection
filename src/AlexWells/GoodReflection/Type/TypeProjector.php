<?php

namespace AlexWells\GoodReflection\Type;

use AlexWells\GoodReflection\Type\Template\TypeParameterMap;

class TypeProjector
{
	public static function templateTypes(Type $type, TypeParameterMap $typeParameterMap): Type
	{
		return TypeTraversingMapper::map($type, static function (Type $type, callable $traverse) use ($typeParameterMap): Type {
			if ($type instanceof TemplateType && !$type->isArgument()) {
				$newType = $typeParameterMap[$type->getName()] ?? null;

				if ($newType === null) {
					return $traverse($type);
				}

				return $newType;
			}

			return $traverse($type);
		});
	}
}
