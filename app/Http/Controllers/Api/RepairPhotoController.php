<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repair;
use App\Support\RepairItemPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RepairPhotoController extends Controller
{
    /**
     * Upload or replace the receiving/item photo for an existing repair.
     *
     * Route:
     * POST /api/repairs/{repair}/item-photo
     * multipart field: item_photo
     */
    public function store(Request $request, Repair $repair): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Prevent one shop from changing another shop's repair photo.
        if (
            !$user->shop_id ||
            (int) $repair->shop_id !== (int) $user->shop_id
        ) {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to update this repair.',
            ], 403);
        }

        try {
            $repair = RepairItemPhoto::save($request, $repair);
            $repair = $repair->fresh()->load('customer');

            $payload = $repair->toArray();
            $payload['item_photo_url'] = $this->photoUrl($repair->item_photo);

            return response()->json([
                'status' => true,
                'message' => 'Receiving photo saved successfully.',
                'data' => $payload,
                'repair' => $payload,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to save the receiving photo.',
            ], 500);
        }
    }

    private function photoUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $storageUrl = Storage::disk('public')->url($path);

        if (preg_match('/^https?:\/\//i', $storageUrl)) {
            return $storageUrl;
        }

        return url($storageUrl);
    }
}
