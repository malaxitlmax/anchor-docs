<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Model;

final class CodeElement
{
    public const TYPE_CLASS = 'class';
    public const TYPE_INTERFACE = 'interface';
    public const TYPE_TRAIT = 'trait';
    public const TYPE_METHOD = 'method';
    public const TYPE_FUNCTION = 'function';
    public const TYPE_PROPERTY = 'property';
    public const TYPE_CONSTANT = 'constant';

    private string $name;
    private string $type;
    private string $file;
    private int $line;
    private ?string $namespace;
    private ?string $parentClass;
    private array $modifiers;
    private bool $isDocumented;

    public function __construct(
        string $name,
        string $type,
        string $file,
        int $line,
        ?string $namespace = null,
        ?string $parentClass = null,
        array $modifiers = [],
        bool $isDocumented = false
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->file = $file;
        $this->line = $line;
        $this->namespace = $namespace;
        $this->parentClass = $parentClass;
        $this->modifiers = $modifiers;
        $this->isDocumented = $isDocumented;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function getParentClass(): ?string
    {
        return $this->parentClass;
    }

    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    public function isDocumented(): bool
    {
        return $this->isDocumented;
    }

    public function setDocumented(bool $documented): void
    {
        $this->isDocumented = $documented;
    }

    public function getFullyQualifiedName(): string
    {
        $fqn = $this->namespace ? $this->namespace . '\\' : '';
        
        if ($this->parentClass && in_array($this->type, [self::TYPE_METHOD, self::TYPE_PROPERTY, self::TYPE_CONSTANT])) {
            $fqn .= $this->parentClass . '::' . $this->name;
        } else {
            $fqn .= $this->name;
        }

        return $fqn;
    }

    public function isPublic(): bool
    {
        return in_array('public', $this->modifiers) || empty($this->modifiers);
    }

    public function isPrivate(): bool
    {
        return in_array('private', $this->modifiers);
    }

    public function isProtected(): bool
    {
        return in_array('protected', $this->modifiers);
    }

    public function shouldBeDocumented(): bool
    {
        // Классы, интерфейсы и трейты всегда должны быть задокументированы
        if (in_array($this->type, [self::TYPE_CLASS, self::TYPE_INTERFACE, self::TYPE_TRAIT])) {
            return true;
        }
        
        // Приватные элементы могут не требовать документации
        return $this->isPublic() || $this->isProtected();
    }
} 