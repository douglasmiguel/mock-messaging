<?php

return [
    // All money values are stored in the smallest currency unit (pence for this demo).
    'delivery_fee' => (int) env('ORDER_DELIVERY_FEE', 299),
];
