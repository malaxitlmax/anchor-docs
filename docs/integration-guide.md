# Руководство по интеграции Anchor Documentation Coverage Analyzer

## 🎯 Обзор

Этот документ описывает различные способы подключения **Anchor Documentation Coverage Analyzer** в другие PHP проекты.

## 📦 Способы интеграции

### 1. Установка через Composer (Рекомендуемый)

#### Вариант A: Через Packagist (после публикации)
```bash
# В вашем проекте
composer require malaxitlmax/anchor-docs --dev
```

#### Вариант B: Через VCS репозиторий
Добавьте в `composer.json` вашего проекта:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/malaxitlmax/anchor-docs"
        }
    ],
    "require-dev": {
        "malaxitlmax/anchor-docs": "dev-main"
    }
}
```

Затем выполните:
```bash
composer install
```

#### Вариант C: Через локальный путь
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../anchor-docs"
        }
    ],
    "require-dev": {
        "malaxitlmax/anchor-docs": "*"
    }
}
```

### 2. Git Submodule

```bash
# В корне вашего проекта
git submodule add https://github.com/malaxitlmax/anchor-docs tools/anchor-docs
git submodule update --init --recursive

# Установка зависимостей
cd tools/anchor-docs
composer install --no-dev
chmod +x bin/anchor-docs
```

### 3. Клонирование в отдельную директорию

```bash
# В корне вашего проекта
git clone https://github.com/malaxitlmax/anchor-docs tools/anchor-docs
cd tools/anchor-docs
composer install --no-dev
chmod +x bin/anchor-docs
```

## ⚙️ Настройка после установки

### 1. Создание конфигурационного файла

Создайте `anchor-docs.yml` в корне вашего проекта:

```yaml
# anchor-docs.yml
source_paths:
  - "src/"           # Ваши исходные файлы
  - "app/"           # Дополнительные директории
docs_paths:
  - "docs/"          # Ваша документация
  - "README.md"      # Отдельные файлы
exclude_paths:
  - "vendor/"        # Исключить vendor
  - "tests/"         # Исключить тесты
  - "storage/"       # Исключить временные файлы
minimum_coverage: 80.0  # Минимальное покрытие
baseline_file: "docs-baseline.yml"  # Baseline файл (опционально)
output_format: "console" # console|html|json
```

### 2. Пример структуры проекта

```
your-project/
├── src/                    # Ваш код
├── docs/                   # Документация
├── tools/anchor-docs/      # Anchor docs (если через git clone)
├── anchor-docs.yml         # Конфигурация
├── composer.json
└── README.md
```

## 🚀 Использование

### Базовое использование

```bash
# Через Composer (если установлен глобально)
vendor/bin/anchor-docs analyze

# Через прямой путь (git clone/submodule)
php tools/anchor-docs/bin/anchor-docs analyze

# С baseline для игнорирования legacy кода
vendor/bin/anchor-docs analyze --baseline=docs-baseline.yml

# Или создайте alias в composer.json
```

### Добавление в composer.json scripts

```json
{
    "scripts": {
        "docs-check": "anchor-docs analyze",
        "docs-coverage": "anchor-docs analyze --format=html --output=docs-coverage.html",
        "docs-baseline": "anchor-docs analyze --generate-baseline --baseline=docs-baseline.yml",
        "ci-docs": "anchor-docs analyze --baseline=docs-baseline.yml --min-coverage=80"
    }
}
```

Использование:
```bash
composer docs-check
composer docs-coverage
composer ci-docs
```

### Создание wrapper скрипта

Создайте `scripts/check-docs.sh`:

```bash
#!/bin/bash
set -e

echo "🔍 Проверка покрытия документацией..."

# Определяем путь к anchor-docs
if [ -f "vendor/bin/anchor-docs" ]; then
    ANCHOR_DOCS="vendor/bin/anchor-docs"
elif [ -f "tools/anchor-docs/bin/anchor-docs" ]; then
    ANCHOR_DOCS="php tools/anchor-docs/bin/anchor-docs"
else
    echo "❌ Anchor docs не найден"
    exit 1
fi

# Запускаем анализ
$ANCHOR_DOCS analyze --min-coverage=80

echo "✅ Проверка завершена"
```

## 🔄 CI/CD интеграция

### GitHub Actions

```yaml
# .github/workflows/docs-coverage.yml
name: Documentation Coverage

on: [push, pull_request]

jobs:
  docs-coverage:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
      with:
        submodules: recursive  # Если используете submodules
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        
    - name: Install dependencies
      run: composer install --no-progress --no-interaction
      
    - name: Install Anchor Docs (если через git clone)
      run: |
        git clone https://github.com/yourusername/anchor-docs tools/anchor-docs
        cd tools/anchor-docs
        composer install --no-dev
        
    - name: Check documentation coverage
      run: |
        vendor/bin/anchor-docs analyze --min-coverage=80
        # или: php tools/anchor-docs/bin/anchor-docs analyze --min-coverage=80
```

### GitLab CI

```yaml
# .gitlab-ci.yml
stages:
  - test

docs-coverage:
  stage: test
  image: php:8.1
  before_script:
    - apt-get update -qq && apt-get install -y -qq git unzip
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --no-progress --no-interaction
  script:
    - vendor/bin/anchor-docs analyze --min-coverage=80
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: coverage.xml
```

### Jenkins

```groovy
pipeline {
    agent any
    
    stages {
        stage('Setup') {
            steps {
                sh 'composer install'
            }
        }
        
        stage('Documentation Coverage') {
            steps {
                sh 'vendor/bin/anchor-docs analyze --min-coverage=80 --format=json --output=docs-coverage.json'
            }
            post {
                always {
                    archiveArtifacts artifacts: 'docs-coverage.json', fingerprint: true
                }
            }
        }
    }
}
```

## 📊 Отчеты и интеграция

### Генерация отчетов

```bash
# HTML отчет для просмотра
anchor-docs analyze --format=html --output=docs-coverage.html

# JSON отчет для автоматизации
anchor-docs analyze --format=json --output=docs-coverage.json

# Консольный отчет (по умолчанию)
anchor-docs analyze
```

### Интеграция с другими инструментами

#### PHPStan integration
```bash
# Проверяем код и документацию
vendor/bin/phpstan analyse src/
vendor/bin/anchor-docs analyze --min-coverage=80
```

#### Make integration
```makefile
# Makefile
.PHONY: docs-check quality

docs-check:
	vendor/bin/anchor-docs analyze --min-coverage=80

quality: docs-check
	vendor/bin/phpstan analyse src/
	vendor/bin/php-cs-fixer fix --dry-run
```

## 🔧 Настройка под конкретный проект

### Laravel проекты
```yaml
# anchor-docs.yml для Laravel
source_paths:
  - "app/"
docs_paths:
  - "docs/"
  - "README.md"
exclude_paths:
  - "vendor/"
  - "storage/"
  - "bootstrap/cache/"
  - "tests/"
minimum_coverage: 75.0
```

### Symfony проекты
```yaml
# anchor-docs.yml для Symfony
source_paths:
  - "src/"
docs_paths:
  - "docs/"
  - "README.md"
exclude_paths:
  - "vendor/"
  - "var/"
  - "tests/"
minimum_coverage: 80.0
```

### WordPress плагины
```yaml
# anchor-docs.yml для WordPress
source_paths:
  - "includes/"
  - "admin/"
docs_paths:
  - "docs/"
  - "README.txt"
exclude_paths:
  - "vendor/"
  - "tests/"
minimum_coverage: 70.0
```

## 🎯 Лучшие практики

### 1. Версионирование
- Зафиксируйте версию в `composer.json`
- Используйте семантическое версионирование
- Проверяйте совместимость при обновлениях

### 2. Конфигурация
- Держите `anchor-docs.yml` в VCS
- Настройте разные уровни покрытия для разных сред
- Используйте переменные окружения для CI/CD

### 3. Мониторинг
- Добавьте проверку в CI/CD
- Генерируйте отчеты для каждого релиза
- Отслеживайте тренды покрытия

### 4. Команда
- Обучите команду работе с инструментом
- Включите в Definition of Done
- Сделайте частью code review

## ❓ Troubleshooting

### Проблема: "anchor-docs не найден"
```bash
# Проверьте установку
which anchor-docs
composer show anchor/docs-coverage-analyzer

# Проверьте права доступа
chmod +x vendor/bin/anchor-docs
```

### Проблема: "Недостаточно памяти"
```bash
# Увеличьте лимит памяти
php -d memory_limit=512M vendor/bin/anchor-docs analyze
```

### Проблема: "Файлы не найдены"
```bash
# Проверьте пути в anchor-docs.yml
# Используйте абсолютные пути при необходимости
```

## 📝 Заключение

**Anchor Documentation Coverage Analyzer** легко интегрируется в любой PHP проект и позволяет автоматизировать контроль качества документации. Выберите подходящий способ интеграции и настройте под ваши потребности!

### Рекомендуемый workflow:
1. Установите через Composer
2. Настройте `anchor-docs.yml`
3. Добавьте в CI/CD
4. Включите в процесс разработки

**Удачной документации!** 📚✨ 