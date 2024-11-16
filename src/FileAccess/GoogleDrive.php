<?php
namespace App\FileAccess;

use GuzzleHttp\Client;
use League\OAuth2\Client\Token\AccessToken;


class GoogleDrive
{
    private AccessToken $accessToken;

    public function __construct(private Client $http, private readonly Client $client)
    {

    }

    /**
     * @param array{
     *     scope:string,
     *     token_type:string,
     *     id_token:string,
     *     access_token:string,
     *     expires:int
     * } $accessTokenData
     * @return void
     */
    public function setAccessToken(array $accessTokenData): void
    {
        $this->accessToken = new AccessToken($accessTokenData);
    }


    /**
     * @return array<int, mixed>
     */
    public function listFiles(string $folderId = null): array
    {
        $url = 'https://www.googleapis.com/drive/v3/files';
        $query = "'me' in owners";
        if ($folderId) {
            $query .= " and '$folderId' in parents";
        }

        $response = $this->http->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken->getToken(),
            ],
            'query' => [
                'q' => $query,
                'fields' => 'files(id, name, mimeType, modifiedTime)',
            ],
        ]);

        /** @var array{files?: array<int, mixed>} $data */
        $data = (array) json_decode($response->getBody()->getContents(), true, JSON_THROW_ON_ERROR);

        return $data['files'] ?? [];
    }

    public function delete(string $fileId): bool
    {
        $url = sprintf('https://www.googleapis.com/drive/v3/files/%s', $fileId);

        $response = $this->client->delete($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken->getToken(),
            ],
        ]);

        // Return true if the deletion was successful (no errors thrown)
        return $response->getStatusCode() === 204;
    }

    /**
     * @return mixed[]
     */
    public function uploadFile(string $filePath, string $fileName, string $folderId = null): array
    {

        $url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart';

        $metadata = [
            'name' => $fileName,
            'parents' => [$folderId],
        ];

        $fileContent = file_get_contents($filePath);
        $boundary = '-------314159265358979323846';
        $body = "--$boundary\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . json_encode($metadata) . "\r\n"
            . "--$boundary\r\n"
            . "Content-Type: image/png\r\n\r\n"
            . $fileContent . "\r\n"
            . "--$boundary--";

        $response = $this->client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken->getToken(),
                'Content-Type' => "multipart/related; boundary=$boundary",
            ],
            'body' => $body,
        ]);

        return (array) json_decode($response->getBody()->getContents(), true, JSON_THROW_ON_ERROR);
    }

    /**
     * @return ?mixed[]
     */
    public function dirExists(string $folderName, ?string $parentFolderId = null): ?array
    {
        $url = 'https://www.googleapis.com/drive/v3/files';

        // Build the query string to search for folders
        $query = sprintf("name='%s' and mimeType='application/vnd.google-apps.folder'", $folderName);

        // If a parent folder ID is specified, add it to the query
        if ($parentFolderId) {
            $query .= sprintf(" and '%s' in parents", $parentFolderId);
        }

        $response = $this->client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken->getToken(),
            ],
            'query' => [
                'q' => $query,
                'fields' => 'files(id, name)',
            ],
        ]);

        /** @var array{files?: array<int, mixed>} $data */
        $data = json_decode($response->getBody()->getContents(), true, JSON_THROW_ON_ERROR);

        // Return the first matching folder or null if none exist
        return isset($data['files']) && count($data['files']) > 0 ? (array) $data['files'][0] : null;
    }

    /**
     * @return mixed[]
     */
    public function makeDir(string $dirname, ?string $parentFolderId = null): array
    {

        $url = 'https://www.googleapis.com/drive/v3/files';

        // Metadata for the folder creation
        $folderMetadata = [
            'name' => $dirname,
            'mimeType' => 'application/vnd.google-apps.folder',
        ];

        // If a parent folder is specified, set it
        if ($parentFolderId) {
            $folderMetadata['parents'] = [$parentFolderId];
        }

        $response = $this->client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken->getToken(),
                'Content-Type' => 'application/json',
            ],
            'json' => $folderMetadata,
        ]);

        return (array) json_decode($response->getBody()->getContents(), true, JSON_THROW_ON_ERROR);
    }

}
