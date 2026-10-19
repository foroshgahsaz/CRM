<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $public = [
            'company_name', 'company_tagline', 'company_description',
            'contact_phone', 'contact_email', 'contact_address',
            'required_documents_text', 'process_guide',
        ];

        foreach (config('crm') as $key => $value) {
            SystemSetting::query()->firstOrCreate(
                ['key' => $key],
                [
                    'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                    'group' => str_starts_with($key, 'company_') || str_starts_with($key, 'contact_') ? 'public' : 'operations',
                    'is_public' => in_array($key, $public, true),
                ],
            );
        }
    }
}
