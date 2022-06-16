<?php

namespace AlexWells\GoodReflection\Reflector\Reflection\Attributes;

use Illuminate\Support\Collection;

interface HasAttributes
{
	/**
	 * @return Collection<int, object>
	 */
	public function attributes(): Collection;
}
