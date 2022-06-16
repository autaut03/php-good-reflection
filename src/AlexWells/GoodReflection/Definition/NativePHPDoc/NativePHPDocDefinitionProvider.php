<?php

namespace AlexWells\GoodReflection\Definition\NativePHPDoc;

use AlexWells\GoodReflection\Definition\DefinitionProvider;
use AlexWells\GoodReflection\Definition\NativePHPDoc\PhpDoc\PhpDocStringParser;
use AlexWells\GoodReflection\Definition\TypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\ClassTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\EnumTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\FunctionParameterDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\InterfaceTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\MethodDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\PropertyDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\TraitTypeDefinition;
use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Type\Template\TemplateTypeVariance;
use AlexWells\GoodReflection\Type\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ReflectionClass;
use ReflectionEnum;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use TenantCloud\Standard\Lazy\Lazy;
use function TenantCloud\Standard\Lazy\lazy;

class NativePHPDocDefinitionProvider implements DefinitionProvider
{
	public function __construct(
		private readonly PhpDocStringParser $phpDocStringParser,
		private readonly TypeAliasResolver $typeAliasResolver,
		private readonly TypeMapper $typeResolver
	) {
	}

	public function forType(string $type): ?TypeDefinition
	{
		return match (true) {
			enum_exists($type) => $this->forEnum($type),
			class_exists($type), interface_exists($type), trait_exists($type) => $this->forClassLike($type),
			default => null
		};
	}

	private function forClassLike(string $type): TypeDefinition
	{
		$reflection = new ReflectionClass($type);

		$phpDoc = $this->phpDocStringParser->parse($reflection);
		$typeParameters = $this->typeParameters($reflection, $phpDoc);

		return match (true) {
			$reflection->isTrait() => new TraitTypeDefinition(
				qualifiedName: $this->qualifiedName($reflection),
				fileName: $this->fileName($reflection),
				builtIn: !$reflection->isUserDefined(),
				typeParameters: $typeParameters,
				uses: $this->traits($reflection),
				properties: $this->properties($reflection, $typeParameters),
				methods: $this->methods($reflection, $typeParameters),
			),
			$reflection->isInterface() => new InterfaceTypeDefinition(
				qualifiedName: $this->qualifiedName($reflection),
				fileName: $this->fileName($reflection),
				builtIn: !$reflection->isUserDefined(),
				typeParameters: $typeParameters,
				extends: $this->interfaces($reflection, $phpDoc, $typeParameters),
				methods: $this->methods($reflection, $typeParameters),
			),
			default => new ClassTypeDefinition(
				qualifiedName: $this->qualifiedName($reflection),
				fileName: $this->fileName($reflection),
				builtIn: !$reflection->isUserDefined(),
				anonymous: $reflection->isAnonymous(),
				final: $reflection->isFinal(),
				abstract: $reflection->isAbstract(),
				typeParameters: $typeParameters,
				extends: $this->parent($reflection, $phpDoc, $typeParameters),
				implements: $this->interfaces($reflection, $phpDoc, $typeParameters),
				uses: $this->traits($reflection),
				properties: $this->properties($reflection, $typeParameters),
				methods: $this->methods($reflection, $typeParameters),
			)
		};
	}

	private function forEnum(string $type): TypeDefinition
	{
		$reflection = new ReflectionEnum($type);

		$phpDoc = $this->phpDocStringParser->parse($reflection);

		return new EnumTypeDefinition(
			qualifiedName: $this->qualifiedName($reflection),
			fileName: $this->fileName($reflection),
			builtIn: !$reflection->isUserDefined(),
			implements: $this->interfaces($reflection, $phpDoc, new Collection()),
			uses: $this->traits($reflection),
			cases: new Collection(),
			methods: $this->methods($reflection, new Collection()),
		);
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 */
	private function qualifiedName(ReflectionClass $reflection): string
	{
		return $reflection->getName();
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 */
	private function fileName(ReflectionClass $reflection): ?string
	{
		return $reflection->getFileName() ?: null;
	}

	/**
	 * @param ReflectionClass<object>                  $reflection
	 * @param Collection<int, TypeParameterDefinition> $typeParameters
	 *
	 * @return Collection<int, PropertyDefinition>
	 */
	private function properties(ReflectionClass $reflection, Collection $typeParameters): Collection
	{
		$constructorPhpDoc = $this->phpDocStringParser->parse(
			$reflection->getConstructor()?->getDocComment() ?: ''
		);

		return Collection::make($reflection->getProperties())
			->map(function (ReflectionProperty $property) use ($typeParameters, $reflection, $constructorPhpDoc) {
				$phpDoc = $this->phpDocStringParser->parse($property);

				// Get first @var tag (if any specified). Works for both regular and promoted properties.
				/** @var TypeNode|null $phpDocType */
				$phpDocType = $phpDoc->getVarTagValues()[0]->type ?? null;

				// If none found, fallback to @param tag if it's a promoted property. The check for promoted property
				// is important because there could be a property with the same name as a parameter, but those being unrelated.
				if (!$phpDocType && $property->isPromoted()) {
					/** @var ParamTagValueNode|null $paramNode */
					$paramNode = Arr::first(
						$constructorPhpDoc->getParamTagValues(),
						fn (ParamTagValueNode $node) => $node->parameterName === $property->getName()
					);

					$phpDocType = $paramNode?->type;
				}

				return new PropertyDefinition(
					name: $property->getName(),
					type: $this->typeResolver->resolve(
						$property->getType(),
						$phpDocType,
						$reflection,
						$typeParameters
					)
				);
			});
	}

	/**
	 * @param ReflectionClass<object>                  $reflection
	 * @param Collection<int, TypeParameterDefinition> $typeParameters
	 *
	 * @return Collection<int, MethodDefinition>
	 */
	private function methods(ReflectionClass $reflection, Collection $typeParameters): Collection
	{
		return Collection::make($reflection->getMethods())
			->map(function (ReflectionMethod $method) use ($typeParameters, $reflection) {
				$phpDoc = $this->phpDocStringParser->parse($method);

				// Get first @return tag (if any specified).
				$phpDocType = $phpDoc->getReturnTagValues()[0]->type ?? null;
				$methodTypeParameters = $this->typeParameters($method, $phpDoc);
				$allTypeParameters = $typeParameters->concat($methodTypeParameters);

				return new MethodDefinition(
					name: $method->getName(),
					typeParameters: $methodTypeParameters,
					parameters: $this->functionParameters($method, $phpDoc, $allTypeParameters),
					returnType: $this->typeResolver->resolve(
						$method->getReturnType(),
						$phpDocType,
						$reflection,
						$allTypeParameters
					)
				);
			});
	}

	/**
	 * @param ReflectionMethod|ReflectionClass<object> $reflection
	 *
	 * @return Collection<int, TypeParameterDefinition>
	 */
	private function typeParameters(ReflectionMethod|ReflectionClass $reflection, PhpDocNode $phpDoc): Collection
	{
		/** @var Collection<string, Lazy<TypeParameterDefinition>> $lazyTypeParametersMap */
		$lazyTypeParametersMap = new Collection();

		// For whatever reason phpstan/phpdoc-parser doesn't parse the differences between @template and @template-covariant,
		// so instead of using ->getTemplateTagValues() we'll filter tags manually.
		foreach ($phpDoc->getTags() as $node) {
			if (!$node->value instanceof TemplateTagValueNode) {
				continue;
			}

			/** @var TemplateTagValueNode $value */
			$value = $node->value;

			$lazyTypeParametersMap[$value->name] = lazy(
				fn () => new TypeParameterDefinition(
					name: $value->name,
					variadic: false,
					upperBound: $value->bound ?
						$this->typeResolver->mapPhpDocType(
							$value->bound,
							$reflection instanceof ReflectionMethod ? $reflection->getDeclaringClass() : $reflection,
							fn (string $key) => ($lazyTypeParametersMap[$key] ?? null)?->value()
						) :
						null,
					variance: match (true) {
						Str::endsWith($node->name, '-covariant') => TemplateTypeVariance::COVARIANT,
						default => TemplateTypeVariance::INVARIANT
					}
				)
			);
		}

		return $lazyTypeParametersMap
			->values()
			->map(fn (Lazy $lazy) => $lazy->value());
	}

	/**
	 * @param Collection<int, TypeParameterDefinition> $typeParameters
	 *
	 * @return Collection<int, FunctionParameterDefinition>
	 */
	private function functionParameters(ReflectionMethod $reflection, PhpDocNode $phpDoc, Collection $typeParameters): Collection
	{
		return Collection::make($reflection->getParameters())
			->map(function (ReflectionParameter $parameter) use ($typeParameters, $reflection, $phpDoc) {
				/** @var ParamTagValueNode|null $phpDocType */
				$phpDocType = Arr::first(
					$phpDoc->getParamTagValues(),
					fn (ParamTagValueNode $node) => Str::after($node->parameterName, '$') === $parameter->getName()
				);

				return new FunctionParameterDefinition(
					name: $parameter->getName(),
					type: $this->typeResolver->resolve(
						$parameter->getType(),
						$phpDocType?->type,
						$reflection->getDeclaringClass(),
						$typeParameters
					)
				);
			});
	}

	/**
	 * @param ReflectionClass<object>                  $reflection
	 * @param Collection<int, TypeParameterDefinition> $typeParameters
	 */
	private function parent(ReflectionClass $reflection, PhpDocNode $phpDoc, Collection $typeParameters): ?Type
	{
		$parentClass = $reflection->getParentClass() ? $reflection->getParentClass()->getName() : null;

		if (!$parentClass) {
			return null;
		}

		/** @var PhpDocTagNode|null $tag */
		$tag = Arr::first(
			$phpDoc->getTags(),
			fn (PhpDocTagNode $node) => $node->value instanceof ExtendsTagValueNode &&
				$parentClass === $this->typeAliasResolver->resolve($node->value->type->type->name, $reflection)
		);

		/** @var ExtendsTagValueNode|null $tagValue */
		$tagValue = $tag?->value;

		return $this->typeResolver->resolve(
			$parentClass,
			$tagValue?->type,
			$reflection,
			$typeParameters
		);
	}

	/**
	 * @param ReflectionClass<object>                  $reflection
	 * @param Collection<int, TypeParameterDefinition> $typeParameters
	 *
	 * @return Collection<int, Type>
	 */
	private function interfaces(ReflectionClass $reflection, PhpDocNode $phpDoc, Collection $typeParameters): Collection
	{
		return Collection::make($reflection->getInterfaceNames())
			->map(function (string $className) use ($typeParameters, $reflection, $phpDoc) {
				/** @var PhpDocTagNode|null $tag */
				$tag = Arr::first(
					$phpDoc->getTags(),
					fn (PhpDocTagNode $node) => ($node->value instanceof ImplementsTagValueNode || $node->value instanceof ExtendsTagValueNode) &&
						$className === $this->typeAliasResolver->resolve($node->value->type->type->name, $reflection)
				);

				/** @var ImplementsTagValueNode|ExtendsTagValueNode|null $tagValue */
				$tagValue = $tag?->value;

				return $this->typeResolver->resolve(
					$className,
					$tagValue?->type,
					$reflection,
					$typeParameters
				);
			});
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 *
	 * @return Collection<int, Type>
	 */
	private function traits(ReflectionClass $reflection): Collection
	{
		// Because traits can be used multiple types, @uses annotations can't be specified in the class PHPDoc and instead
		// must be specified above the `use TraitName;` itself. PHP's native reflection does not give you reflection
		// on PHPDoc for trait uses, so we'll just say generic traits are unsupported due to the complexity of doing so.
		return Collection::make($reflection->getTraitNames())
			->map(fn (string $className) => $this->typeResolver->mapNativeType($className));
	}
}
