<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DeleteProfilePhotoController extends Controller
{
    /**
     * Handle the incoming request to set a media item as the user's profile photo.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(int $mediaId)
    {
        $media = Auth::user()->media->findOrFail($mediaId);

        if ($media->is_profile_picture) {
            return redirect()->back()->with('info', 'You cannot delete your profile picture!');
        }

        $media->delete();

        return redirect()->back()->with('success', 'Profile photo deleted successfully!');
    }
}
