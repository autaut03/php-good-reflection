<?php

namespace Tests\Integration\Type;

use AlexWells\GoodReflection\Type\Combinatorial\IntersectionType;
use AlexWells\GoodReflection\Type\Combinatorial\UnionType;
use AlexWells\GoodReflection\Type\PrimitiveType;
use AlexWells\GoodReflection\Type\Special\NullableType;
use AlexWells\GoodReflection\Type\Type;
use AlexWells\GoodReflection\Type\TypeComparator;
use Generator;
use Illuminate\Support\Collection;
use Tests\Integration\TestCase;

/**
 * @see TypeComparator
 */
class TypeComparatorTest extends TestCase
{
	private TypeComparator $comparator;

	protected function setUp(): void
	{
		parent::setUp();

		$this->comparator = $this->container->get(TypeComparator::class);
	}

	/**
	 * @dataProvider acceptsProvider
	 */
	public function testAccepts(bool $expected, Type $a, Type $b): void
	{
		self::assertSame(
			$expected,
			$this->comparator->accepts($a, $b),
			"Type {$a} does " . ($expected ? 'not ' : '') . "accept type {$b}"
		);
	}

	public function acceptsProvider(): Generator
	{
		yield '?string <= ?string' => [
			true,
			new NullableType(PrimitiveType::string()),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= string' => [
			true,
			new NullableType(PrimitiveType::string()),
			PrimitiveType::string(),
		];

		yield 'string <= ?string' => [
			false,
			PrimitiveType::string(),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= ?int' => [
			false,
			new NullableType(PrimitiveType::string()),
			new NullableType(PrimitiveType::integer()),
		];

		yield '?int <= ?string' => [
			false,
			new NullableType(PrimitiveType::integer()),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= string|int|null' => [
			false,
			new NullableType(PrimitiveType::string()),
			new NullableType(new UnionType(new Collection([PrimitiveType::string(), PrimitiveType::integer()]))),
		];

		yield 'string|int|null <= string' => [
			true,
			new NullableType(new UnionType(new Collection([PrimitiveType::string(), PrimitiveType::integer()]))),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= (?string)|int' => [
			false,
			new NullableType(PrimitiveType::string()),
			new UnionType(new Collection([new NullableType(PrimitiveType::string()), PrimitiveType::integer()])),
		];

		yield '(?string)|int <= ?string' => [
			true,
			new UnionType(new Collection([new NullableType(PrimitiveType::string()), PrimitiveType::integer()])),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= ?(string&integer)' => [
			true,
			new NullableType(PrimitiveType::string()),
			new NullableType(new IntersectionType(new Collection([PrimitiveType::string(), PrimitiveType::integer()]))),
		];

		yield '?(string&integer) <= ?string' => [
			false,
			new NullableType(new IntersectionType(new Collection([PrimitiveType::string(), PrimitiveType::integer()]))),
			new NullableType(PrimitiveType::string()),
		];

		yield '?string <= (?string)&integer' => [
			true,
			new NullableType(PrimitiveType::string()),
			new IntersectionType(new Collection([new NullableType(PrimitiveType::string()), PrimitiveType::integer()])),
		];

		yield '(?string)&integer <= ?string' => [
			false,
			new IntersectionType(new Collection([new NullableType(PrimitiveType::string()), PrimitiveType::integer()])),
			new NullableType(PrimitiveType::string()),
		];
	}
}
