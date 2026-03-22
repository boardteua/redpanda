<?php

namespace App\Chat;

/**
 * Роль відображення та авторизації в чаті (T21).
 * Staff (mod/admin) має пріоритет над VIP для плашки.
 */
enum ChatRole: string
{
    case Guest = 'guest';
    case User = 'user';
    case Vip = 'vip';
    case Moderator = 'moderator';
    case Admin = 'admin';

    /**
     * Клас/токен для поля post_color (узгоджено з legacy board.te.ua).
     */
    public function postColorClass(): string
    {
        return match ($this) {
            self::Guest => 'guest',
            self::User => 'user',
            self::Vip => 'vip',
            self::Moderator => 'mod',
            self::Admin => 'admin',
        };
    }

    /**
     * Колір плашки для UI; null — без акценту (звичайний / гість).
     */
    public function badgeColor(): ?string
    {
        return match ($this) {
            self::Admin => '#1a1a1a',
            self::Moderator => '#16a34a',
            self::Vip => '#ea580c',
            default => null,
        };
    }
}
