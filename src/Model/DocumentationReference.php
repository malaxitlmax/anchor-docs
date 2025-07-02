<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Model;

final class DocumentationReference
{
    private string $sourceFile;
    private int $sourceLine;
    private string $referenceType;
    private string $referencedElement;
    private ?string $referencedFile;
    private ?int $referencedLine;
    private bool $isValid;

    public function __construct(
        string $sourceFile,
        int $sourceLine,
        string $referenceType,
        string $referencedElement,
        ?string $referencedFile = null,
        ?int $referencedLine = null,
        bool $isValid = false
    ) {
        $this->sourceFile = $sourceFile;
        $this->sourceLine = $sourceLine;
        $this->referenceType = $referenceType;
        $this->referencedElement = $referencedElement;
        $this->referencedFile = $referencedFile;
        $this->referencedLine = $referencedLine;
        $this->isValid = $isValid;
    }

    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    public function getSourceLine(): int
    {
        return $this->sourceLine;
    }

    public function getReferenceType(): string
    {
        return $this->referenceType;
    }

    public function getReferencedElement(): string
    {
        return $this->referencedElement;
    }

    public function getReferencedFile(): ?string
    {
        return $this->referencedFile;
    }

    public function getReferencedLine(): ?int
    {
        return $this->referencedLine;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function setValid(bool $valid): void
    {
        $this->isValid = $valid;
    }

    public function setReferencedLocation(?string $file, ?int $line): void
    {
        $this->referencedFile = $file;
        $this->referencedLine = $line;
    }
} 