<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Z-Index for sticky columns
    |--------------------------------------------------------------------------
    |
    | Sticky cells need a z-index so they render on top of scrolling content.
    | Filament overlays (modals, dropdowns) use much higher values, so 10–20
    | is safe for table cells. Header cells automatically get +10 on top of this.
    |
    */
    'z_index' => 10,

    /*
    |--------------------------------------------------------------------------
    | Background Colour
    |--------------------------------------------------------------------------
    |
    | Sticky cells must have an opaque background or scrolling content bleeds
    | through. 'auto' means the package resolves the correct Filament panel
    | surface colour at runtime. You can override with any CSS value:
    |
    |   'background' => '#ffffff',
    |   'background' => 'rgb(249 250 251)',
    |
    */
    'background' => 'auto',

    /*
    |--------------------------------------------------------------------------
    | Scroll Shadow
    |--------------------------------------------------------------------------
    |
    | Show a directional box-shadow on sticky columns when content is scrolled
    | beneath them, giving users a visual cue.
    |
    */
    'shadow' => true,

    /*
    |--------------------------------------------------------------------------
    | Shadow Colour
    |--------------------------------------------------------------------------
    |
    | CSS colour for the scroll shadow. Semi-transparent black works well
    | in both light and dark modes. Dark mode overrides this automatically
    | to a stronger value.
    |
    */
    'shadow_color' => 'rgba(0, 0, 0, 0.12)',
];
