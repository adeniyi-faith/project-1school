<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'slug', 'subject', 'body', 'variables', 'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Render template by replacing {{variable}} placeholders.
     */
    public function render(array $data): array
    {
        $subject = $this->subject;
        $body    = $this->body;
        foreach ($data as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
            $body    = str_replace('{{' . $key . '}}', $value, $body);
        }
        return ['subject' => $subject, 'body' => $body];
    }
}
