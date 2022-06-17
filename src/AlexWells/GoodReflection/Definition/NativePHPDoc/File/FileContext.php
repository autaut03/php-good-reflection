<?php

namespace AlexWells\GoodReflection\Definition\NativePHPDoc\File;

class FileContext
{
	/**
	 * @param array<string, string> $uses
	 */
	public function __construct(
		public readonly ?string $namespace,
		public readonly array $uses,
	) {
	}
}
