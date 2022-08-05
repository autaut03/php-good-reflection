<?php

namespace AlexWells\GoodReflection\Type;

use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Reflector\Reflection\ClassReflection;
use AlexWells\GoodReflection\Reflector\Reflection\EnumReflection;
use AlexWells\GoodReflection\Reflector\Reflection\InterfaceReflection;
use AlexWells\GoodReflection\Reflector\Reflection\SpecialTypeReflection;
use AlexWells\GoodReflection\Reflector\Reflection\TraitReflection;
use AlexWells\GoodReflection\Reflector\Reflector;
use AlexWells\GoodReflection\Type\Combinatorial\IntersectionType;
use AlexWells\GoodReflection\Type\Combinatorial\TupleType;
use AlexWells\GoodReflection\Type\Combinatorial\UnionType;
use AlexWells\GoodReflection\Type\Special\ErrorType;
use AlexWells\GoodReflection\Type\Special\MixedType;
use AlexWells\GoodReflection\Type\Special\NeverType;
use AlexWells\GoodReflection\Type\Special\NullableType;
use AlexWells\GoodReflection\Type\Special\StaticType;
use AlexWells\GoodReflection\Type\Special\VoidType;
use AlexWells\GoodReflection\Type\Template\TemplateType;
use AlexWells\GoodReflection\Type\Template\TemplateTypeVariance;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Tests\Integration\Type\TypeComparatorTest;

/**
 * @see TypeComparatorTest
 */
class TypeComparator
{
	public function __construct(
		private readonly Reflector $reflector
	) {
	}

	public function accepts(Type $a, Type $b): bool
	{
		return match (true) {
			$a instanceof NeverType || $b instanceof NeverType => false,
			$a instanceof ErrorType || $b instanceof ErrorType => false,
			$a instanceof VoidType                             => true,
			$b instanceof VoidType                             => false,
			$a instanceof MixedType                            => true,
			$b instanceof MixedType                            => false,
			$a instanceof IntersectionType                     => $a->types->every(fn (Type $type)                     => $this->accepts($type, $b)),
			$b instanceof IntersectionType                     => $b->types->some(fn (Type $type)                     => $this->accepts($a, $type)),
			$a instanceof UnionType                            => $a->types->some(fn (Type $type)                            => $this->accepts($type, $b)),
			$b instanceof UnionType                            => $b->types->every(fn (Type $type)                            => $this->accepts($a, $type)),
			$a instanceof NullableType                         => $this->accepts($a->innerType, $b instanceof NullableType ? $b->innerType : $b),
			$b instanceof NullableType                         => false,
			// This operates under the assumption that static types should only exist in a scope of a single class,
			// the one that declares a function with that type. If other class extends the one that declares a static,
			// baseType of static types should be changed similarly to how it's done with template types.
			$a instanceof StaticType                           => $this->accepts($a->baseType, $b),
			$b instanceof StaticType                           => $this->accepts($a, $b->baseType),
			// todo
			$a instanceof TemplateType                           => false,
			$b instanceof TemplateType                           => false,
			$a instanceof TupleType                            => $this->acceptsTuple($a, $b),
			$b instanceof TupleType                            => false,
			$a instanceof NamedType && $b instanceof NamedType => $this->acceptsNamed($a, $b),
			default                                            => throw new InvalidArgumentException("Unsupported types given: {$a} (" . $a::class . ") and {$b} (" . $b::class . ')')
		};
	}

	public function acceptsNamed(NamedType $a, NamedType $b): bool
	{
		// If dealing with inheritance, convert bigger type into smaller type
		// and then compare to make sure type arguments aren't messed up.
		if ($a->name !== $b->name) {
			$descendant = $this->findDescendant($b, $a->name);

			// Not a super type.
			if (!$descendant) {
				return false;
			}

			return $this->accepts($a, $descendant);
		}

		$aReflection = $this->reflector->forNamedType($a);

		/** @var Collection<TypeParameterDefinition> $typeParameters */
		$typeParameters = !$aReflection instanceof EnumReflection ? $aReflection->typeParameters() : new Collection();

		/** @var array<array{TypeParameterDefinition, Type, Type}> $pairs */
		$pairs = [];
		$aArguments = clone $a->arguments;
		$bArguments = clone $b->arguments;

		foreach ($typeParameters as $i => $typeParameter) {
			if ($typeParameter->variadic) {
				$pairs[] = [$typeParameter, new TupleType($aArguments), new TupleType($bArguments)];

				break;
			}

			$aArgument = $aArguments->shift();
			$bArgument = $bArguments->shift();

			if (!$aArgument || !$bArgument) {
				throw new InvalidArgumentException('Missing type argument #' . ($i + 1) . " {$typeParameter} when comparing named types '{$a}' and '{$b}'");
			}

			$pairs[] = [$typeParameter, $aArgument, $bArgument];
		}

		foreach ($pairs as [$typeParameter, $aArgument, $bArgument]) {
			$validVariance = match ($typeParameter->variance) {
				TemplateTypeVariance::INVARIANT     => $aArgument->equals($bArgument),
				TemplateTypeVariance::COVARIANT     => $this->accepts($aArgument, $bArgument),
				TemplateTypeVariance::CONTRAVARIANT => $this->accepts($bArgument, $aArgument),
			};

			if (!$validVariance) {
				return false;
			}
		}

		return true;
	}

	private function acceptsTuple(TupleType $a, Type $b): bool
	{
		return $b instanceof TupleType &&
			$b->types->count() >= $a->types->count() &&
			$a->types->every(fn (Type $type, int $i) => $this->accepts($type, $b->types[$i]));
	}

	private function findDescendant(NamedType $a, string $className): ?NamedType
	{
		$aReflection = $this->reflector->forNamedType($a);

		/** @var NamedType[] $descendants */
		$descendants = match (true) {
			$aReflection instanceof ClassReflection => $aReflection
				->implements()
				->concat([$aReflection->extends()])
				->filter(),
			$aReflection instanceof InterfaceReflection => $aReflection
				->extends(),
			$aReflection instanceof TraitReflection => new Collection(),
			$aReflection instanceof EnumReflection  => $aReflection
				->implements(),
			$aReflection instanceof SpecialTypeReflection => $aReflection
				->superTypes(),
			default => throw new InvalidArgumentException('Unsupported type of reflection (' . $aReflection::class . ') given.'),
		};

		foreach ($descendants as $type) {
			if ($type->name === $className) {
				return $type;
			}
		}

		foreach ($descendants as $type) {
			if ($super = $this->findDescendant($type, $className)) {
				return $super;
			}
		}

		return null;
	}
}
