<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Carton label
    |--------------------------------------------------------------------------
    |
    | Fixed text printed on the carton label made by the package station.
    |
    */

    'label' => [
        'company' => env('PACKAGING_LABEL_COMPANY', 'Remark'),
        'group' => env('PACKAGING_LABEL_GROUP', 'Remark-Herlan Group'),
        'from' => env('PACKAGING_LABEL_FROM', 'Munshiganj, Gazaria, Remark HB Headquarters'),
        'route' => env('PACKAGING_LABEL_ROUTE'),
    ],

];
