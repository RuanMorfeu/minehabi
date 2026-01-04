<?php

namespace App\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait DownloadImageTrait
{
    /**
     * Download an image from URL and store it using Laravel Storage
     *
     * @param  string  $url  The URL of the image to download
     * @param  string|null  $name  Custom name for the file (optional)
     * @param  string  $directory  Directory to store the image (default: 'images')
     * @param  string  $disk  Storage disk to use (default: 'public')
     * @return string|null Returns the stored file path or null on failure
     */
    public function downloadImageFromUrl(
        string $url,
        ?string $name = null,
        string $directory = 'images',
        string $disk = 'public'
    ): ?string {
        try {
            $client = new Client;
            $response = $client->get($url);

            if ($response->getStatusCode() === 200) {
                $fileContent = $response->getBody();

                // Extract file information from URL
                $parsedUrl = parse_url($url);
                $pathInfo = pathinfo($parsedUrl['path']);

                // Generate filename
                $fileName = $name ?? (Str::slug($pathInfo['filename']) ?: 'image_'.time());
                $extension = $pathInfo['extension'] ?? $this->getExtensionFromMimeType($response->getHeaderLine('content-type'));

                // Build the complete file path
                $filePath = trim($directory, '/').'/'.$fileName.'.'.$extension;

                // Store the file
                Storage::disk($disk)->put($filePath, $fileContent);

                return $filePath;
            }

            return null;
        } catch (\Exception $e) {
            // Log the error if needed
            // \Log::error('Failed to download image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get file extension from mime type
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];

        return $mimeToExt[$mimeType] ?? 'jpg';
    }
}
