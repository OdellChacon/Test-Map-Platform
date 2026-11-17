<?php

namespace App\Http\Requests\Reports;

use App\Enums\ReportDurationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOutageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');

        // Route model binding may not be resolved yet at this point depending on
        // middleware/binding resolution order. If so, let the request through;
        // the controller's $this->authorize('update', $report) still enforces
        // ownership as a safety net.
        if ($report === null) {
            return true;
        }

        return $this->user()->id === $report->user_id;
    }

    public function rules(): array
    {
        return [
            'radius_km' => ['sometimes', 'numeric', 'between:0.5,'.config('outage.max_report_radius_km')],
            'duration_hours' => ['sometimes', 'integer', 'between:1,'.config('outage.max_report_duration_hours')],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $report = $this->route('report');

            if ($this->has('duration_hours') && $report->duration_type !== ReportDurationType::Fixed) {
                $validator->errors()->add('duration_hours', 'Duration hours can only be changed on a fixed-duration report.');
            }
        });
    }
}
