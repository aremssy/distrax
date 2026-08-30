<?php

namespace App\Services;

use App\Models\PropertyListing;
use App\Models\PropertyDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Shared seller-intake logic used by both the web (OwnerListingController) and API
 * (Api/V1/Listing/ListingController) listing forms, so the two surfaces never drift.
 */
class ListingIntakeService
{
    /** Keys in a validated listing request that belong to the User or property_documents, not PropertyListing. */
    public const NON_LISTING_KEYS = ['seller_type', 'company_name', 'poa_document', 'title_documents'];

    /** Update the seller's identity fields on their User record, if supplied. */
    public function applySellerIdentity(User $user, array $validated, ?UploadedFile $poaDocument): void
    {
        $data = array_filter([
            'seller_type' => $validated['seller_type'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
        ], fn ($v) => $v !== null);

        if ($poaDocument) {
            $data['poa_document_path'] = $poaDocument->store('poa-documents', 'local');
        }

        if ($data !== []) {
            $user->update($data);
        }
    }

    /** @param  list<UploadedFile>  $files */
    public function storeTitleDocuments(PropertyListing $listing, array $files, User $uploader): void
    {
        foreach ($files as $file) {
            PropertyDocument::create([
                'documentable_type' => $listing->getMorphClass(),
                'documentable_id' => $listing->id,
                'uploaded_by' => $uploader->id,
                'type' => 'title',
                'file_path' => $file->store('property-documents/'.$listing->id, 'local'),
            ]);
        }
    }
}
