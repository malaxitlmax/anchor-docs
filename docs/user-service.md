# UserService Documentation

Класс `UserService` предоставляет функциональность для управления пользователями в системе.

## Обзор

Класс `Anchor\DocsCoverage\Example\UserService` реализует базовые операции CRUD для работы с пользователями.

См. исходный код: [UserService.php](../src/Example/UserService.php#L10)

## Публичные методы

### getUserById()

Метод `getUserById()` возвращает данные пользователя по его идентификатору.

**Параметры:**
- `$id` (int) - идентификатор пользователя

**Возвращает:** 
- `array|null` - данные пользователя или null если не найден

### createUser()

Создает нового пользователя в системе.

**Параметры:**
- `$name` (string) - имя пользователя  
- `$email` (string) - email пользователя

**Возвращает:**
- `int` - идентификатор созданного пользователя

### deleteUser() 

Удаляет пользователя из системы.

**Параметры:**
- `$id` (int) - идентификатор пользователя

**Возвращает:**
- `bool` - успешность операции

### getAllUsers()

Возвращает список всех пользователей в системе.

**Возвращает:**
- `array` - массив данных всех пользователей

## Примеры использования

```php
$userService = new UserService();

// Создание пользователя
$userId = $userService->createUser('John Doe', 'john@example.com');

// Получение пользователя
$user = $userService->getUserById($userId);

// Получение всех пользователей
$allUsers = $userService->getAllUsers();

// Удаление пользователя
$deleted = $userService->deleteUser($userId);
```

## Замечания

- Класс использует внутренний массив для хранения данных (не для продакшена)
- Все публичные методы должны быть задокументированы
- Некоторые методы как `updateUser()` и `getUsersByName()` пока не имеют документации 