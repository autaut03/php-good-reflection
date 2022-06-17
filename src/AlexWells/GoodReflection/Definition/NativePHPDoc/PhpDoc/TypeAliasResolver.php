<?php

namespace AlexWells\GoodReflection\Definition\NativePHPDoc\PhpDoc;

use AlexWells\GoodReflection\Definition\NativePHPDoc\File\FileContext;
use Illuminate\Support\Str;

class TypeAliasResolver
{
	public function forComparison(string $symbol): string
	{
		// Globally referenced types should always be treated as type names.
		if (str_starts_with($symbol, '\\')) {
			return Str::after($symbol, '\\');
		}

		return mb_strtolower($symbol);
	}

	public function resolve(string $symbol, FileContext $fileContext): string
	{
		// Globally referenced types should always be treated as type names.
		if (str_starts_with($symbol, '\\')) {
			return Str::after($symbol, '\\');
		}

		$lowerSymbol = mb_strtolower($symbol);

		// There are many implicitly imported types.
		if (
			in_array($lowerSymbol, [
				'mixed', 'void', 'never', 'string', 'int', 'float', 'bool',
				'array', 'object', 'callable', 'iterable', 'null', 'true',
				'false', 'static', 'self', 'parent',
			], true)
		) {
			return $lowerSymbol;
		}

		$alias = $this->imported($symbol, $fileContext);

		if ($alias !== $symbol) {
			return $alias;
		}

		if ($fileContext->namespace) {
			return "{$fileContext->namespace}\\{$symbol}";
		}

		return $symbol;
	}

	public function imported(string $symbol, FileContext $fileContext): string
	{
		$alias = $symbol;

		$namespaceParts = explode('\\', $symbol);
		$lastPart = array_shift($namespaceParts);

		if ($lastPart) {
			$alias = mb_strtolower($lastPart);
		}

		if (!isset($fileContext->uses[$alias])) {
			return $symbol;
		}

		$full = $fileContext->uses[$alias];

		if (!empty($namespaceParts)) {
			$full .= '\\' . implode('\\', $namespaceParts);
		}

		return $full;
	}
}
