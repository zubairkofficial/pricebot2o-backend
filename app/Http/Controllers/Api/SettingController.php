<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LogoSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class SettingController extends Controller
{
    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
        ]);

        $user = Auth::user();
        $logoSetting = LogoSetting::firstOrCreate(['user_id' => $user->id]);

        if ($request->hasFile('logo')) {
            // Delete the old logo if it exists
            if ($logoSetting->logo) {
                Storage::disk('public')->delete($logoSetting->logo);
            }
            $filename = Carbon::now()->timestamp.'.'.$request->file('logo')->getClientOriginalExtension();
            $logoPath = $request->file('logo')->storeAs('logos', $filename);
            $logoSetting->logo = $logoPath;
        } else {
            $logoSetting->logo = null;
        }

        $logoSetting->save();

        return response()->json(['message' => 'Logo updated successfully', 'logo' => $logoSetting->logo], 200);
    }


    public function fetchLogo()
    {
        $user = Auth::user();
        $logoSetting = LogoSetting::where('user_id', $user->id)->first();

        if ($logoSetting && $logoSetting->logo) {
            return response()->json(['logo' => $logoSetting->logo], 200);
        }

        return response()->json(['message' => 'No logo found'], 404);
    }
}

