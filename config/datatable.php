<?php

/**
 * DataTable Configuration
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    | Available: 'bootstrap', 'tailwind'
    |--------------------------------------------------------------------------
    */
    'theme' => 'bootstrap',

    /*
    |--------------------------------------------------------------------------
    | Theme Configurations
    |--------------------------------------------------------------------------
    */
    'themes' => [
        'bootstrap' => [
            'class' => 'table table-striped table-bordered',
            'pagination' => 'datatable::themes.bootstrap.pagination',
        ],
        'tailwind' => [
            'class' => 'min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg',
            'pagination' => 'datatable::themes.tailwind.pagination',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Mode
    |--------------------------------------------------------------------------
    | Available: 'pagination', 'load-more'
    |--------------------------------------------------------------------------
    */
    'pagination_mode' => 'pagination',

    /*
    |--------------------------------------------------------------------------
    | Pagination Configurations
    |--------------------------------------------------------------------------
    */
    'paginations' => [
        'pagination' => [   //.. Pagination Mode
            /*
             * Default Items Per Page
             */
            'per_page' => 10,

            /*
             * Per Page Options
             */
            'per_page_options' => [
                5   => '5 per page',
                10  => '10 per page',
                20  => '20 per page',
                50  => '50 per page',
                75  => '75 per page',
                100 => '100 per page'
            ],
        ],

        'load-more' => [    //.. Load More Mode
            /*
             * Default Items Per Load More
             */
            'per_page' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Box
    |--------------------------------------------------------------------------
    */
    'search' => [
        'show' => true,
        'placeholder' => 'Search...',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reset Button
    |--------------------------------------------------------------------------
    */
    'reset' => [
        'show' => true,
        'label' => 'Reset',
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Column on Table Headers Row
    |--------------------------------------------------------------------------
    */
    'action' => [
        'show' => true,
        'label' => 'Action',
    ],
];
