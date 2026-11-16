<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    //return view('welcome');
    return redirect()->route('user-login');
});

foreach (glob(__DIR__ . '/web-routes/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/public-routes/*.php') as $file) {
    require_once $file;
}

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'index')->name('user-login');
    Route::post('/user-authentication', 'authenticationApi')->name('authenticationApi');
    Route::get('/logout', 'logout')->name('api.logout');
});



Route::get('get-log', function (\Illuminate\Http\Request $request) {
    // 1. SET YOUR CREDENTIALS HERE
    $username = 'oxion';
    $password = '12345';

    // 2. CHECK FOR AUTHENTICATION
    if ($request->getUser() !== $username || $request->getPassword() !== $password) {
        return response('Unauthorized.', 401, [
            'WWW-Authenticate' => 'Basic realm="Log Viewer"',
        ]);
    }

    $path = $request->get('path', '');
    $fullpath = storage_path('logs/' . $path);

    if (is_dir($fullpath)) {
        $files = array_diff(scandir($fullpath), array('.', '..'));
        $html = '<h3>Log Files</h3>';
        foreach ($files as $file) {
            $dw_file = url('get-log?path=' . $path . ($path ? '/' : '') . $file);
            $html .= '<a href="' . $dw_file . '" target="_blank">' . $file . '</a><br>';
        }
        return response($html);
    }

    if (file_exists($fullpath)) {
        $file = fopen($fullpath, "r");
        while (!feof($file)) {
            echo fgets($file) . "<br>";
        }
        fclose($file);
        exit();
    }

    return "File not found.";
});