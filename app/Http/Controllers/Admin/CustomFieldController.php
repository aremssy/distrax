<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PropertyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCustomFieldRequest;
use App\Models\CustomField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomFieldController extends Controller
{
    public function index(Request $request): View
    {
        $selectedType = $request->string('type', 'rent')->value();
        if (! in_array($selectedType, PropertyType::values())) {
            $selectedType = 'rent';
        }

        $fields = CustomField::forType($selectedType)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return view('admin.custom-fields.index', compact('fields', 'selectedType'));
    }

    public function store(SaveCustomFieldRequest $request): RedirectResponse
    {
        $validated = $this->fieldData($request);

        $validated['key'] = Str::snake($validated['label']);
        $validated['sort_order'] = CustomField::forType($validated['property_type'])->max('sort_order') + 1;

        CustomField::create($validated);

        return redirect()->route('admin.custom-fields.index', ['type' => $validated['property_type']])
            ->with('success', 'Custom field added.');
    }

    public function update(SaveCustomFieldRequest $request, CustomField $customField): RedirectResponse
    {
        $validated = $this->fieldData($request);

        $customField->update($validated);

        return redirect()->route('admin.custom-fields.index', ['type' => $customField->property_type])
            ->with('success', 'Custom field updated.');
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        $type = $customField->property_type;

        DB::transaction(function () use ($customField): void {
            $customField->values()->delete();
            $customField->delete();
        });

        return redirect()->route('admin.custom-fields.index', ['type' => $type])
            ->with('success', 'Custom field deleted.');
    }

    /**
     * Persist a drag-and-drop ordering: sort_order is set to each field's position
     * in the submitted id list, atomically so a partial save can't scramble it.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(PropertyType::values())],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:custom_fields,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['ids'] as $position => $id) {
                CustomField::whereKey($id)
                    ->whereIn('property_type', [$validated['type'], 'all'])
                    ->update(['sort_order' => $position]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function move(Request $request, CustomField $customField): RedirectResponse
    {
        $direction = $request->string('direction')->value();

        if ($direction === 'up' && $customField->sort_order > 0) {
            $swap = CustomField::forType($customField->property_type)
                ->where('sort_order', '<', $customField->sort_order)
                ->orderByDesc('sort_order')
                ->first();

            if ($swap) {
                $this->swapSortOrder($customField, $swap);
            }
        } elseif ($direction === 'down') {
            $swap = CustomField::forType($customField->property_type)
                ->where('sort_order', '>', $customField->sort_order)
                ->orderBy('sort_order')
                ->first();

            if ($swap) {
                $this->swapSortOrder($customField, $swap);
            }
        }

        return back()->with('success', 'Field reordered.');
    }

    /**
     * Exchange the sort_order of two fields atomically — a half-applied swap would
     * leave both fields sharing one sort_order and silently scramble the ordering.
     */
    private function swapSortOrder(CustomField $field, CustomField $swap): void
    {
        DB::transaction(function () use ($field, $swap): void {
            [$field->sort_order, $swap->sort_order] = [$swap->sort_order, $field->sort_order];

            $field->saveQuietly();
            $swap->saveQuietly();
        });
    }

    /** @return array<string, mixed> */
    private function fieldData(SaveCustomFieldRequest $request): array
    {
        $validated = $request->validated();

        // Parse newline-separated options into array
        if (! empty($validated['options'])) {
            $validated['options'] = array_values(array_filter(
                array_map('trim', explode("\n", $validated['options']))
            ));
        } else {
            $validated['options'] = null;
        }

        $validated['is_required'] = $request->boolean('is_required');
        $validated['is_filterable'] = $request->boolean('is_filterable');
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
