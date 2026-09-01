<?php

return [
    'air_hours' => (int) env('ALERTS_AIR_HOURS', 24),
    'sea_days' => (int) env('ALERTS_SEA_DAYS', 3),
    'check_every_minutes' => (int) env('ALERTS_CHECK_EVERY_MINUTES', 15),
    'warehouse_statuses' => ['RECEIVED_MIAMI', 'IN_WAREHOUSE_NIC'],
];
