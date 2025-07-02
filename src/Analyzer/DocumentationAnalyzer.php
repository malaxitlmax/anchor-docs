<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Analyzer;

use Anchor\DocsCoverage\Model\DocumentationReference;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\MarkdownParser;
use Symfony\Component\Finder\Finder;

final class DocumentationAnalyzer
{
    private MarkdownParser $parser;

    public function __construct()
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $this->parser = new MarkdownParser($environment);
    }

    /**
     * @param string[] $docsPaths
     * @return DocumentationReference[]
     */
    public function analyze(array $docsPaths): array
    {
        $references = [];
        
        foreach ($docsPaths as $docsPath) {
            if (!file_exists($docsPath)) {
                continue;
            }
            
            $finder = new Finder();
            $finder->files()
                ->in($docsPath)
                ->name('*.md')
                ->name('*.markdown');
            
            foreach ($finder as $file) {
                $fileReferences = $this->analyzeFile($file->getRealPath());
                $references = array_merge($references, $fileReferences);
            }
        }
        
        return $references;
    }

    /**
     * @return DocumentationReference[]
     */
    private function analyzeFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $references = [];
        
        // Сначала находим все полные ссылки на классы в специальных блоках
        $classReferences = $this->findFullyQualifiedClassReferences($filePath, $content);
        $references = array_merge($references, $classReferences);
        
        // Извлекаем имена классов из найденных ссылок для дальнейшей проверки
        $documentedClasses = $this->extractDocumentedClasses($classReferences);
        
        // Теперь ищем ссылки на методы и свойства, но только для задокументированных классов
        $memberReferences = $this->findClassMemberReferences($filePath, $content, $documentedClasses);
        $references = array_merge($references, $memberReferences);
        
        // Поиск ссылок в markdown-ссылках
        $references = array_merge($references, $this->findMarkdownLinks($filePath, $content));
        
        return $references;
    }

    /**
     * Находит только полные ссылки на классы с пространствами имен
     * @return DocumentationReference[]
     */
    private function findFullyQualifiedClassReferences(string $filePath, string $content): array
    {
        $references = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            // Поиск полных ссылок на классы в формате \Namespace\ClassName или Namespace\ClassName
            if (preg_match_all('/\\\\?([A-Z][a-zA-Z0-9_]*(?:\\\\[A-Z][a-zA-Z0-9_]*)+)/', $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $match) {
                    $className = $match[0];
                    
                    // Проверяем, что это не исключение (общие акронимы)
                    if (!$this->isCommonAcronym($className)) {
                        $references[] = new DocumentationReference(
                            $filePath,
                            $lineNumber + 1,
                            'class',
                            $className
                        );
                    }
                }
            }
        }
        
        return $references;
    }

    /**
     * Извлекает имена классов из найденных ссылок для дальнейшего использования
     * @param DocumentationReference[] $classReferences
     * @return array
     */
    private function extractDocumentedClasses(array $classReferences): array
    {
        $classes = [];
        
        foreach ($classReferences as $reference) {
            if ($reference->getReferenceType() === 'class') {
                $fullClassName = $reference->getReferencedElement();
                
                // Добавляем полное имя класса
                $classes[] = $fullClassName;
                
                // Также добавляем короткое имя (последний элемент после \)
                $parts = explode('\\', $fullClassName);
                $shortName = end($parts);
                $classes[] = $shortName;
            }
        }
        
        return array_unique($classes);
    }

    /**
     * Находит ссылки на методы и свойства классов, но только для задокументированных классов
     * @return DocumentationReference[]
     */
    private function findClassMemberReferences(string $filePath, string $content, array $documentedClasses): array
    {
        $references = [];
        $lines = explode("\n", $content);
        $inCodeBlock = false;
        
        foreach ($lines as $lineNumber => $line) {
            // Проверяем, находимся ли мы в блоке кода
            if (preg_match('/^```/', $line)) {
                $inCodeBlock = !$inCodeBlock;
                continue;
            }
            
            // Пропускаем анализ внутри блоков кода
            if ($inCodeBlock) {
                continue;
            }
            
            // Поиск упоминаний методов (->method(), ::method(), ClassName::method())
            if (preg_match_all('/(?:([A-Z][a-zA-Z0-9_]*)::[a-zA-Z_][a-zA-Z0-9_]*\s*\(|(?:->|::)([a-zA-Z_][a-zA-Z0-9_]*)\s*\()/', $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $i => $match) {
                    $fullMatch = $match[0];
                    
                    // Статический вызов ClassName::method()
                    if (preg_match('/([A-Z][a-zA-Z0-9_]*)::[a-zA-Z_][a-zA-Z0-9_]*\s*\(/', $fullMatch, $staticMatch)) {
                        $className = $staticMatch[1];
                        if (in_array($className, $documentedClasses)) {
                            preg_match('/::([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $fullMatch, $methodMatch);
                            if (isset($methodMatch[1])) {
                                $methodName = $methodMatch[1];
                                
                                $references[] = new DocumentationReference(
                                    $filePath,
                                    $lineNumber + 1,
                                    'method',
                                    $methodName
                                );
                            }
                        }
                    } 
                    // Обычный вызов метода ->method() или ::method()
                    elseif (preg_match('/(?:->|::)([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $fullMatch, $methodMatch)) {
                        $methodName = $methodMatch[1];
                        
                        // Исключаем общие методы
                        if (!$this->isCommonMethod($methodName)) {
                            // Проверяем, что в контексте есть упоминание задокументированного класса
                            if ($this->isMethodInDocumentedContext($line, $documentedClasses) || 
                                $this->hasDocumentedClassInNearbyLines($lines, $lineNumber, $documentedClasses)) {
                                
                                $references[] = new DocumentationReference(
                                    $filePath,
                                    $lineNumber + 1,
                                    'method',
                                    $methodName
                                );
                            }
                        }
                    }
                }
            }
            
            // Поиск свойств и переменных классов (но не в PHP коде)
            if (!preg_match('/^\s*(?:\$|\/\/|\/\*|\*|#)/', $line)) {
                if (preg_match_all('/(?:->|::)(\$?[a-zA-Z_][a-zA-Z0-9_]*)(?!\s*\()/', $line, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[1] as $match) {
                        $propertyName = $match[0];
                        
                        // Исключаем общие переменные и проверяем контекст
                        if (!$this->isCommonVariable($propertyName) && 
                            ($this->isMethodInDocumentedContext($line, $documentedClasses) || 
                             $this->hasDocumentedClassInNearbyLines($lines, $lineNumber, $documentedClasses))) {
                            
                            $references[] = new DocumentationReference(
                                $filePath,
                                $lineNumber + 1,
                                'property',
                                $propertyName
                            );
                        }
                    }
                }
            }
        }
        
        return $references;
    }

    /**
     * Проверяет, является ли строка общим акронимом, который не должен считаться классом
     */
    private function isCommonAcronym(string $className): bool
    {
        $commonAcronyms = [
            'PHP', 'HTML', 'JSON', 'XML', 'API', 'CLI', 'URL', 'HTTP', 'HTTPS', 
            'CSS', 'JS', 'SQL', 'CI', 'CD', 'CRUD', 'UUID', 'UTF', 'ASCII'
        ];
        
        $parts = explode('\\', $className);
        $lastPart = end($parts);
        
        return in_array($lastPart, $commonAcronyms);
    }

    /**
     * Проверяет, является ли переменная общей системной переменной
     */
    private function isCommonVariable(string $variableName): bool
    {
        $commonVariables = [
            '$this', '$self', '$static', '$parent', '$id', '$name', '$data', '$config',
            '$request', '$response', '$session', '$user', '$item', '$value', '$key'
        ];
        
        return in_array($variableName, $commonVariables);
    }

    /**
     * Проверяет, является ли метод общим системным методом
     */
    private function isCommonMethod(string $methodName): bool
    {
        $commonMethods = [
            'get', 'set', 'has', 'is', 'add', 'remove', 'create', 'delete', 'update',
            'find', 'save', 'load', 'run', 'execute', 'call', 'apply', 'bind', 'clone',
            '__construct', '__destruct', '__get', '__set', '__call', '__toString'
        ];
        
        return in_array($methodName, $commonMethods);
    }

    /**
     * Проверяет, есть ли в строке контекст задокументированного класса
     */
    private function isMethodInDocumentedContext(string $line, array $documentedClasses): bool
    {
        foreach ($documentedClasses as $className) {
            if (strpos($line, $className) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверяет, есть ли упоминание задокументированного класса в соседних строках
     */
    private function hasDocumentedClassInNearbyLines(array $lines, int $currentLine, array $documentedClasses): bool
    {
        $checkRange = 3; // Проверяем 3 строки до и после
        
        $startLine = max(0, $currentLine - $checkRange);
        $endLine = min(count($lines) - 1, $currentLine + $checkRange);
        
        for ($i = $startLine; $i <= $endLine; $i++) {
            if ($i === $currentLine) continue;
            
            foreach ($documentedClasses as $className) {
                if (strpos($lines[$i], $className) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * @return DocumentationReference[]
     */
    private function findMarkdownLinks(string $filePath, string $content): array
    {
        $references = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            // Поиск markdown ссылок [text](url)
            if (preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $line, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $url = $match[2];
                    
                    // Проверяем, является ли ссылка ссылкой на исходный код
                    if ($this->isCodeReference($url)) {
                        $references[] = new DocumentationReference(
                            $filePath,
                            $lineNumber + 1,
                            'link',
                            $url
                        );
                    }
                }
            }
        }
        
        return $references;
    }

    private function isCodeReference(string $url): bool
    {
        // Ссылки на PHP файлы
        if (preg_match('/\.php(?:#.*)?$/', $url)) {
            return true;
        }
        
        // Ссылки на GitHub/GitLab с указанием строк
        if (preg_match('/github\.com|gitlab\.com.*#L\d+/', $url)) {
            return true;
        }
        
        // Relative пути к исходникам
        if (preg_match('/^(?:\.\.?\/)*src\/.*\.php/', $url)) {
            return true;
        }
        
        return false;
    }

    /**
     * Извлекает информацию о файле и строке из ссылки
     */
    public function parseCodeReference(string $reference): array
    {
        $result = ['file' => null, 'line' => null, 'element' => null];
        
        // Парсинг ссылок вида src/Class.php#L123
        if (preg_match('/^(.+\.php)(?:#L(\d+))?$/', $reference, $matches)) {
            $result['file'] = $matches[1];
            if (isset($matches[2])) {
                $result['line'] = (int)$matches[2];
            }
        }
        
        return $result;
    }
} 