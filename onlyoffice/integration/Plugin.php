<?php namespace Onlyoffice\Integration;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'OnlyOffice',
            'description' => 'Интеграция с ONLYOFFICE Document Server',
            'author'      => 'Onlyoffice',
            'icon'        => 'icon-file-text'
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'ONLYOFFICE Settings',
                'description' => 'Управление настройками интеграции с ONLYOFFICE',
                'category'    => 'OnlyOffice',
                'icon'        => 'icon-cog',
                'class'       => 'Onlyoffice\Integration\Models\Settings',
                'order'       => 500,
                'keywords'    => 'onlyoffice document editor',
                'permissions' => ['onlyoffice.access_settings']
            ]
        ];
    }

    public function registerComponents()
    {
        return [
            'Onlyoffice\Integration\Components\DocumentViewer' => 'documentViewer'
        ];
    }

    public function boot()
    {
        $this->app['router']->group(['middleware' => []], function() {
            include __DIR__ . '/routes.php';
        });
    }
}