<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Report;

use Anchor\DocsCoverage\Model\CodeElement;
use Anchor\DocsCoverage\Model\CoverageReport;
use Anchor\DocsCoverage\Model\DocumentationReference;

final class ReportGenerator
{
    public function generateConsoleReport(CoverageReport $report, float $minimumCoverage): string
    {
        $output = [];
        $output[] = "Documentation Coverage Report";
        $output[] = str_repeat("=", 50);
        $output[] = "";

        // Общая статистика
        $output[] = sprintf("Overall Coverage: %.1f%% (%d/%d elements)", 
            $report->getCoveragePercentage(),
            $report->getDocumentedElements(),
            $report->getTotalElements()
        );
        
        $output[] = sprintf("Minimum Required: %.1f%%", $minimumCoverage);
        $output[] = "";

        // Статистика по типам
        $output[] = "Coverage by Type:";
        $output[] = str_repeat("-", 30);
        
        $coverageByType = $report->getCoverageByType();
        foreach ($coverageByType as $type => $stats) {
            if ($stats['total'] > 0) {
                $output[] = sprintf("  %-12s: %.1f%% (%d/%d)", 
                    ucfirst($type), 
                    $stats['percentage'], 
                    $stats['documented'], 
                    $stats['total']
                );
            }
        }
        $output[] = "";

        // Недокументированные классы
        $undocumentedClasses = $report->getUndocumentedClasses();
        if (!empty($undocumentedClasses)) {
            $output[] = "Undocumented Classes:";
            $output[] = str_repeat("-", 30);
            
            $groupedByFile = $this->groupElementsByFile($undocumentedClasses);
            foreach ($groupedByFile as $file => $elements) {
                $output[] = "  " . $this->getRelativePath($file);
                foreach ($elements as $element) {
                    $output[] = sprintf("    Line %d: %s %s", 
                        $element->getLine(),
                        $element->getType(),
                        $element->getName()
                    );
                }
                $output[] = "";
            }
        }

        // Недокументированные элементы
        $undocumented = $report->getUndocumentedCodeElements();
        if (!empty($undocumented)) {
            $output[] = "Undocumented Elements:";
            $output[] = str_repeat("-", 30);
            
            $groupedByFile = $this->groupElementsByFile($undocumented);
            foreach ($groupedByFile as $file => $elements) {
                $output[] = "  " . $this->getRelativePath($file);
                foreach ($elements as $element) {
                    $output[] = sprintf("    Line %d: %s %s", 
                        $element->getLine(),
                        $element->getType(),
                        $element->getName()
                    );
                }
                $output[] = "";
            }
        }

        // Сломанные ссылки
        $brokenReferences = $report->getBrokenReferences();
        if (!empty($brokenReferences)) {
            $output[] = "Broken Documentation References:";
            $output[] = str_repeat("-", 35);
            
            foreach ($brokenReferences as $reference) {
                $output[] = sprintf("  %s:%d - %s '%s'", 
                    $this->getRelativePath($reference->getSourceFile()),
                    $reference->getSourceLine(),
                    $reference->getReferenceType(),
                    $reference->getReferencedElement()
                );
            }
            $output[] = "";
        }

        // Итоговый статус
        $isSuccessful = $report->isSuccessful($minimumCoverage);
        $output[] = $isSuccessful ? "✅ PASSED" : "❌ FAILED";
        
        if (!$isSuccessful) {
            $issues = [];
            if ($report->getCoveragePercentage() < $minimumCoverage) {
                $issues[] = sprintf("Coverage %.1f%% is below minimum %.1f%%", 
                    $report->getCoveragePercentage(), $minimumCoverage);
            }
            if ($report->hasBrokenReferences()) {
                $issues[] = sprintf("%d broken reference(s)", count($report->getBrokenReferences()));
            }
            $output[] = "Issues: " . implode(", ", $issues);
        }

        return implode("\n", $output);
    }

    public function generateJsonReport(CoverageReport $report): string
    {
        $data = [
            'summary' => [
                'coverage_percentage' => $report->getCoveragePercentage(),
                'total_elements' => $report->getTotalElements(),
                'documented_elements' => $report->getDocumentedElements(),
                'undocumented_elements' => $report->getUndocumentedElements(),
                'undocumented_classes' => count($report->getUndocumentedClasses()),
                'broken_references' => count($report->getBrokenReferences())
            ],
            'coverage_by_type' => $report->getCoverageByType(),
            'undocumented_classes' => array_map([$this, 'elementToArray'], $report->getUndocumentedClasses()),
            'undocumented_elements' => array_map([$this, 'elementToArray'], $report->getUndocumentedCodeElements()),
            'broken_references' => array_map([$this, 'referenceToArray'], $report->getBrokenReferences()),
            'all_elements' => array_map([$this, 'elementToArray'], $report->getCodeElements()),
            'all_references' => array_map([$this, 'referenceToArray'], $report->getDocumentationReferences())
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function generateHtmlReport(CoverageReport $report, float $minimumCoverage): string
    {
        $html = [];
        $html[] = '<!DOCTYPE html>';
        $html[] = '<html>';
        $html[] = '<head>';
        $html[] = '    <meta charset="UTF-8">';
        $html[] = '    <title>Documentation Coverage Report</title>';
        $html[] = '    <style>';
        $html[] = $this->getHtmlStyles();
        $html[] = '    </style>';
        $html[] = '</head>';
        $html[] = '<body>';
        $html[] = '    <div class="container">';
        $html[] = '        <h1>Documentation Coverage Report</h1>';

        // Общая статистика
        $percentage = $report->getCoveragePercentage();
        $statusClass = $percentage >= $minimumCoverage ? 'success' : 'error';
        
        $html[] = '        <div class="summary">';
        $html[] = sprintf('            <div class="coverage-badge %s">%.1f%%</div>', $statusClass, $percentage);
        $html[] = sprintf('            <p>%d out of %d elements documented</p>', 
            $report->getDocumentedElements(), $report->getTotalElements());
        $html[] = sprintf('            <p>Minimum required: %.1f%%</p>', $minimumCoverage);
        $html[] = '        </div>';

        // Статистика по типам
        $html[] = '        <h2>Coverage by Type</h2>';
        $html[] = '        <table class="coverage-table">';
        $html[] = '            <thead>';
        $html[] = '                <tr><th>Type</th><th>Coverage</th><th>Documented</th><th>Total</th></tr>';
        $html[] = '            </thead>';
        $html[] = '            <tbody>';
        
        foreach ($report->getCoverageByType() as $type => $stats) {
            if ($stats['total'] > 0) {
                $typeStatusClass = $stats['percentage'] >= 80 ? 'success' : ($stats['percentage'] >= 50 ? 'warning' : 'error');
                $html[] = sprintf('                <tr class="%s">', $typeStatusClass);
                $html[] = sprintf('                    <td>%s</td>', ucfirst($type));
                $html[] = sprintf('                    <td>%.1f%%</td>', $stats['percentage']);
                $html[] = sprintf('                    <td>%d</td>', $stats['documented']);
                $html[] = sprintf('                    <td>%d</td>', $stats['total']);
                $html[] = '                </tr>';
            }
        }
        
        $html[] = '            </tbody>';
        $html[] = '        </table>';

        // Недокументированные классы
        $undocumentedClasses = $report->getUndocumentedClasses();
        if (!empty($undocumentedClasses)) {
            $html[] = '        <h2>Undocumented Classes</h2>';
            $html[] = '        <div class="file-list">';
            
            $groupedByFile = $this->groupElementsByFile($undocumentedClasses);
            foreach ($groupedByFile as $file => $elements) {
                $html[] = sprintf('            <div class="file-section">');
                $html[] = sprintf('                <h3>%s</h3>', htmlspecialchars($this->getRelativePath($file)));
                $html[] = '                <ul>';
                foreach ($elements as $element) {
                    $html[] = sprintf('                    <li>Line %d: <code>%s %s</code></li>', 
                        $element->getLine(),
                        $element->getType(),
                        htmlspecialchars($element->getName())
                    );
                }
                $html[] = '                </ul>';
                $html[] = '            </div>';
            }
            
            $html[] = '        </div>';
        }

        // Недокументированные элементы
        $undocumented = $report->getUndocumentedCodeElements();
        if (!empty($undocumented)) {
            $html[] = '        <h2>Undocumented Elements</h2>';
            $html[] = '        <div class="file-list">';
            
            $groupedByFile = $this->groupElementsByFile($undocumented);
            foreach ($groupedByFile as $file => $elements) {
                $html[] = sprintf('            <div class="file-section">');
                $html[] = sprintf('                <h3>%s</h3>', htmlspecialchars($this->getRelativePath($file)));
                $html[] = '                <ul>';
                foreach ($elements as $element) {
                    $html[] = sprintf('                    <li>Line %d: <code>%s %s</code></li>', 
                        $element->getLine(),
                        $element->getType(),
                        htmlspecialchars($element->getName())
                    );
                }
                $html[] = '                </ul>';
                $html[] = '            </div>';
            }
            
            $html[] = '        </div>';
        }

        $html[] = '    </div>';
        $html[] = '</body>';
        $html[] = '</html>';

        return implode("\n", $html);
    }

    private function elementToArray(CodeElement $element): array
    {
        return [
            'name' => $element->getName(),
            'type' => $element->getType(),
            'file' => $this->getRelativePath($element->getFile()),
            'line' => $element->getLine(),
            'namespace' => $element->getNamespace(),
            'parent_class' => $element->getParentClass(),
            'modifiers' => $element->getModifiers(),
            'is_documented' => $element->isDocumented(),
            'fully_qualified_name' => $element->getFullyQualifiedName()
        ];
    }

    private function referenceToArray(DocumentationReference $reference): array
    {
        return [
            'source_file' => $this->getRelativePath($reference->getSourceFile()),
            'source_line' => $reference->getSourceLine(),
            'reference_type' => $reference->getReferenceType(),
            'referenced_element' => $reference->getReferencedElement(),
            'referenced_file' => $reference->getReferencedFile() ? $this->getRelativePath($reference->getReferencedFile()) : null,
            'referenced_line' => $reference->getReferencedLine(),
            'is_valid' => $reference->isValid()
        ];
    }

    private function groupElementsByFile(array $elements): array
    {
        $grouped = [];
        foreach ($elements as $element) {
            $grouped[$element->getFile()][] = $element;
        }
        return $grouped;
    }

    private function getRelativePath(string $fullPath): string
    {
        $cwd = getcwd();
        if ($cwd && strpos($fullPath, $cwd) === 0) {
            return substr($fullPath, strlen($cwd) + 1);
        }
        return $fullPath;
    }

    private function getHtmlStyles(): string
    {
        return '
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; }
        h2 { color: #555; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        h3 { color: #666; margin-top: 20px; }
        .summary { text-align: center; margin-bottom: 40px; padding: 20px; background: #f9f9f9; border-radius: 6px; }
        .coverage-badge { display: inline-block; font-size: 3em; font-weight: bold; padding: 20px 30px; border-radius: 50%; margin-bottom: 10px; }
        .coverage-badge.success { background: #4CAF50; color: white; }
        .coverage-badge.error { background: #f44336; color: white; }
        .coverage-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .coverage-table th, .coverage-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .coverage-table th { background-color: #f2f2f2; font-weight: bold; }
        .coverage-table tr.success { background-color: #e8f5e8; }
        .coverage-table tr.warning { background-color: #fff3cd; }
        .coverage-table tr.error { background-color: #f8d7da; }
        .file-section { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px; }
        .file-section ul { margin: 10px 0; padding-left: 20px; }
        .file-section li { margin: 5px 0; }
        code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
        ';
    }
} 