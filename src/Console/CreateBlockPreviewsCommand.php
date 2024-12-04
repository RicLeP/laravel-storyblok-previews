<?php

namespace Riclep\StoryblokPreviews\Console;

use CURLFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Riclep\StoryblokCli\Endpoints\AssetFolders;
use Riclep\StoryblokCli\Endpoints\Components;
use Storyblok\ManagementClient;
use Wnx\SidecarBrowsershot\BrowsershotLambda;

class CreateBlockPreviewsCommand extends Command
{
    protected $signature = 'ls:make-previews {componentName?}';

    protected $description = 'Creates previews for components by capturing screenshots of the components on the live site.';

    protected ManagementClient $client;

    protected array $toCapture;

    public function __construct()
    {
        parent::__construct();
        $this->client = new ManagementClient(config('storyblok-cli.oauth_token'));
        $this->toCapture = config('storyblok-previews.components');
    }

    public function handle()
    {
        $capturedComponents = [];
        $uncapturedComponents = [];

        Storage::makeDirectory(config('storyblok-previews.storage_path'));

        $assetFolder = $this->getOrCreateAssetFolder();

        $componentName = $this->argument('componentName');

        // loop through all the components and update any that are just string values with the default selector
        foreach ($this->toCapture as $componentKey => $captureSettings) {
            if (is_string($captureSettings)) {
                unset($this->toCapture[$componentKey]);

                $this->toCapture[$captureSettings] = ['selector' => '.' . $captureSettings];

                ksort($this->toCapture);
            }
        }

        if ($componentName) {
            $this->processSingleComponent($componentName, $assetFolder, $capturedComponents, $uncapturedComponents);
        } else {
            $this->processAllComponents($assetFolder, $capturedComponents, $uncapturedComponents);
        }

        $this->displayResults($capturedComponents, $uncapturedComponents);
    }

    protected function getOrCreateAssetFolder()
    {
        $assetFolders = AssetFolders::make()->all()->getAssetFolders();
        $assetFolder = $assetFolders->where('name', config('storyblok-previews.folder'))->first();

        if (!$assetFolder) {
            $assetFolder = AssetFolders::make()->create([
                'asset_folder' => [
                    'name' => config('storyblok-previews.folder'),
                    'parent_id' => null,
                ],
            ])->getAssetFolder()->toArray();
        }

        return $assetFolder;
    }

    protected function processSingleComponent($componentName, $assetFolder, &$capturedComponents, &$uncapturedComponents)
    {
        if (array_key_exists($componentName, $this->toCapture) || in_array($componentName, $this->toCapture, true)) {
            $result = $this->captureComponent($componentName, $assetFolder);
            $capturedComponents = array_merge($capturedComponents, $result['captured']);
            $uncapturedComponents = array_merge($uncapturedComponents, $result['uncaptured']);
        } else {
            $this->error('Component not found in configuration: ' . $componentName);
        }
    }

    protected function processAllComponents($assetFolder, &$capturedComponents, &$uncapturedComponents)
    {
        foreach ($this->toCapture as $componentName => $captureSettings) {
            $result = $this->captureComponent($componentName, $assetFolder);
            $capturedComponents = array_merge($capturedComponents, $result['captured']);
            $uncapturedComponents = array_merge($uncapturedComponents, $result['uncaptured']);
        }
    }

    protected function displayResults($capturedComponents, $uncapturedComponents)
    {
        if (count($capturedComponents)) {
            $this->info('Captured components:');
            $this->table(['Component', 'Page', 'Selector', 'Image'], $capturedComponents, 'compact');
        }

        if (count($uncapturedComponents)) {
            $this->info('Uncaptured components:');
            $this->table(['Component', 'Page', 'Selector', 'Error'], $uncapturedComponents, 'compact');
        }
    }

    protected function captureComponent($componentName, $assetFolder)
    {
        $spaceId = config('storyblok-cli.space_id');
        $baseUrl = config('storyblok-previews.base_url');

        $captureSettings = $this->toCapture[$componentName];

        $capturedComponents = [];
        $uncapturedComponents = [];

        $component = $this->getComponentData($componentName);

        if ($component) {
            $componentPageUrl = $this->getComponentPageUrl($componentName, $captureSettings, $baseUrl, $spaceId);

            if (!$componentPageUrl) {
                $this->warn('No page found for component: ' . $componentName);
                return ['captured' => $capturedComponents, 'uncaptured' => $uncapturedComponents];
            }

            $filename = $this->getFilename($componentName, $captureSettings);

            try {
                $this->takeScreenshot($componentPageUrl, $captureSettings, $filename);
                $assetPreviewUrl = $this->uploadScreenshot($filename, $assetFolder, $spaceId);
                $this->updateComponentImage($component, $assetPreviewUrl, $spaceId);

                $capturedComponents[] = [
                    'component' => $componentName,
                    'page' => $componentPageUrl,
                    'selector' => $captureSettings['selector'],
                    'image' => 'https:' . $assetPreviewUrl,
                ];
            } catch (\Exception $e) {
                $uncapturedComponents[] = [
                    'component' => $componentName,
                    'page' => $componentPageUrl,
                    'selector' => $captureSettings['selector'],
                    'error' => $e->getMessage(),
                ];
            }
        } else {
            $uncapturedComponents[] = [
                'component' => $componentName,
                'selector' => $captureSettings['selector'],
                'page' => 'N/A',
                'error' => 'Component not found.',
            ];
        }

        return ['captured' => $capturedComponents, 'uncaptured' => $uncapturedComponents];
    }

    protected function getComponentData($componentName)
    {
        $componentsData = Components::make()->all()->getComponents();
        return $componentsData->where('name', $componentName)->first();
    }

    protected function getComponentPageUrl($componentName, $captureSettings, $baseUrl, $spaceId)
    {
        if (array_key_exists('url', $captureSettings)) {
            return $baseUrl . $captureSettings['url'];
        } else {
            $page = $this->client->get('spaces/' . $spaceId . '/stories/', [
                'contain_component' => $componentName,
                'is_published' => config('storyblok-previews.only_published'),
                'per_page' => 1,
            ])->getBody();

            if (!count($page['stories'])) {
                return null;
            } else {
                return $baseUrl . $page['stories'][0]['full_slug'];
            }
        }
    }

    protected function getFilename($componentName, $captureSettings)
    {
        if (array_key_exists('filename', $captureSettings)) {
            return config('storyblok-previews.prefix') . $captureSettings['filename'];
        } else {
            return config('storyblok-previews.prefix') . Str::slug($componentName) . '.jpg';
        }
    }

    protected function takeScreenshot($componentPageUrl, $captureSettings, $filename)
    {
        $windowSize = $captureSettings['window_size'] ?? config('storyblok-previews.default_window_size');

        BrowsershotLambda::url($componentPageUrl)
            ->setDelay($captureSettings['delay'] ?? 0)
            ->windowSize($windowSize[0], $windowSize[1])
            ->select($captureSettings['selector'])
            ->setScreenshotType('jpeg', 80)
            ->save(Storage::path(config('storyblok-previews.storage_path')) . $filename);
    }

    protected function uploadScreenshot($filename, $assetFolder, $spaceId)
    {
        $imageDimensions = getimagesize(Storage::path(config('storyblok-previews.storage_path')) . $filename);

        $fields = [
            'filename' => $filename,
            'asset_folder_id' => $assetFolder['id'],
            'size' => $imageDimensions[0] . 'x' . $imageDimensions[1],
        ];

        $signature = $this->client->post('spaces/' . $spaceId . '/assets', $fields)->getBody();

        $ch = curl_init($signature['post_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($signature['fields'], [
            'file' => new CurlFile(Storage::path(config('storyblok-previews.storage_path')) . $filename, $signature['fields']['Content-Type'], $filename),
        ]));
        $response = curl_exec($ch);

        $lines = collect(explode("\r\n", $response))->filter(fn($line) => str_starts_with($line, 'Location: '))->implode('');

        Storage::delete(config('storyblok-previews.storage_path') . $filename);

        return urldecode(str_replace('https://s3.amazonaws.com/', '//', substr($lines, 10)));
    }

    protected function updateComponentImage($component, $assetPreviewUrl, $spaceId)
    {
        $component['image'] = $assetPreviewUrl;
        Components::make()->update($component['id'], $component);

        if ($component['image']) {
            $oldPreviewIdentifier = collect(explode('/', $component['image']))->slice(-2)->implode('/');
            $oldPreviewAsset = $this->client->get('spaces/' . $spaceId . '/assets/', [
                'search' => $oldPreviewIdentifier,
            ])->getBody();

            if (count($oldPreviewAsset['assets'])) {
                // $this->client->delete('/spaces/' . $spaceId . '/assets/' . $oldPreviewAsset['assets'][0]['id'])->getBody(); // TODO not working - 404 error
            }
        }
    }
}
