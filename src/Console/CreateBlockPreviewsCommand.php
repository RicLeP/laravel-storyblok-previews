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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ls:make-previews';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates previews for components by capturing screenshots of the components on the live site.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = new ManagementClient(config('storyblok-cli.oauth_token'));

        // TODO - set view port size? per component?
        $toCapture = config('storyblok-previews.components');
        $spaceId = config('storyblok-cli.space_id');
        $baseUrl = config('storyblok-previews.base_url');
        $screenshotPath = config('storyblok-previews.storage_path');
        $screenshotPrefix = config('storyblok-previews.prefix');
        $screenshotAssetFolder = config('storyblok-previews.folder');

        $capturedComponents = [];
        $uncapturedComponents = [];

        Storage::makeDirectory($screenshotPath);

        $componentsData = Components::make()
            ->all()
            ->getComponents();

        $assetFolders = AssetFolders::make()
            ->all()
            ->getAssetFolders();
        $assetFolder = $assetFolders->where('name', $screenshotAssetFolder)->first();

        if (!$assetFolder) {
            $assetFolder = AssetFolders::make()->create([
                'asset_folder' => [
                    'name' => $screenshotAssetFolder,
                    'parent_id' => null,
                ],
            ])->getAssetFolder()->toArray();
        }

        foreach ($toCapture as $componentName => $captureSettings) {
            $component = $componentsData->where('name', $componentName)->first();

            if ($component) {
                if (array_key_exists('url', $captureSettings)) {
                    $componentPageUrl = $baseUrl . $captureSettings['url'];
                } else {
                    $page = $client->get('spaces/' . $spaceId . '/stories/', [
                        'contain_component' => $componentName,
                        'is_published' => true,
                        'per_page' => 1,
                    ])->getBody();

                    $componentPageUrl = $baseUrl . $page['stories'][0]['full_slug'];
                }

                if (array_key_exists('filename', $captureSettings)) {
                    $filename = $screenshotPrefix . $captureSettings['filename'];
                } else {
                    $filename = $screenshotPrefix . Str::slug($componentName) . '.jpg';
                }

                try {
                    BrowsershotLambda::url($componentPageUrl)
                        ->setDelay($captureSettings['delay'] ?? 0)
                        ->select($captureSettings['selector'])
                        ->setScreenshotType('jpeg', 80)
                        ->save(Storage::path($screenshotPath) . $filename);

                    $imageDimensions = getimagesize(Storage::path($screenshotPath) . $filename);

                    $fields = [
                        'filename' => $filename,
                        'asset_folder_id' => $assetFolder['id'],
                        'size' => $imageDimensions[0] . 'x' . $imageDimensions[1],
                    ];

                    if ($component['image']) {
                        $oldPreviewIdentifier = collect(explode('/', $component['image']))->slice(-2)->implode('/');
                    } else {
                        $oldPreviewIdentifier = null;
                    }

                    $signature = $client->post('spaces/' . $spaceId . '/assets', $fields)->getBody();

                    $ch = curl_init($signature['post_url']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HEADER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($signature['fields'], [
                        'file' => new CurlFile(Storage::path($screenshotPath) . $filename, $signature['fields']['Content-Type'], $filename),
                    ]));
                    $response = curl_exec($ch);

                    $lines = collect(explode("\r\n", $response))->filter(fn($line) => str_starts_with($line, 'Location: '))->implode('');

                    $assetPreviewUrl =  urldecode(str_replace('https://s3.amazonaws.com/', '//', substr($lines, 10)));

                    $component['image'] = $assetPreviewUrl;

                    Components::make()->update($component['id'], $component);

                    Storage::delete($screenshotPath . $filename);

                    if ($oldPreviewIdentifier) {
                        $oldPreviewAsset = $client->get('spaces/' . $spaceId . '/assets/', [
                            'search' => $oldPreviewIdentifier,
                        ])->getBody();

                        if (count($oldPreviewAsset['assets'])) {
                          //  $client->delete('/spaces/' . $spaceId . '/assets/' . $oldPreviewAsset['assets'][0]['id'])->getBody(); // TODO not working - 404 error
                        }
                    }

                    $capturedComponents[] = [
                        'component' => $componentName,
                        'page' => $componentPageUrl,
                        'selector' => $captureSettings['selector'],
                        'image' => $assetPreviewUrl,
                    ];

                } catch (\Exception $e) {
                   // $this->error('Error capturing: ' . $componentName . ' - selector: ' . $captureSettings['selector']);
                   // $this->error($e->getMessage());

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
        }

        if (count($capturedComponents)) {
            $this->info('Captured components:');
            $this->table(['Component', 'Page', 'Selector', 'Image'], $capturedComponents, 'compact');
        }

        if (count($uncapturedComponents)) {
            $this->info('Uncaptured components:');
            $this->table(['Component', 'Page', 'Selector', 'Error'], $uncapturedComponents, 'compact');
        }
    }
}
