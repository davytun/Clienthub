<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessSettingsController extends Controller
{
    public function edit(): View
    {
        $business = auth()->user()->business;

        return view('settings.index', compact('business'));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isOwner(), 403);

        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'brand_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo'        => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        $business = auth()->user()->business;

        $data = [
            'name'        => $request->name,
            'brand_color' => $request->brand_color,
        ];

        if ($request->hasFile('logo')) {
            // Delete old logo if it exists
            if ($business->logo_path) {
                Storage::delete($business->logo_path);
            }

            $ext  = $request->file('logo')->getClientOriginalExtension();
            $path = "logos/{$business->id}.{$ext}";
            Storage::put($path, file_get_contents($request->file('logo')->getRealPath()));

            $data['logo_path'] = $path;
        }

        $business->update($data);

        return back()->with('success', 'Settings saved.');
    }

    /**
     * Stream the business logo (kept on private disk).
     */
    public function logo(): StreamedResponse
    {
        $business = auth()->user()->business;

        abort_unless($business->logo_path && Storage::exists($business->logo_path), 404);

        return Storage::response($business->logo_path);
    }
}
