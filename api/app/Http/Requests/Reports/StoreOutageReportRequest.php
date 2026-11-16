<?php

namespace App\Http\Requests\Reports;

use App\Enums\ReportDurationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOutageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['required', 'numeric', 'between:0.5,'.config('outage.max_report_radius_km')],
            'duration_type' => ['required', Rule::enum(ReportDurationType::class)],
            'duration_hours' => [
                Rule::requiredIf($this->input('duration_type') === ReportDurationType::Fixed->value),
                'nullable',
                'integer',
                'between:1,'.config('outage.max_report_duration_hours'),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $activeReports = $this->user()->outageReports()->active()->count();

            if ($activeReports >= config('outage.max_active_reports_per_user')) {
                $validator->errors()->add('reports', 'You have reached the maximum number of active outage reports allowed.');
            }
        });
    }
}
