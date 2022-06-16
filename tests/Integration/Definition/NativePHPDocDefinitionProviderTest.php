<?php

namespace Tests\Integration\Definition;

use AlexWells\GoodReflection\Definition\NativePHPDoc\NativePHPDocDefinitionProvider;
use AlexWells\GoodReflection\Definition\TypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\ClassTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\FunctionParameterDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\MethodDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\PropertyDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Type\NamedType;
use AlexWells\GoodReflection\Type\PrimitiveType;
use AlexWells\GoodReflection\Type\Special\MixedType;
use AlexWells\GoodReflection\Type\Special\StaticType;
use AlexWells\GoodReflection\Type\Template\TemplateType;
use AlexWells\GoodReflection\Type\Template\TemplateTypeVariance;
use Generator;
use Illuminate\Support\Collection;
use Tests\Integration\TestCase;
use Tests\Stubs\Classes\ClassStub;
use Tests\Stubs\Classes\DoubleTemplateType;
use Tests\Stubs\Classes\ParentClassStub;
use Tests\Stubs\Classes\SomeStub;
use Tests\Stubs\Interfaces\ParentInterfaceStub;
use Tests\Stubs\Interfaces\SingleTemplateType;
use Tests\Stubs\Traits\ParentTraitStub;

/**
 * @see NativePHPDocDefinitionProvider
 */
class NativePHPDocDefinitionProviderTest extends TestCase
{
	private NativePHPDocDefinitionProvider $definitionProvider;

	protected function setUp(): void
	{
		parent::setUp();

		$this->definitionProvider = $this->container->get(NativePHPDocDefinitionProvider::class);
	}

	public static function providesDefinitionForTypeProvider(): Generator
	{
		yield [
			ClassStub::class,
			new ClassTypeDefinition(
				qualifiedName: ClassStub::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/ClassStub.php'),
				builtIn: false,
				anonymous: false,
				final: true,
				abstract: false,
				typeParameters: new Collection([
					new TypeParameterDefinition(
						name: 'T',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::INVARIANT,
					),
					new TypeParameterDefinition(
						name: 'S',
						variadic: false,
						upperBound: PrimitiveType::integer(),
						variance: TemplateTypeVariance::COVARIANT,
					),
				]),
				extends: new NamedType(ParentClassStub::class, collect([
					new TemplateType(
						name: 'T'
					),
					new NamedType(SomeStub::class),
				])),
				implements: new Collection([
					new NamedType(ParentInterfaceStub::class, collect([
						new TemplateType(
							name: 'T'
						),
						new NamedType(SomeStub::class),
					])),
				]),
				uses: new Collection([
					new NamedType(ParentTraitStub::class),
				]),
				properties: new Collection([
					new PropertyDefinition(
						name: 'factories',
						type: PrimitiveType::array(new NamedType(SomeStub::class)),
					),
					new PropertyDefinition(
						name: 'generic',
						type: new NamedType(DoubleTemplateType::class, collect([
							new NamedType(SomeStub::class),
							new TemplateType(
								name: 'T'
							),
						]))
					),
				]),
				methods: new Collection([
					new MethodDefinition(
						name: 'method',
						typeParameters: collect([
							new TypeParameterDefinition(
								name: 'G',
								variadic: false,
								upperBound: null,
								variance: TemplateTypeVariance::INVARIANT,
							),
						]),
						parameters: collect([
							new FunctionParameterDefinition(
								name: 'param',
								type: new NamedType(DoubleTemplateType::class, collect([
									new NamedType(SomeStub::class),
									new TemplateType(
										name: 'T'
									),
								]))
							),
						]),
						returnType: new NamedType(Collection::class, collect([
							new TemplateType(
								name: 'S'
							),
							new TemplateType(
								name: 'G'
							),
						]))
					),
					new MethodDefinition(
						name: 'methodTwo',
						typeParameters: collect([
							new TypeParameterDefinition(
								name: 'KValue',
								variadic: false,
								upperBound: null,
								variance: TemplateTypeVariance::INVARIANT,
							),
							new TypeParameterDefinition(
								name: 'K',
								variadic: false,
								upperBound: new NamedType(SingleTemplateType::class, collect([
									new TemplateType(
										name: 'KValue'
									),
								])),
								variance: TemplateTypeVariance::INVARIANT,
							),
						]),
						parameters: collect([
							new FunctionParameterDefinition(
								name: 'param',
								type: new TemplateType(
									name: 'K'
								),
							),
						]),
						returnType: new TemplateType(
							name: 'KValue'
						)
					),
					new MethodDefinition(
						name: 'self',
						typeParameters: collect([]),
						parameters: collect([]),
						returnType: new StaticType(MixedType::get())
					),
				]),
			),
		];
	}

	/**
	 * @dataProvider providesDefinitionForTypeProvider
	 */
	public function testProvidesDefinitionForType(string $type, TypeDefinition $expected): void
	{
		$actual = $this->definitionProvider->forType($type);

		self::assertEquals($expected, $actual);
	}
}
