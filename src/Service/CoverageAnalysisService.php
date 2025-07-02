<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Service;

use Anchor\DocsCoverage\Analyzer\CodeAnalyzer;
use Anchor\DocsCoverage\Analyzer\DocumentationAnalyzer;
use Anchor\DocsCoverage\Config\Configuration;
use Anchor\DocsCoverage\Model\CodeElement;
use Anchor\DocsCoverage\Model\CoverageReport;
use Anchor\DocsCoverage\Model\DocumentationReference;

final class CoverageAnalysisService
{
    private CodeAnalyzer $codeAnalyzer;
    private DocumentationAnalyzer $documentationAnalyzer;

    public function __construct(
        CodeAnalyzer $codeAnalyzer,
        DocumentationAnalyzer $documentationAnalyzer
    ) {
        $this->codeAnalyzer = $codeAnalyzer;
        $this->documentationAnalyzer = $documentationAnalyzer;
    }

    public function analyze(Configuration $config): CoverageReport
    {
        // Анализ исходного кода
        $codeElements = $this->codeAnalyzer->analyze(
            $config->getSourcePaths(),
            $config->getExcludePaths()
        );

        // Анализ документации
        $documentationReferences = $this->documentationAnalyzer->analyze(
            $config->getDocsPaths()
        );

        // Сопоставление документации с кодом
        $this->matchDocumentationToCode($codeElements, $documentationReferences, $config);

        return new CoverageReport($codeElements, $documentationReferences, $config);
    }

    /**
     * Сопоставляет ссылки в документации с элементами кода
     * 
     * @param CodeElement[] $codeElements
     * @param DocumentationReference[] $documentationReferences
     */
    private function matchDocumentationToCode(
        array $codeElements, 
        array $documentationReferences, 
        Configuration $config
    ): void {
        // Создаем индекс элементов кода для быстрого поиска
        $codeIndex = $this->buildCodeIndex($codeElements);

        foreach ($documentationReferences as $reference) {
            $referencedElement = $reference->getReferencedElement();
            $referenceType = $reference->getReferenceType();

            if ($referenceType === 'link') {
                // Парсим ссылку на файл и строку
                $linkInfo = $this->documentationAnalyzer->parseCodeReference($referencedElement);
                if ($linkInfo['file']) {
                    $fullPath = $this->resolveFilePath($linkInfo['file'], $config->getProjectRoot());
                    if ($fullPath && file_exists($fullPath)) {
                        $reference->setReferencedLocation($fullPath, $linkInfo['line']);
                        $reference->setValid(true);

                        // Помечаем элементы в этом файле как задокументированные
                        $this->markFileElementsAsDocumented($codeElements, $fullPath, $linkInfo['line']);
                    }
                }
            } else {
                // Поиск элемента по имени
                if (isset($codeIndex[$referenceType][$referencedElement])) {
                    $matchedElements = $codeIndex[$referenceType][$referencedElement];
                    
                    foreach ($matchedElements as $element) {
                        $element->setDocumented(true);
                        $reference->setReferencedLocation($element->getFile(), $element->getLine());
                        $reference->setValid(true);
                    }
                }
            }
        }
    }

    /**
     * Создает индекс элементов кода для быстрого поиска
     * 
     * @param CodeElement[] $codeElements
     */
    private function buildCodeIndex(array $codeElements): array
    {
        $index = [];

        foreach ($codeElements as $element) {
            $type = $element->getType();
            $name = $element->getName();
            $fullName = $element->getFullyQualifiedName();

            // Индексируем по типу и имени
            $index[$type][$name][] = $element;
            
            // Также индексируем по полному имени
            $index[$type][$fullName][] = $element;

            // Для методов и свойств индексируем без класса
            if (in_array($type, [CodeElement::TYPE_METHOD, CodeElement::TYPE_PROPERTY])) {
                $index[$type][$name][] = $element;
            }
        }

        return $index;
    }

    /**
     * Помечает элементы в указанном файле как задокументированные
     * 
     * @param CodeElement[] $codeElements
     */
    private function markFileElementsAsDocumented(array $codeElements, string $filePath, ?int $lineNumber = null): void
    {
        foreach ($codeElements as $element) {
            if ($element->getFile() === $filePath) {
                // Если указана строка, помечаем только близлежащие элементы
                if ($lineNumber !== null) {
                    $elementLine = $element->getLine();
                    // Допускаем погрешность в 10 строк
                    if (abs($elementLine - $lineNumber) <= 10) {
                        $element->setDocumented(true);
                    }
                } else {
                    // Если строка не указана, помечаем весь файл
                    $element->setDocumented(true);
                }
            }
        }
    }

    /**
     * Разрешает относительный путь к файлу
     */
    private function resolveFilePath(string $relativePath, string $projectRoot): ?string
    {
        // Удаляем ведущие ./ и ../
        $relativePath = ltrim($relativePath, './');
        
        // Попробуем несколько вариантов
        $candidates = [
            $projectRoot . '/' . $relativePath,
            $projectRoot . '/src/' . $relativePath,
            $relativePath
        ];

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);
            if ($realPath && file_exists($realPath)) {
                return $realPath;
            }
        }

        return null;
    }
} 