<?php

namespace AlexWells\GoodReflection\Definition\NativePHPDoc\File\FileClassLikeContext;

class TraitUse
{
	public function __construct(
		public readonly string $name,
		public readonly ?string $docComment,
		public readonly array $aliases,
	) {
	}
}
