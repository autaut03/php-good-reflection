<?php

namespace AlexWells\GoodReflection\Type;

use AlexWells\GoodReflection\Type\Combinatorial\UnionType;
use Illuminate\Support\Collection;

class PrimitiveType
{
	private static NamedType $object;

	private static NamedType $resource;

	private static NamedType $string;

	private static NamedType $boolean;

	private static NamedType $integer;

	private static NamedType $float;

	private static UnionType $arrayKey;

	public static function callable(Type $returnType, Type ...$parameters): NamedType
	{
		return new NamedType('callable', new Collection([
			$returnType,
			...$parameters,
		]));
	}

	public static function array(Type $value, ?Type $key = null): NamedType
	{
		return new NamedType('array', new Collection([
			$key ?? static::arrayKey(),
			$value,
		]));
	}

	public static function iterable(Type $value, ?Type $key = null): NamedType
	{
		return new NamedType('iterable', new Collection([
			$key ?? static::arrayKey(),
			$value,
		]));
	}

	public static function object(): NamedType
	{
		return self::$object ??= new NamedType('object');
	}

	public static function resource(): NamedType
	{
		return self::$resource ??= new NamedType('resource');
	}

	public static function string(): NamedType
	{
		return self::$string ??= new NamedType('string');
	}

	public static function boolean(): NamedType
	{
		return self::$boolean ??= new NamedType('bool');
	}

	public static function integer(): NamedType
	{
		return self::$integer ??= new NamedType('int');
	}

	public static function float(): NamedType
	{
		return self::$float ??= new NamedType('float');
	}

	private static function arrayKey(): UnionType
	{
		return static::$arrayKey ??= new UnionType(
			new Collection([
				static::integer(),
				static::string(),
			]),
		);
	}
}
