# Публикация пакета на Packagist

## 🎯 Обзор

Для упрощения установки и использования **Anchor Documentation Coverage Analyzer** в других проектах, рекомендуется опубликовать пакет на [Packagist](https://packagist.org/).

## 📦 Подготовка к публикации

### 1. Проверка composer.json

Убедитесь, что `composer.json` содержит всю необходимую информацию:

```json
{
    "name": "anchor/docs-coverage-analyzer",
    "description": "Documentation coverage analyzer for PHP projects",
    "type": "library",
    "license": "MIT",
    "keywords": ["documentation", "coverage", "analysis", "php", "markdown"],
    "homepage": "https://github.com/yourusername/anchor-docs",
    "authors": [
        {
            "name": "Your Name",
            "email": "your.email@example.com",
            "role": "Developer"
        }
    ],
    "support": {
        "issues": "https://github.com/yourusername/anchor-docs/issues",
        "source": "https://github.com/yourusername/anchor-docs"
    },
    "autoload": {
        "psr-4": {
            "Anchor\\DocsCoverage\\": "src/"
        }
    },
    "require": {
        "php": ">=8.1",
        "symfony/console": "^6.0",
        "symfony/finder": "^6.0",
        "symfony/yaml": "^6.0",
        "league/commonmark": "^2.0",
        "nikic/php-parser": "^4.0"
    },
    "bin": [
        "bin/anchor-docs"
    ]
}
```

### 2. Создание git тегов

```bash
# Создание релиза
git tag -a v1.0.0 -m "First stable release"
git push origin v1.0.0

# Или для pre-release
git tag -a v1.0.0-beta -m "Beta release"
git push origin v1.0.0-beta
```

## 🚀 Публикация на Packagist

### 1. Регистрация на Packagist

1. Зайдите на [packagist.org](https://packagist.org/)
2. Войдите через GitHub
3. Нажмите "Submit" в верхнем меню

### 2. Добавление пакета

1. Введите URL вашего репозитория: `https://github.com/yourusername/anchor-docs`
2. Нажмите "Check"
3. Если всё корректно, нажмите "Submit"

### 3. Настройка автообновления

Для автоматического обновления пакета при новых релизах:

1. Перейдите в настройки вашего пакета на Packagist
2. Найдите раздел "GitHub Service Hook"
3. Следуйте инструкциям для настройки webhook

## 🔧 GitHub Actions для автоматизации

Создайте `.github/workflows/release.yml`:

```yaml
name: Release

on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        
    - name: Validate composer.json
      run: composer validate --strict
      
    - name: Install dependencies
      run: composer install --no-progress --no-interaction
      
    - name: Run tests (если есть)
      run: composer test || echo "No tests configured"
      
    - name: Create GitHub Release
      uses: actions/create-release@v1
      env:
        GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
      with:
        tag_name: ${{ github.ref }}
        release_name: Release ${{ github.ref }}
        draft: false
        prerelease: false
```

## 📋 Чеклист перед публикацией

- [ ] Проверен и обновлён `composer.json`
- [ ] Добавлены keywords для лучшего поиска
- [ ] Указаны правильные URLs и контакты
- [ ] Созданы git теги
- [ ] Написана документация (README.md, CHANGELOG.md)
- [ ] Выбрана лицензия (MIT рекомендуется)
- [ ] Проверена PSR-4 автозагрузка
- [ ] Работает команда `composer validate`

## 🎉 После публикации

После успешной публикации пакет будет доступен для установки:

```bash
composer require anchor/docs-coverage-analyzer --dev
```

### Мониторинг

- Отслеживайте статистику загрузок на Packagist
- Следите за issues и pull requests
- Регулярно обновляйте зависимости
- Публикуйте новые версии с bug fixes и улучшениями

### Семантическое версионирование

Используйте [SemVer](https://semver.org/):

- `1.0.0` - Major version (breaking changes)
- `1.1.0` - Minor version (new features, backward compatible)
- `1.1.1` - Patch version (bug fixes)

```bash
# Примеры тегирования
git tag v1.0.0    # Первый стабильный релиз
git tag v1.1.0    # Новая функциональность
git tag v1.1.1    # Исправление багов
git tag v2.0.0    # Breaking changes
```

## 🔄 Обновление пакета

Для публикации обновлений:

1. Внесите изменения в код
2. Обновите версию в `composer.json` (опционально)
3. Создайте новый git tag
4. Packagist автоматически подхватит изменения

```bash
git add .
git commit -m "Add new feature"
git tag v1.2.0
git push origin main
git push origin v1.2.0
```

## 📞 Поддержка

После публикации не забывайте:

- Отвечать на issues
- Принимать pull requests
- Обновлять документацию
- Следить за безопасностью зависимостей

**Удачной публикации!** 🚀 