<?php

namespace Tests\Integration\Definition;

use AlexWells\GoodReflection\Definition\NativePHPDoc\NativePHPDocDefinitionProvider;
use AlexWells\GoodReflection\Definition\TypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\ClassTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\FunctionParameterDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\MethodDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\PropertyDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Type\Combinatorial\UnionType;
use AlexWells\GoodReflection\Type\NamedType;
use AlexWells\GoodReflection\Type\PrimitiveType;
use AlexWells\GoodReflection\Type\Special\ErrorType;
use AlexWells\GoodReflection\Type\Special\MixedType;
use AlexWells\GoodReflection\Type\Special\NeverType;
use AlexWells\GoodReflection\Type\Special\NullableType;
use AlexWells\GoodReflection\Type\Special\StaticType;
use AlexWells\GoodReflection\Type\Special\VoidType;
use AlexWells\GoodReflection\Type\Template\TemplateType;
use AlexWells\GoodReflection\Type\Template\TemplateTypeVariance;
use Generator;
use Illuminate\Support\Collection;
use Tests\Integration\TestCase;
use Tests\Stubs\Classes\AllMissingTypes;
use Tests\Stubs\Classes\AllNativeTypes;
use Tests\Stubs\Classes\AllPhpDocTypes;
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
						returnType: new StaticType(
							new NamedType(ClassStub::class)
						)
					),
				]),
			),
		];

		yield [
			AllMissingTypes::class,
			new ClassTypeDefinition(
				qualifiedName: AllMissingTypes::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/AllMissingTypes.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				typeParameters: new Collection(),
				extends: new NamedType(SomeStub::class),
				implements: new Collection([
					new NamedType(SingleTemplateType::class),
				]),
				uses: new Collection(),
				properties: new Collection([
					new PropertyDefinition(
						name: 'property',
						type: null,
					),
				]),
				methods: new Collection([
					new MethodDefinition(
						name: 'test',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'something',
								type: null
							),
						]),
						returnType: null
					),
				]),
			),
		];

		yield [
			AllNativeTypes::class,
			new ClassTypeDefinition(
				qualifiedName: AllNativeTypes::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/AllNativeTypes.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				typeParameters: new Collection(),
				extends: null,
				implements: new Collection(),
				uses: new Collection(),
				properties: new Collection(),
				methods: new Collection([
					new MethodDefinition(
						name: 'f1',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::float(),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::boolean(),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType('array'),
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: PrimitiveType::object(),
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: new NamedType('callable')
							),
							new FunctionParameterDefinition(
								name: 'p8',
								type: new NamedType('iterable'),
							),
							new FunctionParameterDefinition(
								name: 'p9',
								type: MixedType::get(),
							),
						]),
						returnType: VoidType::get(),
					),
					new MethodDefinition(
						name: 'f2',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: new NullableType(PrimitiveType::integer()),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: new NullableType(
									new UnionType(new Collection([
										PrimitiveType::string(),
										PrimitiveType::float(),
									]))
								),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: new UnionType(new Collection([
									PrimitiveType::string(),
									PrimitiveType::float(),
								])),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: new UnionType(new Collection([
									PrimitiveType::string(),
									PrimitiveType::boolean(),
								])),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType(AllNativeTypes::class),
							),
						]),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f3',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: new StaticType(
							new NamedType(AllNativeTypes::class)
						),
					),
				]),
			),
		];

		yield [
			AllPhpDocTypes::class,
			new ClassTypeDefinition(
				qualifiedName: AllPhpDocTypes::class,
				fileName: realpath(__DIR__ . '/../../Stubs/Classes/AllPhpDocTypes.php'),
				builtIn: false,
				anonymous: false,
				final: false,
				abstract: false,
				typeParameters: new Collection(),
				extends: null,
				implements: new Collection(),
				uses: new Collection(),
				properties: new Collection(),
				methods: new Collection([
					new MethodDefinition(
						name: 'f1',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: PrimitiveType::integer(),
							),
							new FunctionParameterDefinition(
								name: 'p8',
								type: new UnionType(new Collection([
									PrimitiveType::integer(),
									PrimitiveType::float(),
								])),
							),
							new FunctionParameterDefinition(
								name: 'p9',
								type: new ErrorType('numeric'),
							),
							new FunctionParameterDefinition(
								name: 'p10',
								type: PrimitiveType::float(),
							),
							new FunctionParameterDefinition(
								name: 'p11',
								type: PrimitiveType::float(),
							),
						]),
						returnType: VoidType::get(),
					),
					new MethodDefinition(
						name: 'f2',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: PrimitiveType::string(),
							),
							new FunctionParameterDefinition(
								name: 'p8',
								type: PrimitiveType::string(),
							),
						]),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f3',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: PrimitiveType::boolean(),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: PrimitiveType::boolean(),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: PrimitiveType::boolean(),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: PrimitiveType::boolean(),
							),
						]),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f4',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: new NamedType('array'),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: new UnionType(new Collection([
									PrimitiveType::integer(),
									PrimitiveType::string(),
								])),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: new NamedType('array'),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: new NamedType('array'),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType('array'),
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: new NamedType('array'),
							),
						]),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f5',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'p1',
								type: new UnionType(new Collection([
									PrimitiveType::integer(),
									PrimitiveType::float(),
									PrimitiveType::string(),
									PrimitiveType::boolean(),
								])),
							),
							new FunctionParameterDefinition(
								name: 'p2',
								type: new ErrorType('null'),
							),
							new FunctionParameterDefinition(
								name: 'p3',
								type: new NamedType('iterable'),
							),
							new FunctionParameterDefinition(
								name: 'p4',
								type: new NamedType('callable'),
							),
							new FunctionParameterDefinition(
								name: 'p5',
								type: new NamedType('resource'),
							),
							new FunctionParameterDefinition(
								name: 'p6',
								type: MixedType::get(),
							),
							new FunctionParameterDefinition(
								name: 'p7',
								type: new NamedType('object'),
							),
						]),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f6',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: NeverType::get(),
					),
					new MethodDefinition(
						name: 'f7',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: new StaticType(
							new NamedType(AllPhpDocTypes::class)
						),
					),
					new MethodDefinition(
						name: 'f8',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: new NamedType(AllPhpDocTypes::class),
					),
					new MethodDefinition(
						name: 'f9',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: new StaticType(
							new NamedType(AllPhpDocTypes::class)
						),
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
