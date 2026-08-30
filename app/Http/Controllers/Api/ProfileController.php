<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Upload / replace the authenticated user's profile photo.
     *
     * POST /api/profile/photo
     * multipart/form-data
     * field: profile_photo
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'profile_photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ], [
            'profile_photo.required' => 'Please select a profile photo.',
            'profile_photo.image' => 'The selected file must be an image.',
            'profile_photo.mimes' => 'Only JPG, JPEG, PNG and WEBP images are allowed.',
            'profile_photo.max' => 'The profile photo must not be larger than 5 MB.',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {
            $file = $request->file('profile_photo');

            $extension = strtolower(
                $file->getClientOriginalExtension()
                ?: $file->extension()
                ?: 'jpg'
            );

            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $extension = 'jpg';
            }

            $filename = sprintf(
                'user_%s_%s_%s.%s',
                $user->id,
                now()->format('YmdHis'),
                Str::lower(Str::random(10)),
                $extension
            );

            $newPath = $file->storeAs(
                'profile-images',
                $filename,
                'public'
            );

            if (!$newPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to save the profile photo.',
                ], 500);
            }

            $oldPath = $user->profile_photo;

            $user->profile_photo = $newPath;
            $user->save();

            // Delete the previous file only after the new file is safely saved.
            if (
                $oldPath &&
                $oldPath !== $newPath &&
                Storage::disk('public')->exists($oldPath)
            ) {
                Storage::disk('public')->delete($oldPath);
            }

            $freshUser = $user->fresh();

            if ($freshUser) {
                $freshUser->loadMissing('shop');
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully.',
                'data' => $freshUser,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to upload profile photo. Please try again.',
            ], 500);
        }
    }

    /**
     * Delete the authenticated user's profile photo.
     *
     * DELETE /api/profile/photo-delete
     */
    public function deletePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {
            $oldPath = $user->profile_photo;

            if (
                $oldPath &&
                Storage::disk('public')->exists($oldPath)
            ) {
                Storage::disk('public')->delete($oldPath);
            }

            $user->profile_photo = null;
            $user->save();

            $freshUser = $user->fresh();

            if ($freshUser) {
                $freshUser->loadMissing('shop');
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile photo removed successfully.',
                'data' => $freshUser,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete profile photo. Please try again.',
            ], 500);
        }
    }

    /**
     * Public image endpoint.
     *
     * GET /api/profile-images/{filename}
     *
     * The image is served directly from storage/app/public/profile-images.
     * This works even when public/storage symlink is unavailable.
     */
    public function showPhoto(string $filename): BinaryFileResponse
    {
        $safeFilename = basename($filename);

        // Prevent path traversal and invalid filenames.
        if ($safeFilename !== $filename) {
            abort(404);
        }

        $path = 'profile-images/' . $safeFilename;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('public')->path($path);

        $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
