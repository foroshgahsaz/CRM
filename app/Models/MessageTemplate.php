<?php

namespace App\Models;

use Database\Factories\MessageTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['event_key', 'title', 'body', 'channel', 'is_active', 'allowed_variables', 'sms_ir_template_id', 'sms_ir_parameter_map'])]
class MessageTemplate extends Model
{
    /** @use HasFactory<MessageTemplateFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allowed_variables' => 'array',
            'sms_ir_parameter_map' => 'array',
            'sms_ir_template_id' => 'integer',
        ];
    }
}
