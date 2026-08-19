<?php

namespace App\Enums;

/**
 * The canonical set of property listing types — the single source of truth used by
 * validation, forms, filters and labels across the whole app. Add a globally-relevant
 * category here and it becomes selectable everywhere; nothing else hard-codes the list.
 *
 * Enum cases are TitleCase; the backing value is the slug stored in the database.
 */
enum PropertyType: string
{
    case Rent = 'rent';
    case Sale = 'sale';
    case Hotel = 'hotel';
    case Mess = 'mess';
    case Land = 'land';
    case Commercial = 'commercial';
    case Office = 'office';
    case Room = 'room';
    case Vacation = 'vacation';
    case Parking = 'parking';

    /** Human-friendly label shown in dropdowns, badges and messages. */
    public function label(): string
    {
        return match ($this) {
            self::Rent => 'Rent',
            self::Sale => 'Sale',
            self::Hotel => 'Hotel & Short Stay',
            self::Mess => 'Shared Mess',
            self::Land => 'Land / Plot',
            self::Commercial => 'Commercial',
            self::Office => 'Office Space',
            self::Room => 'Room / Shared',
            self::Vacation => 'Vacation Rental',
            self::Parking => 'Parking / Garage',
        };
    }

    /** Types whose price is a recurring monthly figure (vs a one-off sale price). */
    public function isRecurring(): bool
    {
        return in_array($this, [self::Rent, self::Mess, self::Room, self::Office, self::Commercial, self::Parking], true);
    }

    /**
     * The unit suffix shown after a price: '/month' for recurring rentals, '/night'
     * for short stays, or null for one-off prices (sale, land). Single source of
     * truth so cards, detail pages and the admin table stay consistent.
     */
    public function priceSuffix(): ?string
    {
        return match (true) {
            $this->isRecurring() => '/month',
            in_array($this, [self::Hotel, self::Vacation], true) => '/night',
            default => null,
        };
    }

    /**
     * All type slugs, for validation rules and enum checks.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }

    /**
     * Slug => label map, for building <select> options and badge lookups.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
