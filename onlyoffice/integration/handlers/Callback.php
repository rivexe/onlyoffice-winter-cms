<?php namespace Onlyoffice\Integration\Handlers;

use Input;
use Request;
use Response;
use Exception;
use Log;
use Onlyoffice\Integration\Models\Settings;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Callback
{
    protected $settings;
    protected $data;

    public function __construct()
    {
        $this->settings = Settings::instance();
        $this->data = Input::all();
    }

    public function handle()
    {
        try {
            Log::info('ONLYOFFICE Callback received', $this->data);

            $this->validateToken();

            return $this->processStatus();
        } catch (Exception $e) {
            Log::error('ONLYOFFICE Callback error', [
                'error' => $e->getMessage(),
                'data' => $this->data
            ]);

            return Response::make([
                'error' => 1,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function validateToken()
    {
        if (empty($this->settings->jwt_secret)) {
            return;
        }

        $token = Request::header($this->settings->jwt_header);
        if (empty($token)) {
            throw new Exception('JWT token required');
        }

        try {
            JWT::decode($token, new Key($this->settings->jwt_secret, 'HS256'));
        } catch (Exception $e) {
            throw new Exception('Invalid JWT token: ' . $e->getMessage());
        }
    }

    protected function processStatus()
    {
        switch ($this->data['status']) {
            case 0:
                return $this->handleNotLoaded();
            case 1:
                return $this->handleLoaded();
            case 2:
                return $this->handleReady();
            case 3:
                return $this->handleConversionFailed();
            case 4:
                return $this->handleSaveFailed();
            default:
                return $this->handleUnknownStatus();
        }
    }

    protected function handleNotLoaded()
    {
        Log::info('ONLYOFFICE: Document not loaded yet');
        return Response::make(['error' => 0]);
    }

    protected function handleLoaded()
    {
        Log::info('ONLYOFFICE: Document loaded successfully');
        return Response::make(['error' => 0]);
    }

    protected function handleReady()
    {
        try {
            $url = $this->data['url'];
            if (empty($url)) {
                throw new Exception('No document URL provided');
            }

            $downloadedContent = file_get_contents($url);
            if ($downloadedContent === false) {
                throw new Exception('Failed to download document from ONLYOFFICE');
            }

            $originalPath = str_replace(url('/'), base_path(), $this->data['key']);
            
            if (file_put_contents($originalPath, $downloadedContent) === false) {
                throw new Exception('Failed to save document to disk');
            }

            Log::info('ONLYOFFICE: Document saved successfully', [
                'path' => $originalPath
            ]);

            return Response::make(['error' => 0]);

        } catch (Exception $e) {
            Log::error('ONLYOFFICE: Document save error', [
                'error' => $e->getMessage(),
                'data' => $this->data
            ]);

            return Response::make([
                'error' => 1,
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function handleConversionFailed()
    {
        Log::error('ONLYOFFICE: Document conversion failed', $this->data);
        return Response::make([
            'error' => 1,
            'message' => 'Document conversion failed'
        ]);
    }

    protected function handleSaveFailed()
    {
        Log::error('ONLYOFFICE: Document save failed', $this->data);
        return Response::make([
            'error' => 1,
            'message' => 'Document save failed'
        ]);
    }

    protected function handleUnknownStatus()
    {
        Log::warning('ONLYOFFICE: Unknown status received', [
            'status' => $this->data['status']
        ]);
        return Response::make([
            'error' => 1,
            'message' => 'Unknown status'
        ]);
    }
}