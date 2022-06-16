<?php

namespace AlexWells\GoodReflection\Definition\BuiltIns;

use AlexWells\GoodReflection\Definition\DefinitionProvider;
use AlexWells\GoodReflection\Definition\TypeDefinition\SpecialTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Type\NamedType;
use AlexWells\GoodReflection\Type\Template\TemplateType;
use AlexWells\GoodReflection\Type\Template\TemplateTypeVariance;
use Illuminate\Support\Collection;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

class BuiltInSpecialsDefinitionProvider implements DefinitionProvider
{
	/** @var array<string, Lazy<SpecialTypeDefinition>> */
	private readonly array $typeDefinitions;

	public function __construct()
	{
		$this->typeDefinitions = [
			'object' => lazy(fn () => new SpecialTypeDefinition(
				'object',
			)),
			'resource' => lazy(fn () => new SpecialTypeDefinition(
				'resource',
			)),
			'string' => lazy(fn () => new SpecialTypeDefinition(
				'string',
			)),
			'int' => lazy(fn () => new SpecialTypeDefinition(
				'int',
			)),
			'float' => lazy(fn () => new SpecialTypeDefinition(
				'float',
			)),
			'bool' => lazy(fn () => new SpecialTypeDefinition(
				'bool',
			)),
			'iterable' => lazy(fn () => new SpecialTypeDefinition(
				'iterable',
				new Collection([
					new TypeParameterDefinition(
						name: 'TKey',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT
					),
					new TypeParameterDefinition(
						name: 'TValue',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT
					),
				])
			)),
			'array' => lazy(fn () => new SpecialTypeDefinition(
				'array',
				new Collection([
					new TypeParameterDefinition(
						name: 'TKey',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT
					),
					new TypeParameterDefinition(
						name: 'TValue',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT
					),
				]),
				new Collection([
					new NamedType('iterable', new Collection([
						new TemplateType(
							name: 'TKey',
						),
						new TemplateType(
							name: 'TValue',
						),
					])),
				])
			)),
			'callable' => lazy(fn () => new SpecialTypeDefinition(
				'callable',
				new Collection([
					new TypeParameterDefinition(
						name: 'TReturn',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT
					),
					new TypeParameterDefinition(
						name: 'TParameter',
						variadic: true,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT
					),
				])
			)),
		];
	}

	public function forType(string $type): ?SpecialTypeDefinition
	{
		return ($this->typeDefinitions[$type] ?? null)?->value();
	}
}
