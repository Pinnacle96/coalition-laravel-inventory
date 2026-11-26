<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inventory</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
    <script src="{{ asset('js/inventory.js') }}" defer></script>
</head>
<body>
    <main class="container inventory-shell py-4 py-md-5">
        <header class="mb-4 mb-md-5">
            <h1 class="display-5 fw-semibold mb-2">Inventory</h1>
            <p class="text-body-secondary mb-0">Record stock and track its total value.</p>
        </header>

        <section class="card inventory-card mb-4" aria-labelledby="form-title">
            <div class="card-body p-3 p-md-4">
                <h2 id="form-title" class="h5 mb-3">Add an entry</h2>
                <form id="entry-form" method="post" action="{{ route('inventory.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="product-name" class="form-label">Product name</label>
                            <input id="product-name" name="name" type="text" maxlength="120" required
                                class="form-control @error('name') is-invalid @enderror"
                                aria-describedby="name-feedback" value="{{ old('name') }}">
                            <div id="name-feedback" class="invalid-feedback">@error('name'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="quantity" class="form-label">Quantity in stock</label>
                            <input id="quantity" name="quantity" type="number" inputmode="numeric" min="0" max="999999" step="1" required
                                class="form-control @error('quantity') is-invalid @enderror"
                                aria-describedby="quantity-feedback" value="{{ old('quantity') }}">
                            <div id="quantity-feedback" class="invalid-feedback">@error('quantity'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="price" class="form-label">Price per item</label>
                            <input id="price" name="price" type="number" inputmode="decimal" min="0" max="999999.99" step="0.01" required
                                class="form-control @error('price') is-invalid @enderror"
                                aria-describedby="price-feedback" value="{{ old('price') }}">
                            <div id="price-feedback" class="invalid-feedback">@error('price'){{ $message }}@enderror</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2 mt-3">
                        <button id="save-entry" class="btn btn-primary" type="submit">Add product</button>
                        <button id="cancel-edit" class="btn btn-outline-secondary" type="button" hidden>Cancel</button>
                    </div>
                </form>
                <p id="form-status" class="mb-0" role="status">{{ session('status') }}</p>
                <p id="form-error" class="mb-0" role="alert"></p>
            </div>
        </section>

        <section class="card inventory-card overflow-hidden" aria-labelledby="entries-title">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-baseline justify-content-between gap-2">
                    <h2 id="entries-title" class="h5 mb-0">Stock entries</h2>
                    <span id="entries-count" class="small text-body-secondary">{{ count($entries) }} {{ count($entries) === 1 ? 'entry' : 'entries' }}</span>
                </div>
            </div>
            <div class="table-responsive" role="region" aria-label="Inventory entries" tabindex="0">
                <table class="table align-middle mb-0 inventory-table">
                    <thead>
                        <tr>
                            <th scope="col">Product name</th>
                            <th scope="col" class="text-end">Quantity in stock</th>
                            <th scope="col" class="text-end">Price per item</th>
                            <th scope="col">Datetime submitted <span class="fw-normal">(UTC)</span></th>
                            <th scope="col" class="text-end">Total value number</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody id="entry-rows">
                        @include('inventory.rows')
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="row" colspan="4">Grand total</th>
                            <td id="grand-total" class="text-end fw-semibold">{{ $grandTotal }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
