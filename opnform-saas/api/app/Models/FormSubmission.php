<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'public_id',
        'data',
        'metadata',
        'completion_time',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'completion_time' => 'integer',
    ];

    protected $attributes = [
        'status' => 'completed',
        'data' => '{}',
        'metadata' => '{}',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($submission) {
            if (empty($submission->public_id)) {
                $submission->public_id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the form
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get a specific field value from submission data
     */
    public function getValue(string $fieldId, $default = null)
    {
        return data_get($this->data, $fieldId, $default);
    }

    /**
     * Check if submission is complete
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if submission is partial
     */
    public function isPartial(): bool
    {
        return $this->status === 'partial';
    }

    /**
     * Get formatted data for display
     */
    public function getFormattedData(): array
    {
        $form = $this->form;
        $formattedData = [];

        foreach ($form->properties as $field) {
            $fieldId = $field['id'] ?? null;
            if ($fieldId && isset($this->data[$fieldId])) {
                $formattedData[] = [
                    'field_id' => $fieldId,
                    'field_name' => $field['name'] ?? $fieldId,
                    'field_type' => $field['type'] ?? 'text',
                    'value' => $this->data[$fieldId],
                ];
            }
        }

        return $formattedData;
    }

    /**
     * Export to array for CSV/Excel
     */
    public function toExportArray(): array
    {
        $export = [
            'id' => $this->public_id,
            'submitted_at' => $this->created_at->toIso8601String(),
            'completion_time' => $this->completion_time,
        ];

        foreach ($this->data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $export[$key] = $value;
        }

        return $export;
    }
}
