<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\IosVersion;
use Illuminate\Http\Request;

class VersionController extends Controller
{
   public function getIosVersion()
{
    $version = IosVersion::where('platform', 'ios')
        ->orderByDesc('id')
        ->first();

    return response()->json([
        'status' => true,
        'message' => 'Latest iOS version fetched successfully.',
        'result' => [
            'platform' => $version?->platform ?? '',
            'version' => $version?->version ?? ''
        ]
    ]);
}
}
