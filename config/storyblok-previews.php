<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Components
	|--------------------------------------------------------------------------
	|
	| Array of components to generate screenshots for. The key should match the
	| component name in Storyblok. Each component should have a selector to
	| target the component in the HTML. Optionally, you can specify a filename
	| for the screenshot, a URL to navigate to and a delay in milliseconds to
	| wait before taking the screenshot.
	|
	| If you leave the filename empty, the component name will be used.
	| If you don't specify a URL it will try and find a published page in
	| Storyblok that contains the component.
	|
	| Example:
	    [
            'hero' => [
                'selector' => '.hero',
            ],
            'grid' => [
                'delay' => 500,
                'filename' => 'grid.jpg',
                'selector' => ':has(> .grid),
                'url' => '/about',
            ],
        ];
	|
	*/
	'components' => [],

	/*
	|--------------------------------------------------------------------------
	| Base URL
	|--------------------------------------------------------------------------
	|
	| The base URL for your application. This is used to generate the preview
	| URLs, i.e https://uandus.co.uk/
	|
	*/
	'base_url' => '',

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
