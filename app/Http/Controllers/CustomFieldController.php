<?php

namespace App\Http\Controllers;

use App\Models\CustomFieldOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomFieldController extends Controller
{
    public const MANAGEABLE_FIELDS = [
        'payment_mode' => 'Payment Mode',
        'payment_plan' => 'Payment Plan',
    ];

    public function __construct()
    {
        $this->middleware(['auth', 'active', 'no.cache', 'role:super_admin']);
    }

    public function index(): View
    {
        $options = CustomFieldOption::query()
            ->orderBy('field_key')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->groupBy('field_key');

        return view('settings.custom-fields', [
            'fields' => self::MANAGEABLE_FIELDS,
            'optionsByField' => $options,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'field_key' => ['required', Rule::in(array_keys(self::MANAGEABLE_FIELDS))],
            'label' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $fieldKey = $validated['field_key'];
        $value = $validated['value'];

        if (CustomFieldOption::where('field_key', $fieldKey)->where('value', $value)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['value' => 'This option value already exists for the selected field.']);
        }

        CustomFieldOption::create([
            'field_key' => $fieldKey,
            'label' => $validated['label'],
            'value' => $value,
            'sort_order' => $validated['sort_order'] ?? $this->nextSortOrder($fieldKey),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Custom field option added successfully.');
    }

    public function update(Request $request, CustomFieldOption $option): RedirectResponse
    {
        $this->ensureManageable($option);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $duplicate = CustomFieldOption::query()
            ->where('field_key', $option->field_key)
            ->where('value', $validated['value'])
            ->whereKeyNot($option->id)
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['value' => 'This option value already exists for this field.']);
        }

        $option->update([
            'label' => $validated['label'],
            'value' => $validated['value'],
            'sort_order' => $validated['sort_order'] ?? $option->sort_order,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Custom field option updated successfully.');
    }

    public function destroy(CustomFieldOption $option): RedirectResponse
    {
        $this->ensureManageable($option);

        $option->update(['is_active' => false]);

        return back()->with('success', 'Custom field option deactivated successfully.');
    }

    public function reorder(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'field_key' => ['nullable', Rule::in(array_keys(self::MANAGEABLE_FIELDS))],
            'order' => ['sometimes', 'array'],
            'order.*' => ['integer', 'exists:custom_field_options,id'],
            'option' => ['nullable', 'integer', 'exists:custom_field_options,id'],
            'direction' => ['nullable', Rule::in(['up', 'down'])],
        ]);

        if ($request->filled('order')) {
            $this->reorderList($validated);
            $message = 'Custom field options reordered successfully.';

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message])
                : back()->with('success', $message);
        }

        if ($request->filled('option') && $request->filled('direction')) {
            $this->moveOption((int) $validated['option'], $validated['direction']);
            $message = 'Custom field option moved successfully.';

            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => $message])
                : back()->with('success', $message);
        }

        return back()->withErrors(['order' => 'Provide an option order or a move direction.']);
    }

    private function ensureManageable(CustomFieldOption $option): void
    {
        if (! isset(self::MANAGEABLE_FIELDS[$option->field_key])) {
            abort(403, 'This field is not configurable.');
        }
    }

    private function nextSortOrder(string $fieldKey): int
    {
        return (CustomFieldOption::where('field_key', $fieldKey)->max('sort_order') ?? 0) + 10;
    }

    private function reorderList(array $validated): void
    {
        $ids = array_values(array_map('intval', $validated['order']));

        if ($ids === [] || count(array_unique($ids)) !== count($ids)) {
            abort(422, 'The option order is invalid.');
        }

        $fieldKeys = CustomFieldOption::query()
            ->whereIn('id', $ids)
            ->pluck('field_key')
            ->unique();

        if ($fieldKeys->count() !== 1) {
            abort(422, 'Options from different fields cannot be reordered together.');
        }

        $fieldKey = $validated['field_key'] ?? $fieldKeys->first();
        $this->ensureFieldKeyIsManageable($fieldKey);

        $currentIds = CustomFieldOption::query()
            ->where('field_key', $fieldKey)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map('intval')
            ->values()
            ->all();

        $orderedIds = $ids;
        $orderedCurrentIds = $currentIds;
        sort($orderedIds);
        sort($orderedCurrentIds);

        if ($orderedIds !== $orderedCurrentIds) {
            abort(422, 'The option order must include every option for this field exactly once.');
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $position => $id) {
                CustomFieldOption::whereKey($id)->update(['sort_order' => ($position + 1) * 10]);
            }
        });
    }

    private function moveOption(int $optionId, string $direction): void
    {
        $option = CustomFieldOption::query()->findOrFail($optionId);
        $this->ensureManageable($option);

        $options = CustomFieldOption::query()
            ->where('field_key', $option->field_key)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $currentIndex = $options->search(fn (CustomFieldOption $item): bool => $item->id === $option->id);
        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($currentIndex === false || $targetIndex < 0 || $targetIndex >= $options->count()) {
            abort(422, 'The option cannot be moved in that direction.');
        }

        $options->splice($currentIndex, 1);
        $options->splice($targetIndex, 0, [$option]);

        DB::transaction(function () use ($options) {
            foreach ($options as $position => $item) {
                $item->sort_order = ($position + 1) * 10;
                $item->save();
            }
        });
    }

    private function ensureFieldKeyIsManageable(string $fieldKey): void
    {
        if (! isset(self::MANAGEABLE_FIELDS[$fieldKey])) {
            abort(403, 'This field is not configurable.');
        }
    }
}
