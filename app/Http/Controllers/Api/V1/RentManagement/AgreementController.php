<?php

namespace App\Http\Controllers\Api\V1\RentManagement;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\RentManagement\StoreAgreementRequest;
use App\Http\Resources\Api\V1\RentManagement\AgreementResource;
use App\Models\Agreement;
use App\Models\AgreementTemplate;
use App\Models\Tenancy;
use App\Services\AgreementGenerator;
use App\Services\PostEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgreementController extends ApiController
{
    use GatesRentManagementFeatures;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'tenancy_id' => ['sometimes', 'integer', 'exists:tenancies,id'],
        ]);

        $agreements = Agreement::whereHas('tenancy', fn ($q) => $q->where('owner_id', $request->user()->id))
            ->when($request->filled('tenancy_id'), fn ($q) => $q->where('tenancy_id', $request->integer('tenancy_id')))
            ->orderByDesc('created_at')
            ->get();

        return $this->success(AgreementResource::collection($agreements));
    }

    /**
     * POST /api/v1/rent-management/agreements
     *
     * Generates an agreement document from a template (or the owner's default) for a tenancy.
     */
    public function store(StoreAgreementRequest $request, PostEntitlementService $entitlement, AgreementGenerator $generator): JsonResponse
    {
        $user = $request->user();

        if ($response = $this->featureGate($entitlement->unlockedFeatures($user), 'agreement_templates', 'Generating agreements requires the agreement_templates feature.')) {
            return $response;
        }

        $tenancy = Tenancy::with(['listing', 'owner'])->findOrFail($request->integer('tenancy_id'));
        $this->authorize('view', $tenancy);

        $template = $request->filled('agreement_template_id')
            ? AgreementTemplate::where('owner_id', $user->id)->findOrFail($request->integer('agreement_template_id'))
            : AgreementTemplate::where('owner_id', $user->id)->where('is_default', true)->first();

        $agreement = $generator->generate($tenancy, $template);

        return $this->created(new AgreementResource($agreement), 'Agreement generated.');
    }

    public function show(Agreement $agreement): JsonResponse
    {
        $this->authorize('view', $agreement->tenancy);

        return $this->success(new AgreementResource($agreement));
    }
}
