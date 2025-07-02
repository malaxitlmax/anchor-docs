<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Analyzer;

use Anchor\DocsCoverage\Model\CodeElement;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

final class CodeAnalyzer
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * @param string[] $sourcePaths
     * @param string[] $excludePaths
     * @return CodeElement[]
     */
    public function analyze(array $sourcePaths, array $excludePaths = []): array
    {
        $elements = [];
        
        foreach ($sourcePaths as $sourcePath) {
            if (!file_exists($sourcePath)) {
                continue;
            }
            
            $finder = new Finder();
            $finder->files()
                ->in($sourcePath)
                ->name('*.php');
                
            foreach ($excludePaths as $excludePath) {
                if (is_dir($excludePath)) {
                    $finder->exclude(basename($excludePath));
                }
            }
            
            foreach ($finder as $file) {
                $fileElements = $this->analyzeFile($file->getRealPath());
                $elements = array_merge($elements, $fileElements);
            }
        }
        
        return $elements;
    }

    /**
     * @return CodeElement[]
     */
    private function analyzeFile(string $filePath): array
    {
        $code = file_get_contents($filePath);
        if ($code === false) {
            return [];
        }

        try {
            $ast = $this->parser->parse($code);
            if ($ast === null) {
                return [];
            }
        } catch (\Exception $e) {
            // Ошибка парсинга - пропускаем файл
            return [];
        }

        $visitor = new CodeElementVisitor($filePath);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getElements();
    }
}

final class CodeElementVisitor extends NodeVisitorAbstract
{
    private string $filePath;
    private array $elements = [];
    private ?string $currentNamespace = null;
    private ?string $currentClass = null;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = $node->name ? $node->name->toString() : null;
        } elseif ($node instanceof Node\Stmt\Class_) {
            $this->currentClass = $node->name->toString();
            $this->elements[] = new CodeElement(
                $node->name->toString(),
                CodeElement::TYPE_CLASS,
                $this->filePath,
                $node->getStartLine(),
                $this->currentNamespace,
                null,
                $this->getModifiers($node)
            );
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $this->currentClass = $node->name->toString();
            $this->elements[] = new CodeElement(
                $node->name->toString(),
                CodeElement::TYPE_INTERFACE,
                $this->filePath,
                $node->getStartLine(),
                $this->currentNamespace
            );
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $this->currentClass = $node->name->toString();
            $this->elements[] = new CodeElement(
                $node->name->toString(),
                CodeElement::TYPE_TRAIT,
                $this->filePath,
                $node->getStartLine(),
                $this->currentNamespace
            );
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            $this->elements[] = new CodeElement(
                $node->name->toString(),
                CodeElement::TYPE_METHOD,
                $this->filePath,
                $node->getStartLine(),
                $this->currentNamespace,
                $this->currentClass,
                $this->getModifiers($node)
            );
        } elseif ($node instanceof Node\Stmt\Function_) {
            $this->elements[] = new CodeElement(
                $node->name->toString(),
                CodeElement::TYPE_FUNCTION,
                $this->filePath,
                $node->getStartLine(),
                $this->currentNamespace
            );
        } elseif ($node instanceof Node\Stmt\Property) {
            foreach ($node->props as $prop) {
                $this->elements[] = new CodeElement(
                    $prop->name->toString(),
                    CodeElement::TYPE_PROPERTY,
                    $this->filePath,
                    $node->getStartLine(),
                    $this->currentNamespace,
                    $this->currentClass,
                    $this->getModifiers($node)
                );
            }
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_ || 
            $node instanceof Node\Stmt\Interface_ || 
            $node instanceof Node\Stmt\Trait_) {
            $this->currentClass = null;
        }
    }

    /**
     * @return CodeElement[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    private function getModifiers($node): array
    {
        $modifiers = [];
        
        if (method_exists($node, 'isPublic') && $node->isPublic()) {
            $modifiers[] = 'public';
        }
        if (method_exists($node, 'isProtected') && $node->isProtected()) {
            $modifiers[] = 'protected';
        }
        if (method_exists($node, 'isPrivate') && $node->isPrivate()) {
            $modifiers[] = 'private';
        }
        if (method_exists($node, 'isStatic') && $node->isStatic()) {
            $modifiers[] = 'static';
        }
        if (method_exists($node, 'isAbstract') && $node->isAbstract()) {
            $modifiers[] = 'abstract';
        }
        if (method_exists($node, 'isFinal') && $node->isFinal()) {
            $modifiers[] = 'final';
        }

        return $modifiers;
    }
} 