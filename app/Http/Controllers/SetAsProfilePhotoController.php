<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetAsProfilePhotoController extends Controller
{
    /**
     * Handle the incoming request to set a media item as the user's profile photo.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request)
    {
        // Validate incoming media_id
        $data = $request->validate([
            'media_id' => ['required', 'integer', 'exists:media,id'],
        ]);

        $media = Auth::user()->media->findOrFail($data['media_id']);

        if ($media->is_profile_picture) {
            return redirect()->back()->with('info', 'This photo is already set as your profile picture!');
        }

        //remove the previous profile picture
        $previousProfilePicture = Auth::user()->media->where('is_profile_picture', true)->first();
        if ($previousProfilePicture) {
            $previousProfilePicture->update([
                'is_profile_picture' => false,
            ]);
        }

        $media->update([
            'is_profile_picture' => true,
        ]);

        // Redirect back with success message
        return back()->with('success', 'Profile photo updated successfully.');
    }
}
