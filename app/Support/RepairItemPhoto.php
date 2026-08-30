<?php

namespace App\Support;

use App\Models\Repair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepairItemPhoto
{
    /**
     * Validate and save the uploaded receiving photo for a repair.
     *
     * Call this AFTER the Repair model has been created. If your store()
     * method is wrapped in a DB transaction, keep this call inside it.
     */
    public static function save(Request $request, Repair $repair): Repair
    {
        $request->validate([
            'item_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        if (!$request->hasFile('item_photo')) {
            return $repair;
        }

        if (
            $repair->item_photo &&
            Storage::disk('public')->exists($repair->item_photo)
        ) {
            Storage::disk('public')->delete($repair->item_photo);
        }

        $path = $request
            ->file('item_photo')
            ->store('repairs/items', 'public');

        // forceFill avoids requiring item_photo in $fillable.
        $repair->forceFill([
            'item_photo' => $path,
        ])->save();

        return $repair->fresh();
    }
}
