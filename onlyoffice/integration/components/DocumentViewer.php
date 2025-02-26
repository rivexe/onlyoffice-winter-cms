<?php namespace Onlyoffice\Integration\Components;

use Cms\Classes\ComponentBase;
use Onlyoffice\Integration\Models\Settings;

class DocumentViewer extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Document Viewer',
            'description' => 'Отображает документ с помощью ONLYOFFICE Document Server'
        ];
    }

    public function defineProperties()
    {
        return [
            'documentUrl' => [
                'title'       => 'URL документа',
                'description' => 'URL-адрес документа для отображения',
                'type'        => 'string',
                'required'    => true,
            ],
            'mode' => [
                'title'       => 'Режим отображения',
                'description' => 'Выберите режим отображения документа',
                'type'        => 'dropdown',
                'options'     => [
                    'view'  => 'Просмотр',
                    'edit'  => 'Редактирование',
                    'embed' => 'Встроенный просмотр'
                ],
                'default'     => 'view'
            ]
        ];
    }

    public function onRun()
    {
        $settings = Settings::instance();
        
        if (empty($settings->server_url)) {
            trace_log('ONLYOFFICE Document Server не настроен');
            return;
        }
    
        // Базовая конфигурация
        $config = [
            'document' => [
                'url' => $this->property('documentUrl'),
                'fileType' => $this->getFileType(),
                'title' => basename($this->property('documentUrl')),
                'key' => md5($this->property('documentUrl') . time())
            ],
            'documentType' => $this->getDocumentType(),
            'height' => '100%',
            'width' => '100%',
            'type' => $this->property('mode'),
            'editorConfig' => [
                'mode' => $this->property('mode'),
                'lang' => 'ru',
                'callbackUrl' => url('/onlyoffice/callback')
            ]
        ];
    
        // Если есть JWT ключ, добавляем токен в конфигурацию
        if (!empty($settings->jwt_secret)) {
            $token = $settings->generateJWT($config);
            $config['token'] = $token;
        }
    
        $this->page['config'] = json_encode($config);
        $this->page['serverUrl'] = $settings->server_url;
        $this->addJs($settings->server_url . '/web-apps/apps/api/documents/api.js', [
            'async' => false,
            'defer' => false
        ]);
    }

    protected function getFileType()
    {
        $url = $this->property('documentUrl');
        return strtolower(pathinfo($url, PATHINFO_EXTENSION));
    }

    protected function getDocumentType()
    {
        $fileType = $this->getFileType();
        $textTypes = ['doc', 'docx', 'odt', 'rtf', 'txt'];
        $spreadsheetTypes = ['xls', 'xlsx', 'ods'];
        $presentationTypes = ['ppt', 'pptx', 'odp'];

        if (in_array($fileType, $textTypes)) return 'word';
        if (in_array($fileType, $spreadsheetTypes)) return 'cell';
        if (in_array($fileType, $presentationTypes)) return 'slide';
        
        return 'word';
    }
}