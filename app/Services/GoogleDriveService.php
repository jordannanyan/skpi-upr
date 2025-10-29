<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Drive\DriveFile;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Storage;
use Exception;

class GoogleDriveService
{
	private $client;
	private $driveService;
	private $sheetsService;
	private $googleDriveConfig;

	public function __construct()
	{
		$this->googleDriveConfig = [
			'client_id' => env('GOOGLE_CLIENT_ID'),
			'client_secret' => env('GOOGLE_CLIENT_SECRET'),
			'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
			'drive_folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
			'spreadsheet_id' => env('GOOGLE_SPREADSHEET_ID'),
		];

		$this->client = $this->getClient();
		$this->driveService = new Drive($this->client);
		$this->sheetsService = new Sheets($this->client);
	}

	/**
	 * Get authenticated Google Client
	 */
	private function getClient()
	{
		$client = new Client();
		$client->setApplicationName('Laravel Google Drive Upload');
		$client->setScopes([
			Drive::DRIVE_FILE,
			Sheets::SPREADSHEETS
		]);
		$client->setAuthConfig([
			'client_id' => $this->googleDriveConfig['client_id'],
			'client_secret' => $this->googleDriveConfig['client_secret'],
			'redirect_uris' => [$this->googleDriveConfig['redirect_uri']],
		]);
		$client->setAccessType('offline');
		$client->setPrompt('select_account consent');

		// Load token from storage
		$tokenPath = storage_path('app/google-token.json');
		if (file_exists($tokenPath)) {
			$accessToken = json_decode(file_get_contents($tokenPath), true);
			$client->setAccessToken($accessToken);

			// Refresh token if expired
			if ($client->isAccessTokenExpired()) {
				if ($client->getRefreshToken()) {
					$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
					file_put_contents($tokenPath, json_encode($client->getAccessToken()));
				} else {
					throw new Exception('No refresh token available. Please re-authorize.');
				}
			}
		}
		// If token doesn't exist, that's OK - we're probably doing first-time auth

		return $client;
	}

	/**
	 * Get authorization URL
	 */
	public function getAuthUrl()
	{
		return $this->client->createAuthUrl();
	}

	/**
	 * Authenticate with authorization code
	 */
	public function authenticate($code)
	{
		$token = $this->client->fetchAccessTokenWithAuthCode($code);

		if (array_key_exists('error', $token)) {
			throw new Exception($token['error']);
		}

		$tokenPath = storage_path('app/google-token.json');
		file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));

		return true;
	}

	/**
	 * Upload file to Google Drive
	 */
	public function uploadFile($filePath, $fileName, $mimeType)
	{
		$fileMetadata = new DriveFile([
			'name' => $fileName,
			'parents' => [$this->googleDriveConfig['drive_folder_id']]
		]);

		$content = file_get_contents($filePath);

		$file = $this->driveService->files->create($fileMetadata, [
			'data' => $content,
			'mimeType' => $mimeType,
			'uploadType' => 'multipart',
			'fields' => 'id, name, webViewLink, webContentLink, size, createdTime'
		]);

		return $file;
	}

	/**
	 * Log upload to Google Sheets
	 */
	public function logToSheets($fileData, $description = '', $uploadedBy = '')
	{
		$values = [[
			now()->format('Y-m-d H:i:s'),
			$fileData->getName(),
			$fileData->getId(),
			$fileData->getWebViewLink(),
			$this->formatFileSize($fileData->getSize()),
			$description,
			$uploadedBy
		]];

		$body = new ValueRange([
			'values' => $values
		]);

		$params = [
			'valueInputOption' => 'RAW'
		];

		$result = $this->sheetsService->spreadsheets_values->append(
			$this->googleDriveConfig['spreadsheet_id'],
			'Sheet1!A:G',
			$body,
			$params
		);

		return $result;
	}

	/**
	 * Format file size
	 */
	private function formatFileSize($bytes)
	{
		if ($bytes >= 1073741824) {
			return number_format($bytes / 1073741824, 2) . ' GB';
		} elseif ($bytes >= 1048576) {
			return number_format($bytes / 1048576, 2) . ' MB';
		} elseif ($bytes >= 1024) {
			return number_format($bytes / 1024, 2) . ' KB';
		} else {
			return $bytes . ' bytes';
		}
	}

	/**
	 * Check if authenticated
	 */
	public function isAuthenticated()
	{
		$tokenPath = storage_path('app/google-token.json');
		return file_exists($tokenPath);
	}
}
