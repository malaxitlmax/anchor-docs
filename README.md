# Anchor Documentation Coverage Analyzer

PHP инструмент для анализа покрытия кода документацией, работающий аналогично PHPStan, но проверяющий наличие и актуальность документации в Markdown файлах.

## Возможности

- 📊 **Анализ покрытия документацией**: Проверяет, какие классы, методы и функции задокументированы
- 🔍 **Поиск ссылок**: Находит ссылки на код в Markdown документации и проверяет их актуальность
- 📈 **Детальные отчеты**: Генерирует отчеты в консольном, JSON и HTML форматах
- ⚙️ **Настраиваемость**: Гибкая конфигурация через YAML файлы и параметры командной строки
- 🎯 **Интеграция в CI/CD**: Возвращает код ошибки при недостаточном покрытии

## Установка

```bash
# Клонируйте репозиторий
git clone <repository-url> anchor-docs
cd anchor-docs

# Установите зависимости
composer install

# Сделайте исполняемым
chmod +x bin/anchor-docs
```

## Быстрый старт

```bash
# Базовый анализ текущего проекта
php bin/anchor-docs analyze

# Анализ с минимальным покрытием 90%
php bin/anchor-docs analyze --min-coverage=90

# Генерация HTML отчета
php bin/anchor-docs analyze --format=html --output=coverage-report.html

# Анализ конкретных директорий
php bin/anchor-docs analyze --source=app/ --docs=documentation/
```

## Конфигурация

Создайте файл `anchor-docs.yml` в корне проекта:

```yaml
source_paths:
  - "src/"
docs_paths:
  - "docs/"
exclude_paths:
  - "vendor/"
  - "tests/"
minimum_coverage: 80.0
output_format: "console"
```

## Пример работы

В проекте есть пример класса `UserService` и его документация. Запустите анализ:

```bash
php bin/anchor-docs analyze
```

Анализатор найдет:
- ✅ Задокументированные методы: `getUserById()`, `createUser()`, `deleteUser()`, `getAllUsers()`
- ❌ Недокументированные методы: `updateUser()`, `getUsersByName()`
- 🔗 Проверит ссылки на исходный код в документации

## Интеграция в CI/CD

```yaml
# GitHub Actions
- name: Check documentation coverage
  run: php bin/anchor-docs analyze --min-coverage=80
```

```bash
# GitLab CI
script:
  - php bin/anchor-docs analyze --min-coverage=80
```

## Форматы отчетов

### Консольный
```
Documentation Coverage Report
==================================================

Overall Coverage: 66.7% (4/6 elements)
Minimum Required: 80.0%

Coverage by Type:
------------------------------
  Class       : 100.0% (1/1)
  Method      : 66.7% (4/6)

Undocumented Elements:
------------------------------
  src/Example/UserService.php
    Line 48: method updateUser
    Line 70: method getUsersByName

❌ FAILED
Issues: Coverage 66.7% is below minimum 80.0%
```

### HTML
Генерирует красивый веб-отчет с интерактивными элементами и детальной статистикой.

### JSON
Структурированные данные для интеграции с другими инструментами.

## Архитектура

- **CodeAnalyzer**: Парсит PHP код с помощью nikic/php-parser
- **DocumentationAnalyzer**: Анализирует Markdown с помощью league/commonmark
- **CoverageAnalysisService**: Сопоставляет код и документацию
- **ReportGenerator**: Создает отчеты в разных форматах

## Разработка

```bash
# Запуск на собственном коде
php bin/anchor-docs analyze --source=src/ --docs=docs/

# Проверка стиля кода
vendor/bin/phpstan analyse src/

# Запуск тестов (когда будут добавлены)
vendor/bin/phpunit
```