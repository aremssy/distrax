<?php

namespace App\Http\Controllers\Api\V1\RentManagement;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\RentManagement\StoreAgreementTemplateRequest;
use App\Http\Requests\Api\V1\RentManagement\UpdateAgreementTemplateRequest;
use App\Http\Resources\Api\V1\RentManagement\AgreementTemplateResource;
use App\Models\AgreementTemplate;
use App\Services\PostEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgreementTemplateController extends ApiController
{
    use GatesRentManagementFeatures;

    public function index(Request $request, PostEntitlementService $entitlement): JsonResponse
    {
        if ($response = $this->featureGate($entitlement->unlockedFeatures($request->user()), 'agreement_templates', 'Agreement templates require an active subscription with that feature.')) {
            return $response;
        }

        $templates = AgreementTemplate::where('owner_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return $this->success(AgreementTemplateResource::collection($templates));
    }

    public function store(StoreAgreementTemplateRequest $request, PostEntitlementService $entitlement): JsonResponse
    {
        if ($response = $this->featureGate($entitlement->unlockedFeatures($request->user()), 'agreement_templates', 'Agreement templates require an active subscription with that feature.')) {
            return $response;
        }

        $template = AgreementTemplate::create([
            'owner_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return $this->created(new AgreementTemplateResource($template), 'Template created.');
    }

    public function update(UpdateAgreementTemplateRequest $request, AgreementTemplate $agreementTemplate): JsonResponse
    {
        abort_unless($agreementTemplate->owner_id === $request->user()->id, 403);

        $agreementTemplate->update($request->validated());

        return $this->success(new AgreementTemplateResource($agreementTemplate));
    }

    public function destroy(Request $request, AgreementTemplate $agreementTemplate): JsonResponse
    {
        abort_unless($agreementTemplate->owner_id === $request->user()->id, 403);

        $agreementTemplate->delete();

        return $this->success(null, 'Template deleted.');
    }
}
