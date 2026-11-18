<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $r)
    {
        return view('settings.index', ['settings' => $r->user()->setting]);
    }

    public function update(Request $r)
    {
        $r->validate(['music_enabled' => 'sometimes|boolean', 'sound_enabled' => 'sometimes|boolean', 'reduced_motion' => 'sometimes|boolean']);
        $r->user()->setting()->updateOrCreate([], ['music_enabled' => $r->boolean('music_enabled'), 'sound_enabled' => $r->boolean('sound_enabled'), 'reduced_motion' => $r->boolean('reduced_motion')]);

        return back()->with('status', 'Pengaturan tersimpan.');
    }

    public function profile(Request $r)
    {
        return view('settings.profile');
    }

    public function updateProfile(Request $r)
    {
        $data = $r->validate(['display_name' => 'required|string|max:40']);
        $r->user()->update($data);

        return back()->with('status', 'Profil diperbarui.');
    }
}
