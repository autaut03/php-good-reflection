<?php

namespace AlexWells\GoodReflection\Definition\Cache;

use AlexWells\GoodReflection\Cache\Verified\VerifiedCache;
use AlexWells\GoodReflection\Definition\DefinitionProvider;
use AlexWells\GoodReflection\Definition\TypeDefinition;
use RuntimeException;
use Throwable;

class FileModificationCacheDefinitionProvider implements DefinitionProvider
{
	public function __construct(
		private readonly DefinitionProvider $delegate,
		private readonly VerifiedCache $verifiedCache
	) {
	}

	public function forType(string $type): ?TypeDefinition
	{
		return $this->verifiedCache->remember(
			"type:{$type}",
			fn (TypeDefinition $definition) => $definition->fileName ? (string) $this->fileModificationTime($definition->fileName) : null,
			fn ()                           => $this->delegate->forType($type),
		);
	}

	private function fileModificationTime(string $fileName): int
	{
		try {
			$variableKey = filemtime($fileName);

			if ($variableKey === false) {
				throw new RuntimeException();
			}
		} catch (Throwable) {
			$variableKey = time();
		}

		return $variableKey;
	}
}
