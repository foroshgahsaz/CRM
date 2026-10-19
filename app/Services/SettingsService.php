<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $stored = $this->all();

        if (array_key_exists($key, $stored)) {
            return $stored[$key];
        }

        return $default ?? config('crm.'.$key);
    }

    public function getInt(string $key, int $default): int
    {
        return (int) $this->get($key, $default);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  list<string>|string  $value
     */
    public function set(string $key, mixed $value, string $group = 'general', bool $isPublic = false): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                'group' => $group,
                'is_public' => $isPublic,
            ],
        );

        Cache::forget($this->cacheKey());
    }

    /**
     * @return array<string, mixed>
     */
    public function publicProfile(): array
    {
        return [
            'company_name' => (string) $this->get('company_name'),
            'company_tagline' => (string) $this->get('company_tagline'),
            'company_description' => (string) $this->get('company_description'),
            'contact_phone' => (string) $this->get('contact_phone', ''),
            'contact_email' => (string) $this->get('contact_email', ''),
            'contact_address' => (string) $this->get('contact_address', ''),
            'required_documents_text' => (string) $this->get('required_documents_text'),
            'process_guide' => (string) $this->get('process_guide'),
            'logo' => $this->get('company_logo'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function all(): array
    {
        if (! Schema::hasTable('system_settings')) {
            return [];
        }

        return Cache::remember($this->cacheKey(), 300, function (): array {
            return SystemSetting::query()
                ->orderBy('key')
                ->get()
                ->mapWithKeys(fn (SystemSetting $setting): array => [$setting->key => $setting->decodedValue()])
                ->all();
        });
    }

    private function cacheKey(): string
    {
        return 'crm.settings';
    }
}
