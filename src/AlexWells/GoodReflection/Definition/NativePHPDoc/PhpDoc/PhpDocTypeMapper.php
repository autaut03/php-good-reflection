<?php

namespace AlexWells\GoodReflection\Definition\NativePHPDoc\PhpDoc;

use AlexWells\GoodReflection\Definition\NativePHPDoc\TypeContext;
use AlexWells\GoodReflection\Type\Combinatorial\IntersectionType;
use AlexWells\GoodReflection\Type\Combinatorial\TupleType;
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
use AlexWells\GoodReflection\Type\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

class PhpDocTypeMapper
{
	public function __construct(
		private readonly TypeAliasResolver $typeAliasResolver,
	) {
	}

	/**
	 * @param TypeNode|iterable<TypeNode> $node
	 *
	 * @return ($node is iterable ? Collection<int, Type> : Type)
	 */
	public function map(TypeNode|iterable $node, TypeContext $context): Type|Collection
	{
		if (!$node instanceof TypeNode) {
			return Collection::wrap($node)->map(fn ($node) => $this->map($node, $context));
		}

		return match (true) {
			$node instanceof ArrayTypeNode => PrimitiveType::array(
				$this->map($node->type, $context)
			),
			$node instanceof ArrayShapeNode                           => new TupleType(
				collect($node->items)->map(fn (ArrayShapeItemNode $node) => $this->map($node->valueType, $context))
			),
			$node instanceof CallableTypeNode => $this->mapNamed(
				$node->identifier->name,
				new Collection([
					$this->map($node->returnType, $context),
					...array_map(
						fn (CallableTypeParameterNode $parameterNode) => $this->map($parameterNode->type, $context),
						$node->parameters
					),
				]),
				$context,
			),
			$node instanceof GenericTypeNode => $this->mapNamed(
				$node->type->name,
				$this->map($node->genericTypes, $context),
				$context,
			),
			$node instanceof IdentifierTypeNode => $this->mapNamed(
				$node->name,
				new Collection(),
				$context,
			),
			$node instanceof IntersectionTypeNode => new IntersectionType(
				$this->map($node->types, $context)
			),
			$node instanceof NullableTypeNode => new NullableType(
				$this->map($node->type, $context),
			),
			// todo: check
			$node instanceof ThisTypeNode => new StaticType(
				$context->definingType,
			),
			$node instanceof UnionTypeNode => $this->mapUnion($node, $context),
			default                        => new ErrorType((string) $node),
		};
	}

	/**
	 * @param Collection<int, Type> $arguments
	 */
	public function mapNamed(string $type, Collection $arguments, TypeContext $context): Type
	{
		if ($context->typeParameters[$type] ?? null) {
			return new TemplateType(
				name: $type,
			);
		}

		$specialType = match ($this->typeAliasResolver->forComparison($type)) {
			'mixed' => MixedType::get(),
			'never', 'never-return', 'never-returns', 'no-return', 'noreturn' => NeverType::get(),
			'void' => VoidType::get(),
			'int', 'integer', 'positive-int', 'negative-int', 'int-mask', 'int-mask-of' => PrimitiveType::integer(),
			'number' => new UnionType(new Collection([
				PrimitiveType::integer(),
				PrimitiveType::float(),
			])),
			'numeric' => new ErrorType('numeric'),
			'null'    => new ErrorType('null'),
			'float', 'double' => PrimitiveType::float(),
			'string', 'numeric-string', 'literal-string', 'class-string',
			'interface-string', 'trait-string', 'callable-string', 'non-empty-string' => PrimitiveType::string(),
			'bool', 'boolean', 'true', 'false' => PrimitiveType::boolean(),
			'array-key' => new UnionType(new Collection([
				PrimitiveType::integer(),
				PrimitiveType::string(),
			])),
			'callable', 'iterable', 'resource', 'object' => new NamedType($type, $arguments),
			'array', 'associative-array', 'non-empty-array', 'list', 'non-empty-list' => new NamedType('array', $arguments),
			'scalar' => new UnionType(new Collection([
				PrimitiveType::integer(),
				PrimitiveType::float(),
				PrimitiveType::string(),
				PrimitiveType::boolean(),
			])),
			'self'   => $context->definingType,
			'static' => new StaticType($context->definingType),
			default  => null,
		};

		if ($specialType) {
			return $specialType;
		}

		$type = $this->typeAliasResolver->resolve($type, $context->fileContext);

		return new NamedType($type, $arguments);
	}

	private function mapUnion(UnionTypeNode $node, TypeContext $context): Type
	{
		$isNullNode = fn (TypeNode $node) => $node instanceof IdentifierTypeNode && $node->name === 'null';
		$types = $node->types;
		$containsNull = false;

		if (Arr::first($types, $isNullNode)) {
			$types = array_filter($types, fn (TypeNode $node) => !$isNullNode($node));
			$containsNull = true;
		}

		$mappedType = new UnionType(
			$this->map($types, $context)
		);

		return $containsNull ? new NullableType($mappedType) : $mappedType;
	}
}
