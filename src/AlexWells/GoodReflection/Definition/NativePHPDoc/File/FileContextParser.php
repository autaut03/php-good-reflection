<?php

namespace AlexWells\GoodReflection\Definition\NativePHPDoc\File;

use Closure;
use Doctrine\Common\Annotations\PhpParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TraitUseAdaptation;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PHPStan\Analyser\NameScope;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use PHPUnit\Framework\Assert;
use ReflectionClass;
use ReflectionFunction;

/**
 * Provides some context for reflection from file AST nodes.
 */
class FileContextParser
{
	public function __construct(private readonly Parser $phpParser)
	{
	}

	public function parse(ReflectionClass|ReflectionFunction $reflection): FileContext
	{
		$nodes = $this->phpParser->parse(
			file_get_contents($reflection->getFileName())
		);

		$traverser = new NodeTraverser();
		$traverser->addVisitor($nameResolverVisitior = new NameResolver());
		$traverser->addVisitor($classLikesVisitor = new ClassLikeContextParsingVisitor($nameResolverVisitior));
		$traverser->traverse($nodes);

		return new FileContext(
			classLikes: $classLikesVisitor->classLikes,
			anonymousClassLikes: $classLikesVisitor->anonymousClassLikes
		);
	}
}
