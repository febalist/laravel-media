<?php

Route::group([
    'middleware' => ['bindings'],
    'namespace' => 'Febalist\Laravel\Media',
    'prefix' => 'media',
    'as' => 'media.',
], function () {
    Route::get('redirect/{media}/{conversion?}', 'MediaController@redirect')->name('redirect');
    Route::get('download/{media}/{conversion?}', 'MediaController@download')->name('download');
    Route::get('stream/{media}/{conversion?}', 'MediaController@stream')->name('stream');
    Route::get('view/{media}/{conversion?}', 'MediaController@view')->name('view');

    Route::get('gallery/{ids}', 'MediaController@gallery')->name('gallery');
    Route::get('zip/{ids}/{name}', 'MediaController@zip')->name('zip');
    Route::post('upload', 'MediaController@upload')->name('upload');
});
