<?php

namespace AlexWells\GoodReflection;

use AlexWells\GoodReflection\Cache\Verified\Storage\CacheStorage;
use AlexWells\GoodReflection\Cache\Verified\Storage\SymfonyVarExportCacheStorage;
use AlexWells\GoodReflection\Cache\Verified\VerifiedCache;
use AlexWells\GoodReflection\Definition\BuiltIns\BuiltInCoreDefinitionProvider;
use AlexWells\GoodReflection\Definition\BuiltIns\BuiltInSpecialsDefinitionProvider;
use AlexWells\GoodReflection\Definition\Cache\FileModificationCacheDefinitionProvider;
use AlexWells\GoodReflection\Definition\DefinitionProvider;
use AlexWells\GoodReflection\Definition\Fallback\FallbackDefinitionProvider;
use AlexWells\GoodReflection\Definition\NativePHPDoc\NativePHPDocDefinitionProvider;
use AlexWells\GoodReflection\Definition\NativePHPDoc\PhpDoc\PhpDocStringParser;
use AlexWells\GoodReflection\Definition\NativePHPDoc\TypeAliasResolver;
use AlexWells\GoodReflection\Definition\NativePHPDoc\TypeMapper;
use AlexWells\GoodReflection\Reflector\Reflector;
use AlexWells\GoodReflection\Type\TypeComparator;
use Doctrine\Common\Annotations\PhpParser;
use Illuminate\Container\Container;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Psr\Container\ContainerInterface;

class GoodReflectionBuilder
{
	private Container $container;

	public function __construct()
	{
		$this->container = new Container();

		$this->container->singleton(PhpParser::class);
		$this->container->singleton(TypeAliasResolver::class);
		$this->container->singleton(TypeMapper::class);
		$this->container->singleton(ConstExprParser::class);
		$this->container->singleton(TypeParser::class);
		$this->container->singleton(PhpDocParser::class);
		$this->container->singleton(Lexer::class);
		$this->container->singleton(PhpDocStringParser::class);
		$this->container->singleton(NativePHPDocDefinitionProvider::class);
		$this->container->singleton(TypeComparator::class);
		$this->container->singleton(CacheStorage::class, fn () => new SymfonyVarExportCacheStorage(
			__DIR__ . '/../../../tmp'
		));
		$this->container->singleton(VerifiedCache::class);

		$this->container->singleton(
			DefinitionProvider::class,
			fn (Container $container) => new FallbackDefinitionProvider([
				$container->make(BuiltInSpecialsDefinitionProvider::class),
				$container->make(BuiltInCoreDefinitionProvider::class),
				new FileModificationCacheDefinitionProvider(
					$container->make(NativePHPDocDefinitionProvider::class),
					$container->make(VerifiedCache::class)
				),
			])
		);
		$this->container->singleton(Reflector::class);
	}

	public function build(): ContainerInterface
	{
		return $this->container;
	}
}
