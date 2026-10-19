<?php

namespace App\Models;

use Database\Factories\SystemSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'group', 'is_public'])]
class SystemSetting extends Model
{
    /** @use HasFactory<SystemSettingFactory> */
    use HasFactory;

    public function decodedValue(): mixed
    {
        if ($this->value === null || $this->value === '') {
            return null;
        }

        $decoded = json_decode($this->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->value;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }
}
