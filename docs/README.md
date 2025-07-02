# Anchor Documentation Coverage Analyzer

Этот инструмент анализирует покрытие PHP кода документацией, работая аналогично PHPStan, но фокусируясь на проверке наличия и актуальности документации.

## Возможности

- **Анализ исходного кода**: Поиск классов, методов, функций в PHP файлах
- **Анализ документации**: Поиск ссылок на код в Markdown файлах  
- **Генерация отчетов**: Консольный, JSON и HTML форматы
- **Конфигурируемость**: Настройки путей, исключений, минимального покрытия

## Основные компоненты

### CodeAnalyzer
Класс `Anchor\DocsCoverage\Analyzer\CodeAnalyzer` отвечает за анализ PHP кода.
См. [CodeAnalyzer.php](../src/Analyzer/CodeAnalyzer.php)

### DocumentationAnalyzer  
Класс `Anchor\DocsCoverage\Analyzer\DocumentationAnalyzer` анализирует Markdown документацию.
См. [DocumentationAnalyzer.php](../src/Analyzer/DocumentationAnalyzer.php)

### CoverageAnalysisService
Главный сервис `Anchor\DocsCoverage\Service\CoverageAnalysisService` объединяет анализаторы.
См. [CoverageAnalysisService.php](../src/Service/CoverageAnalysisService.php)

## Использование

```bash
# Базовый анализ
php bin/anchor-docs analyze

# С настройками
php bin/anchor-docs analyze --min-coverage=90 --format=html --output=report.html

# Указание путей
php bin/anchor-docs analyze --source=app/ --docs=documentation/
```

## Модели данных

### CodeElement
Представляет элемент кода (класс, метод, функцию).
- Метод `getName()` - получение имени элемента
- Метод `getType()` - получение типа элемента
- Метод `isDocumented()` - проверка документированности

### CoverageReport  
Содержит результаты анализа покрытия.
- Метод `getCoveragePercentage()` - процент покрытия
- Метод `getUndocumentedCodeElements()` - недокументированные элементы

## Конфигурация

Пример файла `anchor-docs.yml`:

```yaml
source_paths:
  - "src/"
docs_paths:
  - "docs/"
minimum_coverage: 80.0
``` 