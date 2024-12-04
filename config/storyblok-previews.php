<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Components
    |--------------------------------------------------------------------------
    |
    | Array of components to generate screenshots for. If you only pass a value
    | without any settings it will try and find a component on your website
    | with a CSS class matching the component name.
    |
    | For more control use an array with the component name as the key and
    | pass your settings as detailed below.
    | delay: optional, time in milliseconds to wait before taking the screenshot
    | filename: optional, the filename for the screenshot
    | selector: required, the CSS selector to target the component in the HTML
    | url: optional, the URL to navigate to before taking the screenshot
    | window_size: optional, the window size to use when taking the screenshot
    |
    | If you leave the filename empty, the component name will be used.
    | If you don't specify a URL it will try and find a page that uses the
    | component.
    | If you don't specify a window size it will use the default window size.
    |
    | Example:
        [
            'name',
            'hero' => [
                'selector' => '.hero',
            ],
            'grid' => [
                'delay' => 500,
                'filename' => 'grid.jpg',
                'selector' => ':has(> .grid),
                'url' => '/about',
                'window_size' => [500, 200],

            ],
        ];
    |
    */
    'components' => [
    ],

    /*
    |--------------------------------------------------------------------------
    | Allow only published stories
    |--------------------------------------------------------------------------
    |
    | If set to true, only published stories will be used to generate the
    | screenshots. If set to false it will use every story, including drafts.
    |
    */
    'only_published' => false,

    /*
    |--------------------------------------------------------------------------
    | Window Size (width, height)
    |--------------------------------------------------------------------------
    |
    | The window size to use when taking the screenshots, it can be overridden
    | by specifying a size in the component configuration.
    |
    */
    'default_window_size' => [1400, 1200],

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for your application. This is used to generate the preview
    | URLs, i.e https://uandus.co.uk/
    |
    */
    'base_url' => 'https://beautiful-tundra-y9wkj8wgzl.ploi.dev/',

    /*
    |--------------------------------------------------------------------------
    | Screenshot Storage Path
    |--------------------------------------------------------------------------
    |
    | The storage path for temporarily storing the screenshots.
    |
    */
    'storage_path' => '/storyblok/previews/',
    /*
    |--------------------------------------------------------------------------
    | Screenshot Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for the screenshot files.
    |
    */
    'prefix' => 'preview-',

    /*
    |--------------------------------------------------------------------------
    | Storyblok Asset Folder
    |--------------------------------------------------------------------------
    |
    | The Storyblok asset folder where images are uploaded to.
    |
    */
    'folder' => '_previews',
];
