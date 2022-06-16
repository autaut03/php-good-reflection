<?php

namespace AlexWells\GoodReflection\Type;

use Illuminate\Support\Collection;

class TypeUtil
{
	public static function allEqual(Collection $types, Collection $otherTypes): bool
	{
		return $types->count() === $otherTypes->count() &&
			$types->every(fn (Type $type, int $i) => $type->equals($otherTypes[$i]));
	}
}
