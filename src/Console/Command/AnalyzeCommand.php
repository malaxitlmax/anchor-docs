<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Console\Command;

use Anchor\DocsCoverage\Analyzer\CodeAnalyzer;
use Anchor\DocsCoverage\Analyzer\DocumentationAnalyzer;
use Anchor\DocsCoverage\Config\Configuration;
use Anchor\DocsCoverage\Report\ReportGenerator;
use Anchor\DocsCoverage\Service\CoverageAnalysisService;
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
        $analysisService = new CoverageAnalysisService($codeAnalyzer, $documentationAnalyzer);
        $reportGenerator = new ReportGenerator();

        try {
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

        $configData['project_root'] = realpath($projectPath) ?: $projectPath;

        return Configuration::fromArray($configData);
    }
} 