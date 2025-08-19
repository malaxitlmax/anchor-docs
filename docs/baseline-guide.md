# Baseline Guide - Игнорирование известных проблем

## 🎯 Обзор

**Baseline** функциональность в **Anchor Documentation Coverage Analyzer** позволяет игнорировать известные недокументированные элементы, аналогично тому, как работает PHPStan baseline. Это полезно для:

- 📈 **Постепенного улучшения** покрытия документацией в больших проектах
- 🚫 **Игнорирования legacy кода**, который сложно задокументировать сразу
- 🔄 **Непрерывной интеграции** без блокировки из-за старых проблем
- 🎯 **Фокусировки на новом коде** при контроле качества документации

## 📋 Концепция Baseline

Baseline файл содержит список элементов кода (классы, методы, функции), которые:
- Известны как незадокументированные
- Будут исключены из проверок покрытия документацией
- Не будут влиять на результат анализа (pass/fail)

## 🚀 Быстрый старт

### 1. Генерация начального baseline

```bash
# Создать baseline из текущего состояния
php bin/anchor-docs analyze --generate-baseline --baseline=docs-baseline.yml

# С минимальным покрытием
php bin/anchor-docs analyze --generate-baseline --baseline=docs-baseline.yml --min-coverage=80
```

### 2. Использование baseline

```bash
# Обычный анализ с baseline
php bin/anchor-docs analyze --baseline=docs-baseline.yml

# Через конфигурационный файл
php bin/anchor-docs analyze -c anchor-docs.yml
```

### 3. Обновление baseline

```bash
# Удалить недействительные записи из baseline
php bin/anchor-docs analyze --update-baseline --baseline=docs-baseline.yml
```

## ⚙️ Конфигурация

### Файл конфигурации (anchor-docs.yml)

```yaml
source_paths:
  - "src/"
docs_paths:
  - "docs/"
exclude_paths:
  - "vendor/"
  - "tests/"
minimum_coverage: 80.0
baseline_file: "docs-baseline.yml"  # Путь к baseline файлу
```

### Командная строка

```bash
# Указание baseline файла
--baseline=path/to/baseline.yml

# Генерация baseline
--generate-baseline

# Обновление baseline
--update-baseline
```

## 📁 Структура Baseline файла

```yaml
# Anchor Documentation Coverage Analyzer - Baseline File
# This file contains a list of elements that are excluded from documentation coverage checks
# Generated on: 2024-01-15T10:30:00+00:00
# Total entries: 15

version: '1.0'
generated_at: '2024-01-15T10:30:00+00:00'
total_entries: 15

# Files overview:
# - /path/to/UserService.php (3 entries)
# - /path/to/PaymentProcessor.php (12 entries)

entries:
  - file: '/path/to/src/UserService.php'
    line: 45
    element_type: 'method'
    element_name: 'updateUser'
    reason: 'Legacy code - documentation needed'
    hash: 'a1b2c3d4e5f6...'

  - file: '/path/to/src/UserService.php'
    line: 67
    element_type: 'method'
    element_name: 'deleteUser'
    reason: 'Legacy code - documentation needed'
    hash: 'b2c3d4e5f6a1...'

  - file: '/path/to/src/PaymentProcessor.php'
    line: 23
    element_type: 'class'
    element_name: 'PaymentProcessor'
    reason: 'Legacy code - documentation needed'
    hash: 'c3d4e5f6a1b2...'
```

## 🔧 Команды

### Генерация Baseline

```bash
# Базовая генерация
php bin/anchor-docs analyze --generate-baseline --baseline=docs-baseline.yml

# С пользовательским сообщением
php bin/anchor-docs analyze --generate-baseline --baseline=legacy-baseline.yml

# Генерация для конкретных путей
php bin/anchor-docs analyze --generate-baseline --baseline=api-baseline.yml --source=src/Api/
```

**Что происходит:**
1. 📊 Анализируется текущий код
2. 🔍 Находятся все незадокументированные элементы
3. 📝 Создается baseline файл с этими элементами
4. ✅ Выводится статистика

### Использование Baseline

```bash
# Анализ с baseline
php bin/anchor-docs analyze --baseline=docs-baseline.yml

# Из конфигурационного файла
php bin/anchor-docs analyze  # baseline_file указан в anchor-docs.yml

# С дополнительными опциями
php bin/anchor-docs analyze --baseline=docs-baseline.yml --format=html --output=report.html
```

**Что происходит:**
1. 📂 Загружается baseline файл
2. 📊 Анализируется код
3. 🚫 Элементы из baseline исключаются из проверок
4. 📈 Рассчитывается покрытие без baseline элементов

### Обновление Baseline

```bash
# Обновление существующего baseline
php bin/anchor-docs analyze --update-baseline --baseline=docs-baseline.yml

# Проверка валидности без обновления
php bin/anchor-docs analyze --baseline=docs-baseline.yml --min-coverage=0
```

**Что происходит:**
1. 📂 Загружается существующий baseline
2. 📊 Анализируется текущий код
3. ❌ Удаляются недействительные записи (код изменился/удален)
4. 💾 Сохраняется обновленный baseline

## 📈 Workflow и лучшие практики

### Внедрение в Legacy проект

```bash
# 1. Создать начальный baseline
php bin/anchor-docs analyze --generate-baseline --baseline=docs-baseline.yml

# 2. Добавить в конфигурацию
echo "baseline_file: docs-baseline.yml" >> anchor-docs.yml

# 3. Настроить CI/CD
php bin/anchor-docs analyze --min-coverage=80  # Теперь проходит

# 4. Постепенно документировать код и обновлять baseline
```

### Поддержание актуальности

```bash
# Еженедельное обновление baseline
php bin/anchor-docs analyze --update-baseline --baseline=docs-baseline.yml

# Удаление задокументированных элементов из baseline
# (элементы автоматически исключаются при документировании)
```

### Контроль нового кода

```bash
# Проверка без baseline (строгий режим)
php bin/anchor-docs analyze --min-coverage=90  # без --baseline

# Проверка с baseline (для CI)
php bin/anchor-docs analyze --baseline=docs-baseline.yml --min-coverage=80
```

## 🔄 CI/CD интеграция

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
      run: |
        php bin/anchor-docs analyze \
          --baseline=docs-baseline.yml \
          --min-coverage=80 \
          --format=json \
          --output=coverage.json
          
    - name: Upload coverage report
      uses: actions/upload-artifact@v3
      with:
        name: coverage-report
        path: coverage.json
```

### Проверка на Pull Request

```yaml
# Строгая проверка новых изменений
- name: Check new code documentation (strict)
  run: |
    # Временно переименовываем baseline для строгой проверки
    mv docs-baseline.yml docs-baseline.yml.bak
    
    # Проверяем без baseline
    php bin/anchor-docs analyze --min-coverage=95 || STRICT_FAILED=1
    
    # Восстанавливаем baseline
    mv docs-baseline.yml.bak docs-baseline.yml
    
    # Обычная проверка с baseline
    php bin/anchor-docs analyze --baseline=docs-baseline.yml --min-coverage=80
    
    # Если строгая проверка провалилась, уведомляем
    if [ "$STRICT_FAILED" = "1" ]; then
      echo "⚠️ New code has documentation issues. Consider documenting before merge."
    fi
```

## 🎯 Сценарии использования

### Сценарий 1: Новый проект

```bash
# Начать без baseline, сразу с высокими требованиями
php bin/anchor-docs analyze --min-coverage=90
```

### Сценарий 2: Legacy проект

```bash
# 1. Создать baseline из текущего состояния
php bin/anchor-docs analyze --generate-baseline --baseline=legacy-baseline.yml

# 2. Настроить умеренные требования
php bin/anchor-docs analyze --baseline=legacy-baseline.yml --min-coverage=60

# 3. Постепенно повышать требования
# php bin/anchor-docs analyze --baseline=legacy-baseline.yml --min-coverage=70
# php bin/anchor-docs analyze --baseline=legacy-baseline.yml --min-coverage=80
```

### Сценарий 3: Модульное улучшение

```bash
# Создать baseline для каждого модуля
php bin/anchor-docs analyze --generate-baseline --baseline=api-baseline.yml --source=src/Api/
php bin/anchor-docs analyze --generate-baseline --baseline=web-baseline.yml --source=src/Web/

# Проверять модули по отдельности
php bin/anchor-docs analyze --baseline=api-baseline.yml --source=src/Api/ --min-coverage=85
php bin/anchor-docs analyze --baseline=web-baseline.yml --source=src/Web/ --min-coverage=75
```

## 🔍 Мониторинг и отчетность

### Статистика baseline

```bash
# Простой подсчет записей
grep -c "element_name:" docs-baseline.yml

# Подсчет по типам
grep "element_type:" docs-baseline.yml | sort | uniq -c

# Подсчет по файлам
grep "file:" docs-baseline.yml | sort | uniq -c
```

### Отслеживание прогресса

```bash
# Еженедельный отчет об изменениях baseline
git log --oneline docs-baseline.yml

# Сравнение размера baseline
echo "Baseline entries last week: $(git show HEAD~7:docs-baseline.yml | grep -c element_name)"
echo "Baseline entries now: $(grep -c element_name docs-baseline.yml)"
```

## ⚠️ Важные замечания

### Безопасность

- ✅ **Версионируйте baseline файлы** в git
- ✅ **Регулярно обновляйте baseline** (удаляйте недействительные записи)
- ⚠️ **Не игнорируйте новый код** - используйте baseline только для legacy

### Производительность

- 📂 **Baseline файлы легковесны** - сотни записей занимают килобайты
- ⚡ **Фильтрация быстрая** - использует индексы для O(1) поиска
- 💾 **Файлы в формате YAML** - читаемы человеком и VCS-friendly

### Ограничения

- 🔄 **Автоматическое удаление** - задокументированные элементы не удаляются из baseline автоматически
- 📍 **Привязка к строкам** - изменение номеров строк может сделать записи недействительными
- 🏗️ **Рефакторинг** - переименование/перемещение кода требует обновления baseline

## 📝 Примеры

### Пример 1: Постепенное улучшение

```bash
# Неделя 1: Создаем baseline
php bin/anchor-docs analyze --generate-baseline --baseline=week1-baseline.yml
echo "Week 1 baseline: $(grep -c element_name week1-baseline.yml) entries"

# Неделя 2: Документируем 10 методов и обновляем baseline
# ... документируем код ...
php bin/anchor-docs analyze --update-baseline --baseline=week1-baseline.yml
echo "Week 2 baseline: $(grep -c element_name week1-baseline.yml) entries"

# Неделя 3: Повышаем требования
php bin/anchor-docs analyze --baseline=week1-baseline.yml --min-coverage=85
```

### Пример 2: Разные требования для модулей

```bash
# Новый API модуль - строгие требования
php bin/anchor-docs analyze --source=src/Api/ --min-coverage=95

# Legacy модуль - с baseline
php bin/anchor-docs analyze --source=src/Legacy/ --baseline=legacy-baseline.yml --min-coverage=70

# Основной код - умеренные требования
php bin/anchor-docs analyze --source=src/Core/ --baseline=core-baseline.yml --min-coverage=80
```

## 🎉 Заключение

**Baseline функциональность** позволяет:

- ✅ **Внедрить контроль документации** в любой проект
- 📈 **Постепенно улучшать** качество документации
- 🚫 **Не блокировать CI/CD** из-за legacy кода
- 🎯 **Фокусироваться на новом коде** при проверках

**Начните использовать baseline уже сегодня!** 🚀

```bash
# Создайте свой первый baseline
php bin/anchor-docs analyze --generate-baseline --baseline=docs-baseline.yml

# Добавьте в конфигурацию
echo "baseline_file: docs-baseline.yml" >> anchor-docs.yml

# Наслаждайтесь контролем качества документации!
php bin/anchor-docs analyze --min-coverage=80
``` 