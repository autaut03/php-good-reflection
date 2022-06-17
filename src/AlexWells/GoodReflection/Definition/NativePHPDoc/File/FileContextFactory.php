<?php

namespace AlexWells\GoodReflection\Definition\NativePHPDoc\File;

use Doctrine\Common\Annotations\PhpParser;
use ReflectionClass;
use ReflectionFunction;

class FileContextFactory
{
	public function __construct(private readonly PhpParser $phpParser)
	{
	}

	public function make(ReflectionClass|ReflectionFunction $reflection): FileContext
	{
		return new FileContext(
			namespace: match (true) {
				$reflection instanceof ReflectionClass    => $reflection->getNamespaceName() ?: null,
				$reflection instanceof ReflectionFunction => $reflection->getClosureScopeClass()?->getNamespaceName() ?: null,
			},
			uses: $this->phpParser->parseUseStatements($reflection)
		);
	}
}
