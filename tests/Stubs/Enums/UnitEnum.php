<?php

namespace Tests\Stubs\Enums;

use Tests\Stubs\Interfaces\NonGenericInterface;
use Tests\Stubs\Traits\TraitWithoutProperties;

enum UnitEnum implements NonGenericInterface
{
	use TraitWithoutProperties;
	use TraitWithoutProperties {
		otherFunction as otherOtherFunction;
	}

	public function function(string|int $i, ?string $overwritten = null): string|int|null
	{
	}

	case FIRST;
	case SECOND;
}
