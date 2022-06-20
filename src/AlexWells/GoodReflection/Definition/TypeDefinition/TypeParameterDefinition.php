<?php

namespace AlexWells\GoodReflection\Definition\TypeDefinition;

use AlexWells\GoodReflection\Type\Template\TemplateTypeVariance;
use AlexWells\GoodReflection\Type\Type;
use Stringable;
use Tests\Unit\AlexWells\GoodReflection\Type\Definition\TypeDefinition\TypeParameterDefinitionTest;

/**
 * @see TypeParameterDefinitionTest
 */
final class TypeParameterDefinition implements Stringable
{
	public function __construct(
		public readonly string $name,
		public readonly bool $variadic,
		public readonly ?Type $upperBound,
		public readonly TemplateTypeVariance $variance,
	) {
	}

	public function __toString(): string
	{
		$result = [
			match ($this->variance) {
				TemplateTypeVariance::INVARIANT     => '',
				TemplateTypeVariance::CONTRAVARIANT => 'in',
				TemplateTypeVariance::COVARIANT     => 'out',
			},
			($this->variadic ? '...' : '') . $this->name,
			($this->upperBound ? "of {$this->upperBound}" : ''),
		];

		return implode(' ', array_filter($result));
	}
}
