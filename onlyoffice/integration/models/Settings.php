<?php namespace Onlyoffice\Integration\Models;

use Model;
use ValidationException;
use Firebase\JWT\JWT;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];
    public $settingsCode = 'onlyoffice_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {
        $this->server_url = '';
        $this->jwt_secret = '';
        $this->jwt_header = 'Authorization';
    }

    public function beforeValidate()
    {
        // Проверка URL сервера
        if (!filter_var($this->server_url, FILTER_VALIDATE_URL)) {
            throw new ValidationException(['server_url' => 'Некорректный URL-адрес сервера']);
        }

        // Если указан JWT-ключ, проверяем его
        if (!empty($this->jwt_secret)) {
            try {
                // Пробуем создать тестовый токен
                $token = JWT::encode(['test' => true], $this->jwt_secret, 'HS256');
            } catch (\Exception $e) {
                throw new ValidationException(['jwt_secret' => 'Некорректный JWT-ключ']);
            }
        }

        // Проверка заголовка
        if (!empty($this->jwt_header)) {
            if (!preg_match('/^[a-zA-Z0-9-]+$/', $this->jwt_header)) {
                throw new ValidationException(['jwt_header' => 'Некорректное название заголовка']);
            }
        }
    }

    public function generateJWT($payload)
    {
        if (empty($this->jwt_secret)) {
            return null;
        }

        return JWT::encode($payload, $this->jwt_secret, 'HS256');
    }
}