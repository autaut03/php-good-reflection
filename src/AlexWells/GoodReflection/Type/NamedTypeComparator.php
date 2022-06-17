<?php

namespace AlexWells\GoodReflection\Type;

use AlexWells\GoodReflection\Definition\TypeDefinition\TypeParameterDefinition;
use AlexWells\GoodReflection\Reflector\Reflection\ClassReflection;
use AlexWells\GoodReflection\Reflector\Reflection\EnumReflection;
use AlexWells\GoodReflection\Reflector\Reflection\InterfaceReflection;
use AlexWells\GoodReflection\Reflector\Reflection\SpecialTypeReflection;
use AlexWells\GoodReflection\Reflector\Reflection\TraitReflection;
use AlexWells\GoodReflection\Reflector\Reflector;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class NamedTypeComparator
{
	public function __construct(
		private readonly Reflector $reflector
	) {
	}

	public function accepts(NamedType $a, NamedType $b): bool
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
		/** @var TypeParameterDefinition $lastTypeParameter */
		$lastTypeParameter = $typeParameters->last();

		foreach ($typeParameters->zip($a->arguments, $b->arguments) as [$typeParameter, $aArgument, $bArgument]) {
			/** @var TypeParameterDefinition|null $typeParameter */
			/* @var Type|null $aArgument */
			/* @var Type|null $bArgument */
			// If the last type parameter is variadic, use it.
			if (!$typeParameter && $lastTypeParameter->variadic) {
				$typeParameter = $lastTypeParameter;
			}

			// Ignore any extra given parameters.
			if (!$typeParameter) {
				break;
			}

			// Use default values if missing an argument.
			$aArgument ??= $typeParameter->upperBound;
			$bArgument ??= $typeParameter->upperBound;

			// todo: use variance, scope, strategy?
			if (!$this->accepts($aArgument, $bArgument)) {
				return false;
			}
		}

		return true;
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
