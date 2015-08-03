<?php

Route::group(['prefix' => config('codex.base_route'), 'namespace' => 'Codex\Codex\Hooks\Github\Http\Controllers'], function(){

    Route::any('github-sync-webhook/{type}', [
        'as'   => 'codex.github-sync-webhook',
        #'before' => 'throttle',
        'uses' => 'GithubController@webhook'
    ]);

});
