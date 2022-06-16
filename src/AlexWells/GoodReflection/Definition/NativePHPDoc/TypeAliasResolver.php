<?php

namespace AlexWells\GoodReflection\Definition\NativePHPDoc;

use Doctrine\Common\Annotations\PhpParser;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionFunction;

class TypeAliasResolver
{
	/** @var array<string, array<string, string>> */
	private array $aliases = [];

	public function __construct(private readonly PhpParser $phpParser)
	{
	}

	/**
	 * If the given symbol was imported as an alias in the given class, the original value is returned.
	 */
	public function resolve(string $symbol, ReflectionClass|ReflectionFunction $reflection): string
	{
		// Globally referenced types should always be treated as type names.
		if (str_starts_with($symbol, '\\')) {
			return Str::after($symbol, '\\');
		}

		$lowerSymbol = mb_strtolower($symbol);

		// Also any special built-in types do not require an explicit import.
		if (
			in_array($lowerSymbol, [
				'mixed',
				'void',
				'never',
				'string',
				'int',
				'float',
				'bool',
				'array',
				'object',
				'callable',
				'iterable',
				'resource',
				'null',
				'true',
				'false',
				'static',
				'self',
				'parent',
			], true)
		) {
			if ($lowerSymbol === 'true' || $lowerSymbol === 'false') {
				return 'bool';
			}

			return $lowerSymbol;
		}

		$alias = $this->imported($symbol, $reflection);

		if ($alias !== $symbol) {
			return $alias;
		}

		return $this->resolveNamespaced($symbol, $reflection);
	}

	public function imported(string $symbol, ReflectionClass|ReflectionFunction $reflection): string
	{
		$alias = $symbol;

		$namespaceParts = explode('\\', $symbol);
		$lastPart = array_shift($namespaceParts);

		if ($lastPart) {
			$alias = mb_strtolower($lastPart);
		}

		$aliases = $this->aliases($reflection);

		if (!isset($aliases[$alias])) {
			return $symbol;
		}

		$full = $aliases[$alias];

		if (!empty($namespaceParts)) {
			$full .= '\\' . implode('\\', $namespaceParts);
		}

		return $full;
	}

	private function aliases(ReflectionClass|ReflectionFunction $reflection): array
	{
		return $this->aliases[get_class($reflection) . $reflection->name] ??= $this->phpParser->parseUseStatements($reflection);
	}

	private function resolveNamespaced(string $symbol, ReflectionClass|ReflectionFunction $reflection): string
	{
		if ($reflection instanceof ReflectionFunction) {
			$reflection = $reflection->getClosureScopeClass();
		}

		if (!$reflection) {
			return $symbol;
		}

		$namespace = $reflection->getNamespaceName();

		if (!$namespace) {
			return $symbol;
		}

		return $namespace . '\\' . $symbol;
	}
}
