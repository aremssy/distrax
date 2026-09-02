<?php

namespace App\Http\Requests\Admin;

use App\Enums\PropertyType;
use App\Models\CustomField;
use App\Models\Currency;
use App\Models\PropertyVideo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreListingRequest extends FormRequest
{
    /**
     * Authorization is enforced by the route's `permission:listings.create` middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'owner_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::in(PropertyType::values())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'language_tag' => ['required', 'string', 'max:10'],
            'price' => ['required', 'integer', 'min:0'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'size:3', Rule::in(Currency::activeCached()->pluck('code')->all())],
            'service_charge' => ['nullable', 'integer', 'min:0'],
            'advance_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'is_negotiable' => ['nullable', 'boolean'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:255'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:255'],
            'floor' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'total_floors' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'area_sqft' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'parking' => ['nullable', 'boolean'],
            'furnished' => ['nullable', 'boolean'],
            'allowed_for' => ['required', Rule::in(['bachelor', 'family', 'both'])],
            'address' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::in(['draft', 'pending', 'active', 'rented', 'sold', 'archived', 'rejected'])],
            'is_featured' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*' => ['nullable'],
            'images' => ['nullable', 'array', 'max:'.(int) setting('listing_max_images', 10)],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'floor_plans' => ['nullable', 'array', 'max:5'],
            'floor_plans.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'videos' => ['nullable', 'array', 'max:'.(int) setting('listing_max_videos', 3)],
            'videos.*.url' => ['nullable', 'url', 'max:500'],
            'video_files' => ['nullable', 'array', 'max:'.(int) setting('listing_max_videos', 3)],
            'video_files.*' => ['file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:102400'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $fields = CustomField::forType((string) $this->input('type'))
                    ->active()
                    ->get();

                foreach ($fields as $field) {
                    $value = $this->input("custom_fields.{$field->id}");
                    $isEmpty = is_array($value)
                        ? count(array_filter($value, fn ($item) => filled($item))) === 0
                        : blank($value);

                    if ($field->is_required && $isEmpty) {
                        $validator->errors()->add("custom_fields.{$field->id}", "{$field->label} is required.");
                    }

                    if ($isEmpty) {
                        continue;
                    }

                    if ($field->type === 'number' && ! is_numeric($value)) {
                        $validator->errors()->add("custom_fields.{$field->id}", "{$field->label} must be a number.");
                    }

                    if ($field->hasOptions() && $field->options) {
                        $submitted = is_array($value) ? $value : [$value];
                        $invalid = array_diff($submitted, $field->options);

                        if ($invalid !== []) {
                            $validator->errors()->add("custom_fields.{$field->id}", "{$field->label} has an invalid option.");
                        }
                    }
                }
            },
            fn (Validator $validator) => $this->validateVideos($validator),
        ];
    }

    /**
     * The video URL must match its declared source, and the total video count
     * (already attached + new URLs + new files) must stay within the limit.
     */
    protected function validateVideos(Validator $validator): void
    {
        $urlRows = collect($this->input('videos', []))
            ->filter(fn ($video) => filled($video['url'] ?? null));

        $maxVideos = (int) setting('listing_max_videos', 3);
        $existing = $this->route('listing')?->videos()->count() ?? 0;
        $total = $existing + $urlRows->count() + count((array) $this->file('video_files', []));

        if ($total > $maxVideos) {
            $validator->errors()->add(
                'videos',
                "A listing can have at most {$maxVideos} videos — it already has {$existing}. Delete one first."
            );
        }

        foreach ($urlRows as $index => $video) {
            if (PropertyVideo::detectSource(trim((string) $video['url'])) === null) {
                $validator->errors()->add(
                    "videos.{$index}.url",
                    'Enter a YouTube or Vimeo link, or a direct MP4/WebM/MOV file link.'
                );
            }
        }
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
