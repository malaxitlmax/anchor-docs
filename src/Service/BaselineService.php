<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Service;

use Anchor\DocsCoverage\Model\BaselineEntry;
use Anchor\DocsCoverage\Model\CodeElement;
use Symfony\Component\Yaml\Yaml;

/**
 * Сервис для работы с baseline файлами
 */
final class BaselineService
{
    private const BASELINE_VERSION = '1.0';

    /**
     * Загружает baseline из файла
     * 
     * @return BaselineEntry[]
     */
    public function loadBaseline(string $baselineFile): array
    {
        if (!file_exists($baselineFile)) {
            return [];
        }

        $data = Yaml::parseFile($baselineFile);
        
        if (!is_array($data) || !isset($data['entries'])) {
            return [];
        }

        $entries = [];
        foreach ($data['entries'] as $entryData) {
            try {
                $entries[] = BaselineEntry::fromArray($entryData);
            } catch (\Exception $e) {
                // Игнорируем некорректные записи
                continue;
            }
        }

        return $entries;
    }

    /**
     * Сохраняет baseline в файл
     * 
     * @param BaselineEntry[] $entries
     */
    public function saveBaseline(string $baselineFile, array $entries): void
    {
        // Группируем записи по файлам для лучшей читаемости
        $groupedEntries = [];
        foreach ($entries as $entry) {
            $groupedEntries[$entry->getFile()][] = $entry->toArray();
        }

        // Сортируем файлы и записи внутри файлов
        ksort($groupedEntries);
        foreach ($groupedEntries as $file => &$fileEntries) {
            usort($fileEntries, fn($a, $b) => $a['line'] <=> $b['line']);
        }

        $data = [
            'version' => self::BASELINE_VERSION,
            'generated_at' => date('c'),
            'total_entries' => count($entries),
            'files' => $groupedEntries,
            'entries' => array_map(fn($entry) => $entry->toArray(), $entries)
        ];

        $yaml = $this->generateYamlWithComments($data, $groupedEntries);
        file_put_contents($baselineFile, $yaml);
    }

    /**
     * Фильтрует элементы кода, исключая те, что есть в baseline
     * 
     * @param CodeElement[] $codeElements
     * @param BaselineEntry[] $baselineEntries
     * @return CodeElement[]
     */
    public function filterCodeElementsByBaseline(array $codeElements, array $baselineEntries): array
    {
        if (empty($baselineEntries)) {
            return $codeElements;
        }

        // Создаем индекс baseline записей для быстрого поиска
        $baselineIndex = [];
        foreach ($baselineEntries as $entry) {
            $key = $this->createElementKey($entry->getFile(), $entry->getLine(), $entry->getElementType(), $entry->getElementName());
            $baselineIndex[$key] = $entry;
        }

        // Фильтруем элементы кода
        $filteredElements = [];
        foreach ($codeElements as $element) {
            $key = $this->createElementKey($element->getFile(), $element->getLine(), $element->getType(), $element->getName());
            
            // Если элемент не в baseline или уже задокументирован, оставляем его
            if (!isset($baselineIndex[$key]) || $element->isDocumented()) {
                $filteredElements[] = $element;
            }
        }

        return $filteredElements;
    }

    /**
     * Генерирует baseline из незадокументированных элементов
     * 
     * @param CodeElement[] $undocumentedElements
     * @param string $reason
     * @return BaselineEntry[]
     */
    public function generateBaselineFromUndocumentedElements(array $undocumentedElements, string $reason = 'Legacy code - needs documentation'): array
    {
        $entries = [];
        foreach ($undocumentedElements as $element) {
            if (!$element->isDocumented()) {
                $entries[] = BaselineEntry::fromCodeElement($element, $reason);
            }
        }

        // Сортируем по файлу и строке
        usort($entries, function (BaselineEntry $a, BaselineEntry $b) {
            $fileCompare = strcmp($a->getFile(), $b->getFile());
            if ($fileCompare !== 0) {
                return $fileCompare;
            }
            return $a->getLine() <=> $b->getLine();
        });

        return $entries;
    }

    /**
     * Проверяет актуальность baseline
     * 
     * @param BaselineEntry[] $baselineEntries
     * @param CodeElement[] $allCodeElements
     * @return array{valid: BaselineEntry[], invalid: BaselineEntry[]}
     */
    public function validateBaseline(array $baselineEntries, array $allCodeElements): array
    {
        $codeIndex = [];
        foreach ($allCodeElements as $element) {
            $key = $this->createElementKey($element->getFile(), $element->getLine(), $element->getType(), $element->getName());
            $codeIndex[$key] = $element;
        }

        $valid = [];
        $invalid = [];

        foreach ($baselineEntries as $entry) {
            $key = $this->createElementKey($entry->getFile(), $entry->getLine(), $entry->getElementType(), $entry->getElementName());
            
            if (isset($codeIndex[$key])) {
                $valid[] = $entry;
            } else {
                $invalid[] = $entry;
            }
        }

        return ['valid' => $valid, 'invalid' => $invalid];
    }

    /**
     * Обновляет baseline, удаляя недействительные записи
     * 
     * @param BaselineEntry[] $baselineEntries
     * @param CodeElement[] $allCodeElements
     * @return BaselineEntry[]
     */
    public function updateBaseline(array $baselineEntries, array $allCodeElements): array
    {
        $validation = $this->validateBaseline($baselineEntries, $allCodeElements);
        return $validation['valid'];
    }

    /**
     * Создает ключ для элемента кода
     */
    private function createElementKey(string $file, int $line, string $type, string $name): string
    {
        return $file . ':' . $line . ':' . $type . ':' . $name;
    }

    /**
     * Генерирует YAML с комментариями для лучшей читаемости
     */
    private function generateYamlWithComments(array $data, array $groupedEntries): string
    {
        $yaml = "# Anchor Documentation Coverage Analyzer - Baseline File\n";
        $yaml .= "# This file contains a list of elements that are excluded from documentation coverage checks\n";
        $yaml .= "# Generated on: " . $data['generated_at'] . "\n";
        $yaml .= "# Total entries: " . $data['total_entries'] . "\n\n";

        $yaml .= "version: '" . $data['version'] . "'\n";
        $yaml .= "generated_at: '" . $data['generated_at'] . "'\n";
        $yaml .= "total_entries: " . $data['total_entries'] . "\n\n";

        $yaml .= "# Files overview:\n";
        foreach ($groupedEntries as $file => $entries) {
            $yaml .= "# - $file (" . count($entries) . " entries)\n";
        }
        $yaml .= "\n";

        $yaml .= "entries:\n";
        foreach ($data['entries'] as $entry) {
            $yaml .= "  - file: '" . $entry['file'] . "'\n";
            $yaml .= "    line: " . $entry['line'] . "\n";
            $yaml .= "    element_type: '" . $entry['element_type'] . "'\n";
            $yaml .= "    element_name: '" . $entry['element_name'] . "'\n";
            if (!empty($entry['reason'])) {
                $yaml .= "    reason: '" . $entry['reason'] . "'\n";
            }
            $yaml .= "    hash: '" . $entry['hash'] . "'\n\n";
        }

        return $yaml;
    }
} 