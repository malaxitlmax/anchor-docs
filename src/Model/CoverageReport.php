<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Model;

use Anchor\DocsCoverage\Config\Configuration;

final class CoverageReport
{
    /** @var CodeElement[] */
    private array $codeElements;
    
    /** @var DocumentationReference[] */
    private array $documentationReferences;
    
    private float $coveragePercentage;
    private int $totalElements;
    private int $documentedElements;
    private int $undocumentedElements;
    
    /** @var CodeElement[] */
    private array $undocumentedCodeElements;
    
    /** @var DocumentationReference[] */
    private array $brokenReferences;
    
    private Configuration $config;

    /**
     * @param CodeElement[] $codeElements
     * @param DocumentationReference[] $documentationReferences
     */
    public function __construct(array $codeElements, array $documentationReferences, Configuration $config)
    {
        $this->codeElements = $codeElements;
        $this->documentationReferences = $documentationReferences;
        $this->config = $config;
        $this->calculateCoverage();
    }

    private function calculateCoverage(): void
    {
        $documentableElements = array_filter($this->codeElements, fn(CodeElement $element) => $element->shouldBeDocumented());
        
        // Фильтруем элементы в зависимости от области покрытия
        if ($this->config->isClassesOnlyScope()) {
            $documentableElements = array_filter($documentableElements, function (CodeElement $element) {
                return in_array($element->getType(), [
                    CodeElement::TYPE_CLASS,
                    CodeElement::TYPE_INTERFACE,
                    CodeElement::TYPE_TRAIT
                ]);
            });
        }
        
        $this->totalElements = count($documentableElements);
        
        $documentedElements = array_filter($documentableElements, fn(CodeElement $element) => $element->isDocumented());
        $this->documentedElements = count($documentedElements);
        
        $this->undocumentedElements = $this->totalElements - $this->documentedElements;
        $this->undocumentedCodeElements = array_filter($documentableElements, fn(CodeElement $element) => !$element->isDocumented());
        
        $this->coveragePercentage = $this->totalElements > 0 
            ? ($this->documentedElements / $this->totalElements) * 100 
            : 100.0;
            
        $this->brokenReferences = array_filter($this->documentationReferences, fn(DocumentationReference $ref) => !$ref->isValid());
    }

    /** @return CodeElement[] */
    public function getCodeElements(): array
    {
        return $this->codeElements;
    }

    /** @return DocumentationReference[] */
    public function getDocumentationReferences(): array
    {
        return $this->documentationReferences;
    }

    public function getCoveragePercentage(): float
    {
        return $this->coveragePercentage;
    }

    public function getTotalElements(): int
    {
        return $this->totalElements;
    }

    public function getDocumentedElements(): int
    {
        return $this->documentedElements;
    }

    public function getUndocumentedElements(): int
    {
        return $this->undocumentedElements;
    }

    /** @return CodeElement[] */
    public function getUndocumentedCodeElements(): array
    {
        return $this->undocumentedCodeElements;
    }

    /** @return CodeElement[] */
    public function getUndocumentedClasses(): array
    {
        return array_filter($this->undocumentedCodeElements, function (CodeElement $element) {
            return in_array($element->getType(), [
                CodeElement::TYPE_CLASS,
                CodeElement::TYPE_INTERFACE,
                CodeElement::TYPE_TRAIT
            ]);
        });
    }

    /** @return DocumentationReference[] */
    public function getBrokenReferences(): array
    {
        return $this->brokenReferences;
    }

    public function hasBrokenReferences(): bool
    {
        return count($this->brokenReferences) > 0;
    }

    public function isSuccessful(float $minimumCoverage): bool
    {
        return $this->coveragePercentage >= $minimumCoverage; //&& !$this->hasBrokenReferences();
    }

    public function getCoverageByType(): array
    {
        $coverageByType = [];
        
        foreach ([CodeElement::TYPE_CLASS, CodeElement::TYPE_INTERFACE, CodeElement::TYPE_TRAIT, 
                 CodeElement::TYPE_METHOD, CodeElement::TYPE_FUNCTION, CodeElement::TYPE_PROPERTY] as $type) {
            $elements = array_filter($this->codeElements, fn(CodeElement $el) => $el->getType() === $type && $el->shouldBeDocumented());
            $documented = array_filter($elements, fn(CodeElement $el) => $el->isDocumented());
            
            $total = count($elements);
            $documentedCount = count($documented);
            
            $coverageByType[$type] = [
                'total' => $total,
                'documented' => $documentedCount,
                'undocumented' => $total - $documentedCount,
                'percentage' => $total > 0 ? ($documentedCount / $total) * 100 : 100.0
            ];
        }
        
        return $coverageByType;
    }
} 