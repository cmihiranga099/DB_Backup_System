<?php

namespace BackupSuite\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Strip seconds from time if browser submits H:i:s
        if ($this->has('time') && preg_match('/^\d{2}:\d{2}:\d{2}$/', $this->input('time'))) {
            $this->merge(['time' => substr($this->input('time'), 0, 5)]);
        }
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:120'],
            'project_id' => ['nullable', 'integer', 'exists:backup_projects,id'],
            'type' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly', 'custom'])],
            'mode' => ['required', Rule::in(['database', 'files', 'both'])],
            'time' => ['required', 'date_format:H:i'],
            'cron_expression' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
            'timezone' => ['nullable', 'string', 'max:120'],
            'day_of_week' => ['nullable', 'integer', 'between:0,6'],
            'day_of_month' => ['nullable', 'integer', 'between:1,31'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'enabled' => ['sometimes', 'boolean'],
            'file_paths' => ['sometimes', 'nullable', 'array'],
            'file_paths.*' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
