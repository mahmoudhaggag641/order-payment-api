<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function canProcessPayment(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function canBeDeleted(): bool
    {
        return $this !== self::CANCELLED;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
