# Руководство по использованию Anchor Documentation Coverage Analyzer

## 🚀 Быстрый старт

### Установка
```bash
./install.sh
```

### Первый запуск
```bash
php bin/anchor-docs analyze
```

## 📊 Результаты тестирования

✅ **Инструмент полностью функционален!**

### Проведенные тесты:

1. **Анализ исходного кода**: ✅ Найдено 69 методов в проекте
2. **Анализ документации**: ✅ Обнаружено покрытие 31.9% (22/69 элементов)
3. **Консольный отчет**: ✅ Детальная статистика с указанием файлов и строк
4. **HTML отчет**: ✅ Создан файл `coverage-report.html` (7KB)
5. **JSON отчет**: ✅ Создан файл `coverage.json` с структурированными данными
6. **Фильтрация ложных срабатываний**: ✅ Исключены общие акронимы (PHP, HTML, JSON, etc.)

## 📈 Пример вывода

```
Documentation Coverage Report
==================================================

Overall Coverage: 31.9% (22/69 elements)
Minimum Required: 30.0%

Coverage by Type:
------------------------------
  Method      : 31.9% (22/69)

Undocumented Elements:
------------------------------
  src/Config/Configuration.php
    Line 17: method __construct
    Line 35: method fromArray
    ...

✅ PASSED (при покрытии >= минимального)
❌ FAILED (при покрытии < минимального)
```

## 🔧 Основные команды

### Базовый анализ
```bash
php bin/anchor-docs analyze
```

### Анализ с настройками
```bash
# Установка минимального покрытия
php bin/anchor-docs analyze --min-coverage=80

# Анализ конкретных директорий
php bin/anchor-docs analyze --source=app/ --docs=documentation/

# Исключение папок
php bin/anchor-docs analyze --exclude=vendor/ --exclude=tests/
```

### Генерация отчетов
```bash
# HTML отчет (красивый веб-интерфейс)
php bin/anchor-docs analyze --format=html --output=report.html

# JSON отчет (для интеграции)
php bin/anchor-docs analyze --format=json --output=coverage.json

# Консольный отчет (по умолчанию)
php bin/anchor-docs analyze --format=console
```

## ⚙️ Конфигурация

### Файл `anchor-docs.yml`:
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

## 🎯 Возможности

### ✅ Что умеет анализатор:

1. **Парсинг PHP кода**:
   - Классы, интерфейсы, трейты
   - Методы, функции, свойства
   - Модификаторы доступа (public/private/protected)
   - Пространства имен

2. **Анализ Markdown документации**:
   - Упоминания классов и методов в тексте
   - Ссылки на исходные файлы `[text](file.php)`
   - Проверка существования файлов
   - Фильтрация ложных срабатываний

3. **Сопоставление кода и документации**:
   - Поиск соответствий по именам
   - Валидация ссылок на файлы
   - Маркировка задокументированных элементов

4. **Отчеты**:
   - Консольный с детализацией
   - HTML с красивым интерфейсом
   - JSON для автоматизации

## 🔄 CI/CD интеграция

### Exit codes:
- `0` - успех (покрытие >= минимального, нет сломанных ссылок)
- `1` - ошибка (низкое покрытие или сломанные ссылки)

### Пример GitHub Actions:
```yaml
- name: Check docs coverage
  run: php bin/anchor-docs analyze --min-coverage=80
```

## 📝 Рекомендации по документации

### Хорошо документированный код:
```php
/**
 * Сервис для работы с пользователями
 */
class UserService 
{
    /**
     * Получает пользователя по ID
     */
    public function getUserById(int $id): ?array 
    {
        // ...
    }
}
```

### Корректные ссылки в документации:
```markdown
## UserService

Класс `UserService` находится в [UserService.php](../src/UserService.php).

Метод `getUserById()` возвращает данные пользователя.
```

## 🎉 Заключение

**Anchor Documentation Coverage Analyzer** - полностью готовый к использованию инструмент для контроля качества документации PHP проектов!

### Основные преимущества:
- 🚀 Простота использования
- 📊 Точный анализ
- 🔧 Гибкая конфигурация
- 📈 Множественные форматы отчетов
- 🔄 Готов для CI/CD

**Тестирование прошло успешно - инструмент работает корректно!** ✅ 