# Примеры использования

## Базовые команды

### Анализ с настройками по умолчанию
```bash
php bin/anchor-docs analyze
```

### Анализ с указанием путей
```bash
php bin/anchor-docs analyze --source=app/src/ --docs=documentation/
```

### Установка минимального покрытия
```bash
php bin/anchor-docs analyze --min-coverage=95
```

## Генерация отчетов

### HTML отчет
```bash
php bin/anchor-docs analyze --format=html --output=docs-coverage.html
```

### JSON отчет для интеграции
```bash
php bin/anchor-docs analyze --format=json --output=coverage.json
```

## Конфигурационные файлы

### Минимальная конфигурация
```yaml
# anchor-docs.yml
source_paths:
  - "src/"
docs_paths:
  - "docs/"
minimum_coverage: 80.0
```

### Расширенная конфигурация
```yaml
# anchor-docs.yml
source_paths:
  - "src/"
  - "lib/"
docs_paths:
  - "docs/"
  - "documentation/"
exclude_paths:
  - "vendor/"
  - "tests/"
  - "build/"
minimum_coverage: 85.0
output_format: "console"
settings:
  include_private: false
  include_getters_setters: true
```

## Интеграция в CI/CD

### GitHub Actions
```yaml
name: Documentation Coverage

on: [push, pull_request]

jobs:
  docs-coverage:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          
      - name: Install dependencies
        run: composer install
        
      - name: Check documentation coverage
        run: php bin/anchor-docs analyze --min-coverage=80
        
      - name: Generate HTML report
        run: php bin/anchor-docs analyze --format=html --output=coverage-report.html
        
      - name: Upload coverage report
        uses: actions/upload-artifact@v3
        with:
          name: documentation-coverage
          path: coverage-report.html
```

### GitLab CI
```yaml
# .gitlab-ci.yml
stages:
  - test

documentation_coverage:
  stage: test
  image: php:8.1
  before_script:
    - apt-get update -qq && apt-get install -y git unzip
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install
  script:
    - php bin/anchor-docs analyze --min-coverage=80 --format=json --output=coverage.json
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: coverage.json
    paths:
      - coverage.json
    expire_in: 1 week
```

### Jenkins Pipeline
```groovy
pipeline {
    agent any
    
    stages {
        stage('Install') {
            steps {
                sh 'composer install'
            }
        }
        
        stage('Documentation Coverage') {
            steps {
                sh 'php bin/anchor-docs analyze --min-coverage=80 --format=json --output=coverage.json'
            }
            post {
                always {
                    archiveArtifacts artifacts: 'coverage.json'
                    publishHTML([
                        allowMissing: false,
                        alwaysLinkToLastBuild: true,
                        keepAll: true,
                        reportDir: '.',
                        reportFiles: 'coverage-report.html',
                        reportName: 'Documentation Coverage Report'
                    ])
                }
            }
        }
    }
}
```

## Использование в PHP коде

### Программный доступ к анализатору
```php
<?php

require_once 'vendor/autoload.php';

use Anchor\DocsCoverage\Analyzer\CodeAnalyzer;
use Anchor\DocsCoverage\Analyzer\DocumentationAnalyzer;
use Anchor\DocsCoverage\Config\Configuration;
use Anchor\DocsCoverage\Service\CoverageAnalysisService;
use Anchor\DocsCoverage\Report\ReportGenerator;

// Создание конфигурации
$config = new Configuration(
    projectRoot: '/path/to/project',
    sourcePaths: ['src/', 'lib/'],
    docsPaths: ['docs/'],
    excludePaths: ['vendor/', 'tests/'],
    minimumCoverage: 80.0
);

// Создание сервисов
$codeAnalyzer = new CodeAnalyzer();
$documentationAnalyzer = new DocumentationAnalyzer();
$analysisService = new CoverageAnalysisService($codeAnalyzer, $documentationAnalyzer);

// Выполнение анализа
$report = $analysisService->analyze($config);

// Генерация отчета
$reportGenerator = new ReportGenerator();
$consoleReport = $reportGenerator->generateConsoleReport($report, $config->getMinimumCoverage());

echo $consoleReport;

// Проверка результата
if ($report->isSuccessful($config->getMinimumCoverage())) {
    echo "✅ Coverage check passed!\n";
} else {
    echo "❌ Coverage check failed!\n";
    exit(1);
}
```

## Форматирование ссылок в документации

### Прямые ссылки на файлы
```markdown
См. код в файле [UserService.php](../src/Example/UserService.php)
```

### Ссылки с указанием строки
```markdown
Метод определен в [UserService.php#L25](../src/Example/UserService.php#L25)
```

### Упоминание классов в тексте
```markdown
Класс `UserService` реализует основную логику.
Используйте `Anchor\DocsCoverage\Example\UserService` для работы с пользователями.
```

### Упоминание методов
```markdown
Вызовите метод `getUserById()` для получения пользователя.
Статический метод `UserService::createInstance()` создает экземпляр.
```

## Настройка исключений

### Исключение файлов по маске
```yaml
exclude_paths:
  - "vendor/"
  - "tests/"
  - "*.tmp.php"
  - "src/deprecated/"
```

### Исключение по типам элементов
```php
// В коде анализатора можно добавить фильтрацию
$elements = array_filter($elements, function($element) {
    // Исключаем getter/setter методы
    if (preg_match('/^(get|set)[A-Z]/', $element->getName())) {
        return false;
    }
    
    // Исключаем приватные методы
    if ($element->isPrivate()) {
        return false;
    }
    
    return true;
});
``` 