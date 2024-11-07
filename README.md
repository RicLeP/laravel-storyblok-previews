# Laravel Storyblok - Artisan CLI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/riclep/laravel-storyblok-previews.svg?style=flat-square)](https://packagist.org/packages/riclep/laravel-storyblok-previews)
[![Total Downloads](https://img.shields.io/packagist/dt/riclep/laravel-storyblok-previews.svg?style=flat-square)](https://packagist.org/packages/riclep/laravel-storyblok-previews)
![GitHub Actions](https://github.com/riclep/laravel-storyblok-previews/actions/workflows/main.yml/badge.svg)

Artisan commands for working with the Storyblok API in Laravel.

## Installation

You can install the package via composer:

```bash
composer require riclep/laravel-storyblok-previews
```

Make sure you publish the config file with the following command:

```bash
php artisan vendor:publish --provider="Riclep\StoryblokPreviews\StoryblokPreviewsServiceProvider" --tag="config"
```

Please ensure you have configured the [Laravel Storyblok or Laravel Storyblok CLI](https://ls.sirric.co.uk) package before using this package.

You will also need to set up [Sidecar Browsershot](https://github.com/stefanzweifel/sidecar-browsershot).

If you would like to use a different screenshot driver, feel free to submit a PR.


## Usage

This is a package you may wish you install outside of your main project and use it simply to generate screenshots of 
your Storyblok components for various websites or projects but adjust the configuration to suit your needs.

To specify the components you would like to generate screenshots for modify your `storyblok-previews.php` config file.

Each component will need an entry in the `components` item. The item’s key should match the component name in Storyblok. 
Each component should have a selector to target the component in the HTML. Optionally, you can specify a filename for 
the screenshot, a URL to navigate to and a delay in milliseconds to wait before taking the screenshot. If you leave the 
filename empty, the component name will be used. If you don't specify a URL it will try and find a published page in 
Storyblok that contains the component.

Example:

```php
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
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email ric@sirric.co.uk instead of using the issue tracker.

## Credits

-   [Richard Le Poidevin](https://github.com/riclep)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
