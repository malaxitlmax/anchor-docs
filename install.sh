#!/bin/bash

echo "🚀 Anchor Documentation Coverage Analyzer - Установка"
echo "======================================================="

# Проверка PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP не найден. Пожалуйста, установите PHP 8.1 или выше."
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✅ PHP версия: $PHP_VERSION"

if [[ $(echo "$PHP_VERSION < 8.1" | bc -l) -eq 1 ]]; then
    echo "⚠️  Рекомендуется PHP 8.1 или выше"
fi

# Проверка Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer не найден. Пожалуйста, установите Composer."
    exit 1
fi

echo "✅ Composer найден"

# Установка зависимостей
echo ""
echo "📦 Установка зависимостей..."
composer install --no-dev --optimize-autoloader

if [ $? -ne 0 ]; then
    echo "❌ Ошибка установки зависимостей"
    exit 1
fi

# Делаем исполняемым
chmod +x bin/anchor-docs

echo ""
echo "✅ Установка завершена!"
echo ""
echo "🎯 Быстрый тест:"
echo "   php bin/anchor-docs analyze"
echo ""
echo "📖 Полная документация:"
echo "   cat README.md"
echo ""
echo "🔧 Конфигурация:"
echo "   cat anchor-docs.yml"

# Проверяем на примере
if [ -f "src/Example/UserService.php" ]; then
    echo ""
    echo "🧪 Запуск тестового анализа..."
    php bin/anchor-docs analyze --min-coverage=50
fi 