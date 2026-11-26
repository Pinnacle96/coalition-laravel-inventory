@forelse ($entries as $entry)
    <tr>
        <th scope="row" class="entry-name fw-medium">{{ $entry['name'] }}</th>
        <td class="text-end">{{ number_format($entry['quantity']) }}</td>
        <td class="text-end">{{ \App\InventoryStore::formatAmount($entry['price_minor']) }}</td>
        <td><time datetime="{{ $entry['submitted_at'] }}">{{ \Illuminate\Support\Carbon::parse($entry['submitted_at'])->format('Y-m-d H:i:s') }}</time></td>
        <td class="text-end fw-medium">{{ \App\InventoryStore::formatAmount($entry['total_minor']) }}</td>
        <td class="text-end">
            <button type="button" class="btn btn-sm btn-outline-secondary"
                data-edit-url="{{ route('inventory.update', $entry['id']) }}"
                data-name="{{ $entry['name'] }}"
                data-quantity="{{ $entry['quantity'] }}"
                data-price="{{ \App\InventoryStore::formatAmount($entry['price_minor'], false) }}"
                aria-label="Edit {{ $entry['name'] }}">Edit</button>
        </td>
    </tr>
@empty
    <tr><td colspan="6" class="text-center text-body-secondary py-4">No products added yet.</td></tr>
@endforelse
