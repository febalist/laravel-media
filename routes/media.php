<?php

Route::group([
    'namespace' => 'Febalist\Laravel\Media',
    'prefix' => 'media',
], function () {
    Route::get('{id}/{filename}', 'MediaController@download')->name('media.download');
});
