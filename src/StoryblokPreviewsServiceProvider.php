<?php

namespace Riclep\StoryblokPreviews;

use Illuminate\Support\ServiceProvider;
use Riclep\StoryblokPreviews\Console\CreateBlockPreviewsCommand;

class StoryblokPreviewsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
		$this->commands([
            CreateBlockPreviewsCommand::class,
		]);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/storyblok-previews.php' => config_path('storyblok-previews.php'),
            ], 'storyblok-previews');
        }
    }

	/**
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__.'/../config/storyblok-previews.php', 'storyblok-previews');
	}
}
