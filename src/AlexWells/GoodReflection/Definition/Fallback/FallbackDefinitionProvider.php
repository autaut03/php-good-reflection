<?php

namespace AlexWells\GoodReflection\Definition\Fallback;

use AlexWells\GoodReflection\Definition\DefinitionProvider;
use AlexWells\GoodReflection\Definition\TypeDefinition;

class FallbackDefinitionProvider implements DefinitionProvider
{
	/**
	 * @param DefinitionProvider[] $providers
	 */
	public function __construct(
		private readonly array $providers
	) {
	}

	public function forType(string $type): ?TypeDefinition
	{
		return $this->fallback(
			$type,
			fn (DefinitionProvider $provider) => $provider->forType($type)
		);
	}

	private function fallback(string $type, callable $callback): mixed
	{
		foreach ($this->providers as $provider) {
			$definition = $callback($provider);

			if ($definition) {
				return $definition;
			}
		}

		return null;
	}
}
