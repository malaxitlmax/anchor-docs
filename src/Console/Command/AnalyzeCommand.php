<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Console\Command;

use Anchor\DocsCoverage\Analyzer\CodeAnalyzer;
use Anchor\DocsCoverage\Analyzer\DocumentationAnalyzer;
use Anchor\DocsCoverage\Config\Configuration;
use Anchor\DocsCoverage\Report\ReportGenerator;
use Anchor\DocsCoverage\Service\CoverageAnalysisService;
use Anchor\DocsCoverage\Service\BaselineService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'analyze',
    description: 'Analyze documentation coverage for PHP code'
)]
final class AnalyzeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::OPTIONAL, 'Project path to analyze', '.')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file path', 'anchor-docs.yml')
            ->addOption('source', 's', InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Source directories', ['src/'])
            ->addOption('docs', 'd', InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Documentation directories', ['docs/'])
            ->addOption('exclude', 'e', InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Exclude directories', ['vendor/', 'tests/'])
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format (console, json, html)', 'console')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file (for json/html formats)')
            ->addOption('min-coverage', 'm', InputOption::VALUE_REQUIRED, 'Minimum coverage percentage')
            ->addOption('coverage-scope', null, InputOption::VALUE_REQUIRED, 'Coverage scope (classes, elements)')
            ->addOption('baseline', 'b', InputOption::VALUE_REQUIRED, 'Baseline file path')
            ->addOption('generate-baseline', null, InputOption::VALUE_NONE, 'Generate baseline file from current undocumented elements')
            ->addOption('update-baseline', null, InputOption::VALUE_NONE, 'Update existing baseline file (remove invalid entries)')
            ->setHelp(
                <<<'HELP'
The <info>analyze</info> command analyzes your PHP code for documentation coverage.

<info>php bin/anchor-docs analyze</info>

Use a configuration file:
<info>php bin/anchor-docs analyze --config=my-config.yml</info>

Specify custom paths:
<info>php bin/anchor-docs analyze --source=app/ --docs=documentation/</info>

Generate HTML report:
<info>php bin/anchor-docs analyze --format=html --output=coverage-report.html</info>

Focus coverage on classes only:
<info>php bin/anchor-docs analyze --coverage-scope=classes</info>

Generate baseline from current state:
<info>php bin/anchor-docs analyze --generate-baseline --baseline=docs-baseline.yml</info>

Use existing baseline:
<info>php bin/anchor-docs analyze --baseline=docs-baseline.yml</info>

Update baseline file:
<info>php bin/anchor-docs analyze --update-baseline --baseline=docs-baseline.yml</info>
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $projectPath = $input->getArgument('path');
        $configFile = $input->getOption('config');
        
        // Загрузка конфигурации
        $config = $this->loadConfiguration($projectPath, $configFile, $input);
        
        $io->title('Anchor Documentation Coverage Analyzer');
        $io->note('Analyzing project: ' . $config->getProjectRoot());

        // Создание сервисов
        $codeAnalyzer = new CodeAnalyzer();
        $documentationAnalyzer = new DocumentationAnalyzer();
        $baselineService = new BaselineService();
        $analysisService = new CoverageAnalysisService($codeAnalyzer, $documentationAnalyzer, $baselineService);
        $reportGenerator = new ReportGenerator();

        try {
            // Обработка baseline команд
            if ($input->getOption('generate-baseline')) {
                return $this->handleGenerateBaseline($input, $io, $config, $codeAnalyzer, $documentationAnalyzer, $baselineService);
            }

            if ($input->getOption('update-baseline')) {
                return $this->handleUpdateBaseline($input, $io, $config, $codeAnalyzer, $baselineService);
            }

            // Выполнение анализа
            $io->section('Analyzing source code...');
            $report = $analysisService->analyze($config);
            
            $io->section('Generating report...');
            
            // Генерация отчета
            $format = $input->getOption('format');
            $outputFile = $input->getOption('output');
            
            switch ($format) {
                case 'json':
                    $content = $reportGenerator->generateJsonReport($report);
                    break;
                case 'html':
                    $content = $reportGenerator->generateHtmlReport($report, $config->getMinimumCoverage());
                    break;
                case 'console':
                default:
                    $content = $reportGenerator->generateConsoleReport($report, $config->getMinimumCoverage());
                    break;
            }

            // Вывод или сохранение отчета
            if ($outputFile) {
                file_put_contents($outputFile, $content);
                $io->success("Report saved to: $outputFile");
            } else {
                if ($format === 'console') {
                    $output->writeln($content);
                } else {
                    $io->error('Output file is required for non-console formats');
                    return Command::FAILURE;
                }
            }

            // Проверка результата
            $isSuccessful = $report->isSuccessful($config->getMinimumCoverage());
            
            if ($isSuccessful) {
                $io->success(sprintf(
                    'Documentation coverage check passed! Coverage: %.1f%%',
                    $report->getCoveragePercentage()
                ));
                return Command::SUCCESS;
            } else {
                $io->error(sprintf(
                    'Documentation coverage check failed! Coverage: %.1f%% (minimum: %.1f%%)',
                    $report->getCoveragePercentage(),
                    $config->getMinimumCoverage()
                ));
                
                if ($report->hasBrokenReferences()) {
                    $io->warning(sprintf(
                        'Found %d broken documentation reference(s)',
                        count($report->getBrokenReferences())
                    ));
                }
                
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('Analysis failed: ' . $e->getMessage());
            
            if ($output->isVerbose()) {
                $io->listing(explode("\n", $e->getTraceAsString()));
            }
            
            return Command::FAILURE;
        }
    }

    private function loadConfiguration(string $projectPath, string $configFile, InputInterface $input): Configuration
    {
        $configPath = $projectPath . '/' . $configFile;
        $configData = [];

        // Загружаем конфигурацию из файла, если он существует
        if (file_exists($configPath)) {
            $configData = Yaml::parseFile($configPath);
        }

        // Переопределяем параметры из командной строки только если они были явно переданы
        if ($input->getOption('source')) {
            $configData['source_paths'] = $input->getOption('source');
        }
        if ($input->getOption('docs')) {
            $configData['docs_paths'] = $input->getOption('docs');
        }
        if ($input->getOption('exclude')) {
            $configData['exclude_paths'] = $input->getOption('exclude');
        }
        if ($input->getOption('format')) {
            $configData['output_format'] = $input->getOption('format');
        }
        if ($input->getOption('output')) {
            $configData['output_file'] = $input->getOption('output');
        }
        // Проверяем, была ли опция min-coverage передана пользователем
        if ($input->hasParameterOption(['--min-coverage', '-m'])) {
            $configData['minimum_coverage'] = (float) $input->getOption('min-coverage');
        }
        // Проверяем, была ли опция coverage-scope передана пользователем
        if ($input->hasParameterOption(['--coverage-scope'])) {
            $configData['coverage_scope'] = $input->getOption('coverage-scope');
        }
        // Проверяем baseline опцию
        if ($input->getOption('baseline')) {
            $configData['baseline_file'] = $input->getOption('baseline');
        }

        $configData['project_root'] = realpath($projectPath) ?: $projectPath;

        return Configuration::fromArray($configData);
    }

    private function handleGenerateBaseline(
        InputInterface $input,
        SymfonyStyle $io,
        Configuration $config,
        CodeAnalyzer $codeAnalyzer,
        DocumentationAnalyzer $documentationAnalyzer,
        BaselineService $baselineService
    ): int {
        $baselineFile = $input->getOption('baseline');
        if (!$baselineFile) {
            $io->error('Baseline file path is required when using --generate-baseline');
            return Command::FAILURE;
        }

        $io->title('Generating Baseline File');
        $io->note("Analyzing current state to generate baseline: $baselineFile");

        // Анализируем код без baseline
        $codeElements = $codeAnalyzer->analyze(
            $config->getSourcePaths(),
            $config->getExcludePaths()
        );

        $documentationReferences = $documentationAnalyzer->analyze(
            $config->getDocsPaths()
        );

        // Временно создаем службу анализа без baseline для получения текущего состояния
        $tempService = new CoverageAnalysisService($codeAnalyzer, $documentationAnalyzer);
        $tempConfig = new Configuration(
            $config->getProjectRoot(),
            array_map(fn($path) => str_replace($config->getProjectRoot() . '/', '', $path), $config->getSourcePaths()),
            array_map(fn($path) => str_replace($config->getProjectRoot() . '/', '', $path), $config->getDocsPaths()),
            array_map(fn($path) => str_replace($config->getProjectRoot() . '/', '', $path), $config->getExcludePaths()),
            $config->getOutputFormat(),
            $config->getOutputFile(),
            $config->getMinimumCoverage(),
            $config->getCoverageScope()
        );

        // Сопоставляем документацию с кодом
        $this->matchDocumentationToCodeForBaseline($codeElements, $documentationReferences, $tempConfig);

        // Находим незадокументированные элементы
        $undocumentedElements = array_filter($codeElements, fn($element) => !$element->isDocumented());

        if (empty($undocumentedElements)) {
            $io->success('All elements are documented! No baseline needed.');
            return Command::SUCCESS;
        }

        // Генерируем baseline
        $reason = 'Legacy code - documentation needed';
        $baselineEntries = $baselineService->generateBaselineFromUndocumentedElements($undocumentedElements, $reason);

        // Сохраняем baseline
        $baselineService->saveBaseline($baselineFile, $baselineEntries);

        $io->success([
            "Baseline generated successfully: $baselineFile",
            "Total entries: " . count($baselineEntries),
            "Files covered: " . count(array_unique(array_map(fn($entry) => $entry->getFile(), $baselineEntries)))
        ]);

        return Command::SUCCESS;
    }

    private function handleUpdateBaseline(
        InputInterface $input,
        SymfonyStyle $io,
        Configuration $config,
        CodeAnalyzer $codeAnalyzer,
        BaselineService $baselineService
    ): int {
        $baselineFile = $input->getOption('baseline');
        if (!$baselineFile) {
            $io->error('Baseline file path is required when using --update-baseline');
            return Command::FAILURE;
        }

        if (!file_exists($baselineFile)) {
            $io->error("Baseline file not found: $baselineFile");
            return Command::FAILURE;
        }

        $io->title('Updating Baseline File');
        $io->note("Validating and updating baseline: $baselineFile");

        // Загружаем существующий baseline
        $existingEntries = $baselineService->loadBaseline($baselineFile);
        $originalCount = count($existingEntries);

        // Анализируем текущий код
        $codeElements = $codeAnalyzer->analyze(
            $config->getSourcePaths(),
            $config->getExcludePaths()
        );

        // Обновляем baseline (удаляем недействительные записи)
        $updatedEntries = $baselineService->updateBaseline($existingEntries, $codeElements);
        $updatedCount = count($updatedEntries);
        $removedCount = $originalCount - $updatedCount;

        // Сохраняем обновленный baseline
        $baselineService->saveBaseline($baselineFile, $updatedEntries);

        if ($removedCount > 0) {
            $io->warning([
                "Removed $removedCount invalid entries from baseline",
                "Remaining entries: $updatedCount"
            ]);
        } else {
            $io->success("Baseline is up to date. No changes needed.");
        }

        return Command::SUCCESS;
    }

    /**
     * Временный метод для сопоставления документации с кодом при генерации baseline
     */
    private function matchDocumentationToCodeForBaseline(
        array $codeElements,
        array $documentationReferences,
        Configuration $config
    ): void {
        // Создаем временный сервис для анализа
        $tempService = new CoverageAnalysisService(
            new CodeAnalyzer(),
            new DocumentationAnalyzer()
        );

        // Используем reflection для доступа к приватному методу
        $reflection = new \ReflectionClass($tempService);
        $method = $reflection->getMethod('matchDocumentationToCode');
        $method->setAccessible(true);
        $method->invoke($tempService, $codeElements, $documentationReferences, $config);
    }
} 