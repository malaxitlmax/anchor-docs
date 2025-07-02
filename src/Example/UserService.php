<?php

declare(strict_types=1);

namespace Anchor\DocsCoverage\Example;

/**
 * Сервис для работы с пользователями
 */
final class UserService
{
    private array $users = [];

    /**
     * Получает пользователя по ID
     */
    public function getUserById(int $id): ?array
    {
        return $this->users[$id] ?? null;
    }

    /**
     * Создает нового пользователя
     */
    public function createUser(string $name, string $email): int
    {
        $id = count($this->users) + 1;
        $this->users[$id] = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $id;
    }

    /**
     * Удаляет пользователя
     */
    public function deleteUser(int $id): bool
    {
        if (isset($this->users[$id])) {
            unset($this->users[$id]);
            return true;
        }
        return false;
    }

    // Этот метод НЕ задокументирован - должен попасть в отчет
    public function updateUser(int $id, array $data): bool
    {
        if (!isset($this->users[$id])) {
            return false;
        }
        
        $this->users[$id] = array_merge($this->users[$id], $data);
        return true;
    }

    // Приватный метод - может не требовать документации  
    private function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Получает всех пользователей
     */
    public function getAllUsers(): array
    {
        return array_values($this->users);
    }

    // Еще один недокументированный метод
    public function getUsersByName(string $name): array
    {
        return array_filter($this->users, fn($user) => $user['name'] === $name);
    }
} 