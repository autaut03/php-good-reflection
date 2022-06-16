<?php

namespace AlexWells\GoodReflection\Definition\NativePHPDoc;

use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Type\Combinatorial\IntersectionType;
use AlexWells\GoodReflection\Type\Combinatorial\UnionType;
use AlexWells\GoodReflection\Type\NamedType;
use AlexWells\GoodReflection\Type\PrimitiveType;
use AlexWells\GoodReflection\Type\Special\MixedType;
use AlexWells\GoodReflection\Type\Special\NeverType;
use AlexWells\GoodReflection\Type\Special\NullableType;
use AlexWells\GoodReflection\Type\Special\StaticType;
use AlexWells\GoodReflection\Type\Special\VoidType;
use AlexWells\GoodReflection\Type\Template\TemplateType;
use AlexWells\GoodReflection\Type\Type;
use Illuminate\Support\Collection;
use InvalidArgumentException;
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
use ReflectionClass;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Webmozart\Assert\Assert;

class TypeMapper
{
	public function __construct(
		private readonly TypeAliasResolver $typeAliasResolver,
	) {
	}

	public function resolve(
		ReflectionType|string|null $nativeType,
		?TypeNode $phpDocType,
		ReflectionClass|ReflectionFunction $reflection,
		Collection|callable $findTypeParameter
	): Type {
		Assert::true($nativeType || $phpDocType, 'Both native and PHPDoc types are missing.');

		return $phpDocType ?
			$this->mapPhpDocType($phpDocType, $reflection, $findTypeParameter) :
			$this->mapNativeType($nativeType);
	}

	public function mapPhpDocType(TypeNode $node, ReflectionClass|ReflectionFunction $reflection, Collection|callable $findTypeParameter): Type
	{
		return match (true) {
			$node instanceof ArrayTypeNode => PrimitiveType::array(
				$this->mapPhpDocType($node->type, $reflection, $findTypeParameter)
			),
			$node instanceof CallableTypeNode => $this->mapPhpDocNamedType(
				$node->identifier->name,
				new Collection([
					$this->mapPhpDocType($node->returnType, $reflection, $findTypeParameter),
					...array_map(
						fn (CallableTypeParameterNode $parameterNode) => $this->mapPhpDocType($parameterNode->type, $reflection, $findTypeParameter),
						$node->parameters
					),
				]),
				$reflection,
				$findTypeParameter
			),
			$node instanceof GenericTypeNode => $this->mapPhpDocNamedType(
				$node->type->name,
				$this->mapPhpDocTypes($node->genericTypes, $reflection, $findTypeParameter),
				$reflection,
				$findTypeParameter
			),
			$node instanceof IdentifierTypeNode => $this->mapPhpDocNamedType(
				$node->name,
				new Collection(),
				$reflection,
				$findTypeParameter
			),
			$node instanceof IntersectionTypeNode => new IntersectionType(
				$this->mapPhpDocTypes($node->types, $reflection, $findTypeParameter)
			),
			$node instanceof NullableTypeNode => new NullableType(
				$this->mapPhpDocType($node->type, $reflection, $findTypeParameter),
			),
			$node instanceof ThisTypeNode => new StaticType(
				PrimitiveType::object()
			),
			$node instanceof UnionTypeNode => new UnionType(
				$this->mapPhpDocTypes($node->types, $reflection, $findTypeParameter)
			),
			default => new InvalidArgumentException('PHPDoc type node [' . $node::class . '] is not supported.'),
		};
	}

	public function mapPhpDocTypes(array|Collection $types, ReflectionClass|ReflectionFunction $reflection, Collection|callable $findTypeParameter): Collection
	{
		return Collection::wrap($types)->map(fn ($type) => $this->mapPhpDocType($type, $reflection, $findTypeParameter));
	}

	public function mapPhpDocNamedType(string $type, Collection $parameters, ReflectionClass|ReflectionFunction $reflection, Collection|callable $findTypeParameter): Type
	{
		if ($findTypeParameter instanceof Collection) {
			$findTypeParameter = fn (string $name) => $findTypeParameter->first(fn (TypeParameterDefinition $typeParameter) => $typeParameter->name === $name);
		}

		$specialType = match (mb_strtolower($type)) {
			'mixed'  => MixedType::get(),
			'never'  => NeverType::get(),
			'static' => new StaticType(
				MixedType::get(),
			),
			'void'  => VoidType::get(),
			default => null,
		};

		if ($specialType) {
			return $specialType;
		}

		if ($typeParameter = $findTypeParameter($type)) {
			return new TemplateType(
				name: $type,
			);
		}

		$type = $this->typeAliasResolver->resolve($type, $reflection);

		return new NamedType($type, $parameters);
	}

	public function mapNativeType(ReflectionType|string $type): Type
	{
		$mappedType = match (true) {
			$type instanceof ReflectionIntersectionType => new IntersectionType(
				...$this->mapNativeTypes($type->getTypes())
			),
			$type instanceof ReflectionUnionType => new UnionType(
				...$this->mapNativeTypes($type->getTypes())
			),
			$type instanceof ReflectionNamedType => $this->mapNativeNamedType($type->getName()),
			is_string($type)                     => $this->mapNativeNamedType($type),
			default                              => new InvalidArgumentException('Native type reflection [' . get_class($type) . '] is not supported.'),
		};

		return $type instanceof ReflectionType && $type->allowsNull() ?
			new NullableType($mappedType) :
			$mappedType;
	}

	public function mapNativeTypes(array $types): array
	{
		return array_map(fn ($type) => $this->mapNativeType($type), $types);
	}

	private function mapNativeNamedType(string $name): Type
	{
		return match (mb_strtolower($name)) {
			'mixed'  => MixedType::get(),
			'never'  => NeverType::get(),
			'static' => new StaticType(
				MixedType::get(),
			),
			'void'  => VoidType::get(),
			default => new NamedType($name),
		};
	}
}
