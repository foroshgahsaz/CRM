<?php

namespace App\Filament\Forms\Components;

use App\Support\JalaliDate;
use Filament\Forms\Components\TextInput;

class JalaliDateInput extends TextInput
{
    protected bool $limitToToday = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->placeholder('1404/07/27')
            ->extraInputAttributes([
                'data-jdp' => '',
                'autocomplete' => 'off',
            ], merge: true);
    }

    public function maxToday(bool $condition = true): static
    {
        $this->limitToToday = $condition;

        if ($condition) {
            $this->extraInputAttributes([
                'data-jdp-max-date' => 'today',
            ], merge: true);
        }

        return $this;
    }

    public function minJalali(string $jalaliDate): static
    {
        return $this->extraInputAttributes([
            'data-jdp-min-date' => $jalaliDate,
        ], merge: true);
    }

    public function maxJalali(string $jalaliDate): static
    {
        return $this->extraInputAttributes([
            'data-jdp-max-date' => $jalaliDate,
        ], merge: true);
    }

    public function defaultToday(): static
    {
        return $this->default(JalaliDate::formatDate(now()));
    }
}
