<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Media;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Traits\UtilsPhotoConverter;

class MediaController extends Controller
{
    use UtilsPhotoConverter;
    /**
     * Display a listing of the media items for the authenticated user.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = Auth::user();
        /** @var \App\Models\Media $user */
        $media = Media::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Media $item) => [
                'id'              => $item->id,
                'url'             => $this->convertToWebp($item->file_path),
                'collection_name' => $item->collection_name,
                'mime_type'       => $item->mime_type,
                'is_profile'      => $item->is_profile_picture,
            ]);

        return Inertia::render('media/Index', [
            'media' => $media,
        ]);
    }

    /**
     * Store newly uploaded media items for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'collection_name' => ['required', 'string', 'max:255'],
            'images.*'        => ['required', 'mimes:jpg,png,jpeg,webp', 'max:5000'],
        ], [
            'images.*.mimes'  => 'Each file must be a jpg, png, jpeg, or webp image.',
            'images.*.max'    => 'Each image must be at most 5MB.',
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('images', 'public');
                /** @var \App\Models\Media $user */
                $user->media()->create([
                    'file_path'           => $path,
                    'file_name'           => $file->getClientOriginalName(),
                    'mime_type'           => $file->getClientMimeType(),
                    'size'                => $file->getSize(),
                    'is_profile_picture'  => false,
                    'collection_name'     => $request->input('collection_name'),
                ]);
            }
        }

        return redirect()
            ->back()
            ->with('success', 'Images uploaded successfully.');
    }
}
