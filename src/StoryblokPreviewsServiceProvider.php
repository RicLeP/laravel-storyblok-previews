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
    }

	/**
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__.'/../config/storyblok-previews.php', 'storyblok-previews');
	}
}
