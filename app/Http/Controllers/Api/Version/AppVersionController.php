<?php

namespace App\Http\Controllers\Api\Version;

use App\Models\AppVersion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AppVersionController extends Controller
{
    /**
     * Return the latest version info for a given platform.
     */
    public function check(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:android,ios',
            'version'  => 'required|string',
        ]);

        $platform = strtolower($request->platform);
        $currentVersion = $request->version;

        $latestVersion = AppVersion::where('platform', $platform)
            ->orderByDesc('created_at')
            ->first();

        if (!$latestVersion) {
            return response()->json([
                'status' => false,
                'message' => 'No version info found for this platform.',
            ], 404);
        }

        // Compare versions
        $forceUpdate = version_compare($currentVersion, $latestVersion->version_name, '<');

        return response()->json([
            'platform' => $latestVersion->platform,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion->version_name,
            'force_update' => $forceUpdate,
            'download_url' => $latestVersion->download_url,
        ]);
    }


    public function updateAppVersion(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:ios,android',
            'version'  => 'required|string',
            'force_update' => 'required|boolean',
            'release_notes' => 'required|string',
            'download_url' => 'required|url',
        ]);

        // Delete old version for the same platform
        AppVersion::where('platform', $request->platform)->delete();

        // Create new version
        $appVersion = AppVersion::create([
            'platform' => $request->platform,
            'version'  => $request->version,
            'version_name'  => $request->version,
            'force_update' => $request->force_update,
            'release_notes' => $request->release_notes,
            'download_url' => $request->download_url,
        ]);

        return response()->json([
            'message' => ucfirst($request->platform) . ' version updated successfully.',
            'data' => $appVersion,
        ]);
    }
}
