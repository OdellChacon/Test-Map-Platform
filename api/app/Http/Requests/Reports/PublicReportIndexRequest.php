<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PublicReportIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'south_west_lat' => ['required', 'numeric', 'between:-90,90'],
            'south_west_lng' => ['required', 'numeric', 'between:-180,180'],
            'north_east_lat' => ['required', 'numeric', 'between:-90,90'],
            'north_east_lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $maxSpan = config('outage.max_bounding_box_span_degrees');
            $latSpan = abs($this->input('north_east_lat') - $this->input('south_west_lat'));
            $lngSpan = abs($this->input('north_east_lng') - $this->input('south_west_lng'));

            if ($latSpan > $maxSpan || $lngSpan > $maxSpan) {
                $validator->errors()->add('bounding_box', 'The requested area is too large.');
            }
        });
    }
}
