<?php

namespace Tests\Unit\AlexWells\GoodReflection\Type\Definition\TypeDefinition;

use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Type\PrimitiveType;
use AlexWells\GoodReflection\Type\Template\TemplateTypeVariance;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * @see TypeParameterDefinition
 */
class TypeParameterDefinitionTest extends TestCase
{
	/**
	 * @dataProvider toStringProvider
	 */
	public function testToString(string $expected, TypeParameterDefinition $parameter): void
	{
		self::assertSame(
			$expected,
			(string) $parameter,
		);
	}

	public function toStringProvider(): Generator
	{
		yield [
			'T',
			new TypeParameterDefinition(
				name: 'T',
				variadic: false,
				upperBound: null,
				variance: TemplateTypeVariance::INVARIANT,
			),
		];

		yield [
			'in T',
			new TypeParameterDefinition(
				name: 'T',
				variadic: false,
				upperBound: null,
				variance: TemplateTypeVariance::CONTRAVARIANT,
			),
		];

		yield [
			'out T',
			new TypeParameterDefinition(
				name: 'T',
				variadic: false,
				upperBound: null,
				variance: TemplateTypeVariance::COVARIANT,
			),
		];

		yield [
			'...T',
			new TypeParameterDefinition(
				name: 'T',
				variadic: true,
				upperBound: null,
				variance: TemplateTypeVariance::INVARIANT,
			),
		];

		yield [
			'in ...T',
			new TypeParameterDefinition(
				name: 'T',
				variadic: true,
				upperBound: null,
				variance: TemplateTypeVariance::CONTRAVARIANT,
			),
		];

		yield [
			'T of int',
			new TypeParameterDefinition(
				name: 'T',
				variadic: false,
				upperBound: PrimitiveType::integer(),
				variance: TemplateTypeVariance::INVARIANT,
			),
		];

		yield [
			'out ...T of int',
			new TypeParameterDefinition(
				name: 'T',
				variadic: true,
				upperBound: PrimitiveType::integer(),
				variance: TemplateTypeVariance::COVARIANT,
			),
		];
	}
}
