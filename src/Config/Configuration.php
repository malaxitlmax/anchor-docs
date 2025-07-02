<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Config;

final class Configuration
{
    public const COVERAGE_SCOPE_CLASSES = 'classes';
    public const COVERAGE_SCOPE_ELEMENTS = 'elements';

    private string $projectRoot;
    private array $sourcePaths;
    private array $docsPaths;
    private array $excludePaths;
    private string $outputFormat;
    private string $outputFile;
    private float $minimumCoverage;
    private string $coverageScope;

    public function __construct(
        string $projectRoot,
        array $sourcePaths = ['src/'],
        array $docsPaths = ['docs/'],
        array $excludePaths = ['vendor/', 'tests/'],
        string $outputFormat = 'console',
        string $outputFile = '',
        float $minimumCoverage = 80.0,
        string $coverageScope = self::COVERAGE_SCOPE_ELEMENTS
    ) {
        $this->projectRoot = rtrim($projectRoot, '/');
        $this->sourcePaths = $sourcePaths;
        $this->docsPaths = $docsPaths;
        $this->excludePaths = $excludePaths;
        $this->outputFormat = $outputFormat;
        $this->outputFile = $outputFile;
        $this->minimumCoverage = $minimumCoverage;
        $this->coverageScope = $this->validateCoverageScope($coverageScope);
    }

    public static function fromArray(array $config): self
    {
        return new self(
            $config['project_root'] ?? getcwd(),
            $config['source_paths'] ?? ['src/'],
            $config['docs_paths'] ?? ['docs/'],
            $config['exclude_paths'] ?? ['vendor/', 'tests/'],
            $config['output_format'] ?? 'console',
            $config['output_file'] ?? '',
            $config['minimum_coverage'] ?? 80.0,
            $config['coverage_scope'] ?? self::COVERAGE_SCOPE_ELEMENTS
        );
    }

    public function getProjectRoot(): string
    {
        return $this->projectRoot;
    }

    public function getSourcePaths(): array
    {
        return array_map(fn($path) => $this->projectRoot . '/' . ltrim($path, '/'), $this->sourcePaths);
    }

    public function getDocsPaths(): array
    {
        return array_map(fn($path) => $this->projectRoot . '/' . ltrim($path, '/'), $this->docsPaths);
    }

    public function getExcludePaths(): array
    {
        return array_map(fn($path) => $this->projectRoot . '/' . ltrim($path, '/'), $this->excludePaths);
    }

    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }

    public function getOutputFile(): string
    {
        return $this->outputFile;
    }

    public function getMinimumCoverage(): float
    {
        return $this->minimumCoverage;
    }

    public function getCoverageScope(): string
    {
        return $this->coverageScope;
    }

    public function isClassesOnlyScope(): bool
    {
        return $this->coverageScope === self::COVERAGE_SCOPE_CLASSES;
    }

    private function validateCoverageScope(string $scope): string
    {
        if (!in_array($scope, [self::COVERAGE_SCOPE_CLASSES, self::COVERAGE_SCOPE_ELEMENTS])) {
            return self::COVERAGE_SCOPE_ELEMENTS;
        }
        return $scope;
    }
} 