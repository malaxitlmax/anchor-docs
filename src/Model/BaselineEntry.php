<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Model;

/**
 * Представляет одну запись в baseline файле
 */
final class BaselineEntry
{
    private string $file;
    private int $line;
    private string $elementType;
    private string $elementName;
    private string $reason;
    private ?string $hash;

    public function __construct(
        string $file,
        int $line,
        string $elementType,
        string $elementName,
        string $reason = '',
        ?string $hash = null
    ) {
        $this->file = $file;
        $this->line = $line;
        $this->elementType = $elementType;
        $this->elementName = $elementName;
        $this->reason = $reason;
        $this->hash = $hash ?? $this->generateHash();
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getElementName(): string
    {
        return $this->elementName;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Проверяет, соответствует ли эта запись указанному элементу кода
     */
    public function matches(CodeElement $element): bool
    {
        return $this->file === $element->getFile() &&
               $this->line === $element->getLine() &&
               $this->elementType === $element->getType() &&
               $this->elementName === $element->getName();
    }

    /**
     * Создает BaselineEntry из CodeElement
     */
    public static function fromCodeElement(CodeElement $element, string $reason = ''): self
    {
        return new self(
            $element->getFile(),
            $element->getLine(),
            $element->getType(),
            $element->getName(),
            $reason
        );
    }

    /**
     * Создает BaselineEntry из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['file'],
            $data['line'],
            $data['element_type'],
            $data['element_name'],
            $data['reason'] ?? '',
            $data['hash'] ?? null
        );
    }

    /**
     * Преобразует в массив для сериализации
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'line' => $this->line,
            'element_type' => $this->elementType,
            'element_name' => $this->elementName,
            'reason' => $this->reason,
            'hash' => $this->hash,
        ];
    }

    /**
     * Генерирует уникальный хеш для записи
     */
    private function generateHash(): string
    {
        return md5($this->file . ':' . $this->line . ':' . $this->elementType . ':' . $this->elementName);
    }
} 