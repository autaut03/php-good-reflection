<?php

namespace AlexWells\GoodReflection\Definition\BuiltIns;

use AlexWells\GoodReflection\Definition\DefinitionProvider;
use AlexWells\GoodReflection\Definition\TypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\FunctionParameterDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\InterfaceTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\MethodDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Type\NamedType;
use AlexWells\GoodReflection\Type\PrimitiveType;
use AlexWells\GoodReflection\Type\Special\NullableType;
use AlexWells\GoodReflection\Type\Special\VoidType;
use AlexWells\GoodReflection\Type\Template\TemplateType;
use AlexWells\GoodReflection\Type\Template\TemplateTypeVariance;
use ArrayAccess;
use Countable;
use Illuminate\Support\Collection;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;
use Traversable;

class BuiltInCoreDefinitionProvider implements DefinitionProvider
{
	/** @var array<string, Lazy<TypeDefinition>> */
	private readonly array $typeDefinitions;

	public function __construct()
	{
		$this->typeDefinitions = [
			Countable::class => lazy(fn () => new InterfaceTypeDefinition(
				qualifiedName: Countable::class,
				fileName: null,
				builtIn: true,
				typeParameters: new Collection(),
				extends: new Collection(),
				methods: new Collection([
					new MethodDefinition(
						name: 'count',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: PrimitiveType::integer(),
					),
				])
			)),
			ArrayAccess::class => lazy(fn () => new InterfaceTypeDefinition(
				qualifiedName: ArrayAccess::class,
				fileName: null,
				builtIn: true,
				typeParameters: new Collection([
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
				extends: new Collection(),
				methods: new Collection([
					new MethodDefinition(
						name: 'offsetExists',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'offset',
								type: new TemplateType(
									name: 'TKey',
								)
							),
						]),
						returnType: PrimitiveType::boolean(),
					),
					new MethodDefinition(
						name: 'offsetGet',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'offset',
								type: new TemplateType(
									name: 'TKey',
								)
							),
						]),
						returnType: new NullableType(
							new TemplateType(
								name: 'TValue',
							)
						),
					),
					new MethodDefinition(
						name: 'offsetSet',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'offset',
								type: new NullableType(
									new TemplateType(
										name: 'TKey',
									)
								)
							),
							new FunctionParameterDefinition(
								name: 'value',
								type: new TemplateType(
									name: 'TValue',
								)
							),
						]),
						returnType: VoidType::get(),
					),
					new MethodDefinition(
						name: 'offsetUnset',
						typeParameters: new Collection(),
						parameters: new Collection([
							new FunctionParameterDefinition(
								name: 'offset',
								type: new TemplateType(
									name: 'TKey',
								)
							),
						]),
						returnType: VoidType::get(),
					),
				])
			)),
			Traversable::class => lazy(fn () => new InterfaceTypeDefinition(
				qualifiedName: Traversable::class,
				fileName: null,
				builtIn: true,
				typeParameters: new Collection([
					new TypeParameterDefinition(
						name: 'TKey',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT,
					),
					new TypeParameterDefinition(
						name: 'TValue',
						variadic: false,
						upperBound: null,
						variance: TemplateTypeVariance::COVARIANT,
					),
				]),
				extends: new Collection([
					new NamedType('iterable', new Collection([
						new TemplateType(
							name: 'TKey',
						),
						new TemplateType(
							name: 'TValue',
						),
					])),
				]),
				methods: new Collection([
					new MethodDefinition(
						name: 'count',
						typeParameters: new Collection(),
						parameters: new Collection(),
						returnType: PrimitiveType::integer(),
					),
				])
			)),
		];
	}

	public function forType(string $type): ?TypeDefinition
	{
		return ($this->typeDefinitions[$type] ?? null)?->value();
	}
}
