<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\File\File;
use Psr\Log\LoggerInterface;

class CloudStorageService
{
    private $client;
    private $logger;
    private $provider;
    private $accessToken;
    private $uploadPath;

    public function __construct(LoggerInterface $logger, string $cloudProvider = null, string $cloudAccessToken = null, string $uploadPath = null)
    {
        $this->logger = $logger;
        $this->client = HttpClient::create();
        $this->provider = $cloudProvider ?: $_ENV['CLOUD_STORAGE_PROVIDER'] ?? 'onedrive';
        $this->accessToken = $cloudAccessToken ?: $_ENV['CLOUD_STORAGE_ACCESS_TOKEN'] ?? null;
        $this->uploadPath = $uploadPath ?: $_ENV['CLOUD_UPLOAD_PATH'] ?? '/InventoryApp/SupportTickets';
    }

    public function uploadTicket(array $ticketData): array
    {
        try {
            $filename = 'support-ticket-' . $ticketData['ticket_id'] . '-' . date('Y-m-d-H-i-s') . '.json';
            $jsonContent = json_encode($ticketData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if ($this->provider === 'onedrive') {
                return $this->uploadToOneDrive($filename, $jsonContent);
            } elseif ($this->provider === 'dropbox') {
                return $this->uploadToDropbox($filename, $jsonContent);
            } else {
 
                return $this->uploadToLocal($filename, $jsonContent);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload support ticket: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function uploadToOneDrive(string $filename, string $content): array
    {
        if (!$this->accessToken) {
            throw new \Exception('OneDrive access token not configured');
        }

        $uploadUrl = 'https://graph.microsoft.com/v1.0/me/drive/root:' . $this->uploadPath . '/' . $filename . ':/content';

        $response = $this->client->request('PUT', $uploadUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
            'body' => $content,
        ]);

        if ($response->getStatusCode() === 201) {
            $data = $response->toArray();
            return [
                'success' => true,
                'file_id' => $data['id'],
                'download_url' => $data['@microsoft.graph.downloadUrl'] ?? null,
                'web_url' => $data['webUrl'] ?? null,
            ];
        }

        throw new \Exception('OneDrive upload failed: ' . $response->getContent(false));
    }

    private function uploadToDropbox(string $filename, string $content): array
    {
        if (!$this->accessToken) {
            throw new \Exception('Dropbox access token not configured');
        }

        $uploadUrl = 'https://content.dropboxapi.com/2/files/upload';
        $path = $this->uploadPath . '/' . $filename;

        $response = $this->client->request('POST', $uploadUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/octet-stream',
                'Dropbox-API-Arg' => json_encode([
                    'path' => $path,
                    'mode' => 'add',
                    'autorename' => true,
                ]),
            ],
            'body' => $content,
        ]);

        if ($response->getStatusCode() === 200) {
            $data = $response->toArray();
            return [
                'success' => true,
                'file_id' => $data['id'],
                'path_lower' => $data['path_lower'],
                'name' => $data['name'],
            ];
        }

        throw new \Exception('Dropbox upload failed: ' . $response->getContent(false));
    }

    private function uploadToLocal(string $filename, string $content): array
    {

        $uploadDir = __DIR__ . '/../../public/uploads/support-tickets';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filepath = $uploadDir . '/' . $filename;
        
        if (file_put_contents($filepath, $content)) {
            return [
                'success' => true,
                'file_path' => $filepath,
                'public_url' => '/uploads/support-tickets/' . $filename,
                'method' => 'local',
            ];
        }

        throw new \Exception('Local upload failed');
    }

    public function createShareLink(string $fileId): ?string
    {
        try {
            if ($this->provider === 'onedrive') {
                return $this->createOneDriveShareLink($fileId);
            } elseif ($this->provider === 'dropbox') {
                return $this->createDropboxShareLink($fileId);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to create share link: ' . $e->getMessage());
        }

        return null;
    }

    private function createOneDriveShareLink(string $fileId): string
    {
        $url = 'https://graph.microsoft.com/v1.0/me/drive/items/' . $fileId . '/createLink';
        
        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'type' => 'view',
                'scope' => 'anonymous',
            ],
        ]);

        if ($response->getStatusCode() === 200) {
            $data = $response->toArray();
            return $data['link']['webUrl'];
        }

        throw new \Exception('Failed to create OneDrive share link');
    }

    private function createDropboxShareLink(string $fileId): string
    {
        $url = 'https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings';
        
        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'path' => $fileId,
                'settings' => [
                    'requested_visibility' => 'public',
                ],
            ],
        ]);

        if ($response->getStatusCode() === 200) {
            $data = $response->toArray();
            return $data['url'];
        }

        throw new \Exception('Failed to create Dropbox share link');
    }
}
