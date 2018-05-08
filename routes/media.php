<?php

Route::group([
    'namespace' => 'Febalist\Laravel\Media',
    'prefix' => 'media',
], function () {
    Route::get('gallery/{ids}', 'MediaController@gallery')
        ->name('media.gallery');
    Route::get('zip/{ids}/{name}', 'MediaController@zip')
        ->name('media.zip');
});
