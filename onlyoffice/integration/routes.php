<?php

Route::post('onlyoffice/callback', function() {
    $handler = new \Onlyoffice\Integration\Handlers\Callback();
    return $handler->handle();
});