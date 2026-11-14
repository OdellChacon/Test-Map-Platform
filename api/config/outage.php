<?php

return [

    'max_report_duration_hours' => (int) env('OUTAGE_MAX_REPORT_DURATION_HOURS', 72),

    'max_active_reports_per_user' => (int) env('OUTAGE_MAX_ACTIVE_REPORTS_PER_USER', 3),

    'report_rate_limit' => [
        'attempts' => (int) env('OUTAGE_REPORT_RATE_LIMIT_ATTEMPTS', 5),
        'decay_minutes' => (int) env('OUTAGE_REPORT_RATE_LIMIT_DECAY_MINUTES', 60),
    ],

    'auth_rate_limit' => [
        'attempts' => (int) env('AUTH_RATE_LIMIT_ATTEMPTS', 5),
        'decay_minutes' => (int) env('AUTH_RATE_LIMIT_DECAY_MINUTES', 1),
    ],

    'public_rate_limit' => [
        'attempts' => (int) env('OUTAGE_PUBLIC_RATE_LIMIT_ATTEMPTS', 60),
        'decay_minutes' => (int) env('OUTAGE_PUBLIC_RATE_LIMIT_DECAY_MINUTES', 1),
    ],

    'max_bounding_box_span_degrees' => (float) env('OUTAGE_MAX_BOUNDING_BOX_SPAN_DEGREES', 60),

    'confirmation_rate_limit' => [
        'attempts' => (int) env('OUTAGE_CONFIRMATION_RATE_LIMIT_ATTEMPTS', 20),
        'decay_minutes' => (int) env('OUTAGE_CONFIRMATION_RATE_LIMIT_DECAY_MINUTES', 60),
    ],

    'report_mutation_rate_limit' => [
        'attempts' => (int) env('OUTAGE_REPORT_MUTATION_RATE_LIMIT_ATTEMPTS', 30),
        'decay_minutes' => (int) env('OUTAGE_REPORT_MUTATION_RATE_LIMIT_DECAY_MINUTES', 1),
    ],

];
