<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryEntryRequest;
use App\InventoryStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryStore $inventory) {}

    public function index(): View
    {
        return view('inventory.index', $this->snapshot($this->inventory->all()));
    }

    public function store(InventoryEntryRequest $request): JsonResponse|RedirectResponse
    {
        return $this->saved($request, $this->inventory->add($request->entry()), 'Product added.', 201);
    }

    public function update(InventoryEntryRequest $request, string $entry): JsonResponse|RedirectResponse
    {
        $entries = $this->inventory->update($entry, $request->entry());
        abort_if($entries === null, 404, 'This entry is no longer available.');

        return $this->saved($request, $entries, 'Product updated.', 200);
    }

    private function saved(InventoryEntryRequest $request, array $entries, string $message, int $status): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return to_route('inventory.index')->with('status', $message);
        }

        $snapshot = $this->snapshot($entries);

        return response()->json([
            'rows' => view('inventory.rows', $snapshot)->render(),
            'grand_total' => $snapshot['grandTotal'],
            'count' => count($entries),
            'message' => $message,
        ], $status);
    }

    private function snapshot(array $entries): array
    {
        usort($entries, fn (array $first, array $second) => strcmp($first['submitted_at'], $second['submitted_at']));

        $grandTotal = 0;
        foreach ($entries as &$entry) {
            $entry['total_minor'] = $entry['quantity'] * $entry['price_minor'];
            $grandTotal += $entry['total_minor'];
        }

        return [
            'entries' => $entries,
            'grandTotal' => InventoryStore::formatAmount($grandTotal),
        ];
    }
}
