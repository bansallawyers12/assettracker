<?php

namespace App\Enums;

enum AppRole: string
{
    case Administrator = 'administrator';
    case Staff = 'staff';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Staff => 'Staff',
            self::Viewer => 'Viewer',
        };
    }

    public function canMutatePortfolio(): bool
    {
        return $this !== self::Viewer;
    }

    /**
     * @return list<self>
     */
    public static function assignable(): array
    {
        return [self::Staff, self::Viewer];
    }
}
