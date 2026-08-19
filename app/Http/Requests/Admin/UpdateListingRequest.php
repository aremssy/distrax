<?php

namespace App\Http\Requests\Admin;

class UpdateListingRequest extends StoreListingRequest
{
    /**
     * Authorization is enforced by the route's `permission:listings.edit` middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the custom validation messages for this request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'zone_id.required' => 'Please choose the zone this property is in.',
            'zone_id.exists' => 'The selected zone no longer exists. Please pick another.',
            'owner_id.required' => 'Please choose the owner of this listing.',
            'owner_id.exists' => 'The selected owner no longer exists. Please pick another.',
            'type.required' => 'Please choose a listing type.',
            'type.in' => 'Please choose a valid listing type from the list.',
            'title.required' => 'Please enter a title for this listing.',
            'title.max' => 'The title cannot be longer than 255 characters.',
            'language_tag.required' => 'Please choose the language for this listing.',
            'price.required' => 'Please enter the price.',
            'price.integer' => 'The price must be a whole number, without commas or symbols.',
            'price.min' => 'The price cannot be negative.',
            'service_charge.min' => 'The service charge cannot be negative.',
            'advance_months.max' => 'Advance months cannot be more than 120.',
            'allowed_for.required' => 'Please choose who this property is available for.',
            'allowed_for.in' => 'Please choose bachelor, family or both.',
            'status.required' => 'Please choose a status for this listing.',
            'status.in' => 'Please choose a valid listing status.',
            'lat.between' => 'The latitude must be between -90 and 90.',
            'lng.between' => 'The longitude must be between -180 and 180.',
            'images.max' => 'You have attached too many images for this listing.',
            'images.*.image' => 'Each listing photo must be an image file.',
            'images.*.mimes' => 'Listing photos must be JPG, JPEG, PNG or WEBP files.',
            'images.*.max' => 'Each listing photo must be 5MB or smaller.',
            'videos.max' => 'You have attached too many videos for this listing.',
            'videos.*.url.url' => 'Enter a full video link, starting with https://',
            'videos.*.url.max' => 'A video link cannot be longer than 500 characters.',
            'video_files.max' => 'You have attached too many video files for this listing.',
            'video_files.*.mimetypes' => 'Video files must be MP4, WebM or MOV.',
            'video_files.*.max' => 'Each video file must be 100MB or smaller.',
        ];
    }
}
