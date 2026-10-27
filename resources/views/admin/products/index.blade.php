@extends('admin.master')
@section('title', 'Products')

@push('styles')
    @include('admin.includes.datatable-style')
    <style>
        .catalog-stat { border: 0; border-left: 4px solid #7460ee; box-shadow: 0 2px 8px rgba(0, 0, 0, .05); }
        .catalog-stat .stat-value { font-size: 1.55rem; font-weight: 600; line-height: 1; }
        .product-thumb, .product-placeholder { width: 44px; height: 44px; border-radius: 8px; }
        .product-thumb { object-fit: cover; border: 1px solid #e9ecef; background: #fff; }
        .product-placeholder { display: inline-flex; align-items: center; justify-content: center; color: #6c757d; background: #f1f3f5; font-size: 20px; }
        #productsDataTable td { vertical-align: middle; }
        #productsDataTable_wrapper .dataTables_filter { display: none; }
        .filter-panel { background: #f8f9fa; border: 1px solid #edf0f2; border-radius: 6px; }
        .modal-product-image { width: 112px; height: 112px; border-radius: 10px; object-fit: cover; border: 1px solid #e9ecef; }
        .detail-label { color: #6c757d; font-size: .74rem; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 2px; }
        .detail-value { color: #2b2f33; font-weight: 500; overflow-wrap: anywhere; }
        .detail-section-title { font-size: .85rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; }
        .modal-loading { min-height: 260px; display: flex; align-items: center; justify-content: center; }
        @media (max-width: 767.98px) { .dataTables_length, .dataTables_info { text-align: left !important; } }
    </style>
@endpush

@section('content')
    <div class="page-breadcrumb border-bottom mb-3">
        <div class="row">
            <div class="col-md-7 align-self-center">
                <h5 class="page-title mb-1"><i class="mdi mdi-cube-outline mr-2"></i>Products</h5>
                <p class="mb-0 text-muted">Search and inspect the product catalogue</p>
            </div>
            <div class="col-md-5 align-self-center py-2">
                <nav class="d-flex justify-content-md-end" aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Products</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid pb-4">
        <div class="row">
            @foreach ([
                ['label' => 'Total products', 'value' => $summary->total ?? 0, 'icon' => 'mdi-cube-outline', 'color' => '#7460ee'],
                ['label' => 'Active', 'value' => $summary->active ?? 0, 'icon' => 'mdi-check-circle-outline', 'color' => '#36bea6'],
                ['label' => 'Verified', 'value' => $summary->verified ?? 0, 'icon' => 'mdi-shield-check', 'color' => '#2962ff'],
                ['label' => 'Needs review', 'value' => $summary->needs_review ?? 0, 'icon' => 'mdi-alert-circle-outline', 'color' => '#ffbc34'],
            ] as $stat)
                <div class="col-6 col-xl-3">
                    <div class="card catalog-stat" style="border-left-color: {{ $stat['color'] }}">
                        <div class="card-body d-flex align-items-center justify-content-between py-3">
                            <div><div class="stat-value">{{ number_format((int) $stat['value']) }}</div><small class="text-muted">{{ $stat['label'] }}</small></div>
                            <i class="mdi {{ $stat['icon'] }}" style="font-size: 28px; color: {{ $stat['color'] }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                <div><h5 class="mb-0">Product catalogue</h5><small class="text-muted">Results are loaded securely from the server</small></div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshProducts"><i class="mdi mdi-refresh mr-1"></i>Refresh</button>
            </div>
            <div class="card-body">
                <div class="filter-panel p-3 mb-3">
                    <div class="row align-items-end">
                        <div class="col-lg-6 mb-2 mb-lg-0">
                            <label class="small font-weight-bold" for="productSearch">Search products</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="mdi mdi-magnify"></i></span></div>
                                <input type="search" class="form-control" id="productSearch" placeholder="Name, SKU, category or brand" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-sm-5 col-lg-2 mb-2 mb-lg-0">
                            <label class="small font-weight-bold" for="verificationFilter">Verification</label>
                            <select class="form-control" id="verificationFilter">
                                <option value="">All</option><option value="VERIFIED">Verified</option><option value="PENDING">Pending</option>
                                <option value="PROVISIONAL">Provisional</option><option value="REJECTED">Rejected</option>
                            </select>
                        </div>
                        <div class="col-sm-5 col-lg-2 mb-2 mb-sm-0">
                            <label class="small font-weight-bold" for="statusFilter">Record status</label>
                            <select class="form-control" id="statusFilter"><option value="">All</option><option value="ACTIVE">Active</option><option value="INACTIVE">Inactive</option></select>
                        </div>
                        <div class="col-sm-2 col-lg-2 text-sm-right">
                            <button type="button" class="btn btn-link px-0" id="clearProductFilters">Clear filters</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered w-100" id="productsDataTable">
                        <thead class="thead-light"><tr><th>Product</th><th>SKU</th><th>Category</th><th>Brand</th><th>Primary barcode</th><th>UOM</th><th>Verification</th><th>Status</th><th>Action</th></tr></thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="productDetailsModal" tabindex="-1" role="dialog" aria-labelledby="productDetailsTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div><h5 class="modal-title" id="productDetailsTitle">Product details</h5><small class="text-muted" id="productDetailsSubtitle"></small></div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="productDetailsBody"><div class="modal-loading text-muted"><i class="mdi mdi-loading mdi-spin mr-2"></i>Loading product details…</div></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('admin.includes.datatable-js')
    <script>
        $(function () {
            const escapeHtml = value => $('<div>').text(value == null || value === '' ? '—' : value).html();
            const labelize = value => String(value || '').toLowerCase().replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase());
            const badge = (value, type) => {
                const colors = type === 'verification'
                    ? {VERIFIED: 'success', PENDING: 'warning', PROVISIONAL: 'info', REJECTED: 'danger'}
                    : {ACTIVE: 'success', INACTIVE: 'secondary'};
                return '<span class="badge badge-' + (colors[value] || 'secondary') + '">' + escapeHtml(labelize(value)) + '</span>';
            };
            const detail = (label, value) => '<div class="col-sm-6 col-lg-4 mb-3"><div class="detail-label">' + escapeHtml(label) + '</div><div class="detail-value">' + escapeHtml(value) + '</div></div>';

            const table = $('#productsDataTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searchDelay: 400,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[0, 'asc']],
                ajax: {
                    url: @json(route('products.index')),
                    data: data => {
                        data.verification_status = $('#verificationFilter').val();
                        data.status = $('#statusFilter').val();
                    },
                    error: () => toastr.error('The product list could not be loaded. Please try again.')
                },
                language: { processing: '<i class="mdi mdi-loading mdi-spin mr-1"></i> Loading products…', emptyTable: 'No products are available', zeroRecords: 'No products match your filters' },
                columns: [
                    {data: 'name', name: 'products.name', render: (data, type, row) => {
                        if (type !== 'display') return data;
                        const visual = row.image_url
                            ? '<img class="product-thumb mr-2" src="' + escapeHtml(row.image_url) + '" alt="">'
                            : '<span class="product-placeholder mr-2"><i class="mdi mdi-cube-outline"></i></span>';
                        return '<div class="d-flex align-items-center">' + visual + '<div><strong>' + escapeHtml(data) + '</strong><br><small class="text-muted">' + row.barcodes_count + ' barcode' + (Number(row.barcodes_count) === 1 ? '' : 's') + '</small></div></div>';
                    }},
                    {data: 'sku', name: 'products.sku'},
                    {data: 'category_name', name: 'product_categories.name', defaultContent: '—'},
                    {data: 'brand_name', name: 'brands.name', defaultContent: '—'},
                    {data: 'primary_barcode', name: 'primary_barcode', orderable: false, searchable: false},
                    {data: 'base_uom', name: 'products.base_uom'},
                    {data: 'verification_status', name: 'products.verification_status', render: (data, type) => type === 'display' ? badge(data, 'verification') : data},
                    {data: 'status', name: 'products.status', render: (data, type) => type === 'display' ? badge(data, 'status') : data},
                    {data: 'show_url', name: 'action', orderable: false, searchable: false, className: 'text-center', render: (data, type) => type === 'display' ? '<button type="button" class="btn btn-sm btn-outline-primary view-product" data-url="' + escapeHtml(data) + '"><i class="mdi mdi-eye-outline mr-1"></i>View</button>' : data}
                ]
            });

            let searchTimer;
            $('#productSearch').on('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => table.search(this.value).draw(), 350);
            });
            $('#verificationFilter, #statusFilter').on('change', () => table.ajax.reload());
            $('#refreshProducts').on('click', () => table.ajax.reload(null, false));
            $('#clearProductFilters').on('click', function () {
                $('#productSearch').val(''); $('#verificationFilter, #statusFilter').val('');
                table.search('').ajax.reload();
            });

            $('#productsDataTable').on('click', '.view-product', function () {
                const modal = $('#productDetailsModal');
                $('#productDetailsTitle').text('Product details'); $('#productDetailsSubtitle').text('');
                $('#productDetailsBody').html('<div class="modal-loading text-muted"><i class="mdi mdi-loading mdi-spin mr-2"></i>Loading product details…</div>');
                modal.modal('show');

                $.getJSON($(this).data('url')).done(product => {
                    $('#productDetailsTitle').text(product.name); $('#productDetailsSubtitle').text(product.sku || 'No SKU');
                    const image = product.image_url
                        ? '<img class="modal-product-image" src="' + escapeHtml(product.image_url) + '" alt="">'
                        : '<div class="modal-product-image product-placeholder"><i class="mdi mdi-cube-outline" style="font-size:42px"></i></div>';
                    const barcodes = product.barcodes.length
                        ? '<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr><th>Barcode</th><th>Type</th><th>Multiplier</th><th>Status</th></tr></thead><tbody>' + product.barcodes.map(item => '<tr><td>' + escapeHtml(item.barcode) + (item.is_primary ? ' <span class="badge badge-primary">Primary</span>' : '') + '</td><td>' + escapeHtml(item.type) + '</td><td>' + escapeHtml(item.quantity_multiplier) + '</td><td>' + badge(item.status, 'status') + '</td></tr>').join('') + '</tbody></table></div>'
                        : '<p class="text-muted mb-0">No barcodes recorded.</p>';
                    const weights = product.weight_specs.length
                        ? '<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr><th>Version</th><th>Reference</th><th>Tolerance</th><th>Method</th><th>Status</th></tr></thead><tbody>' + product.weight_specs.map(item => '<tr><td>v' + escapeHtml(item.version) + '</td><td>' + escapeHtml(item.reference_weight_g) + ' g</td><td>−' + escapeHtml(item.lower_tolerance_g) + ' / +' + escapeHtml(item.upper_tolerance_g) + ' g</td><td>' + escapeHtml(labelize(item.measurement_method)) + '</td><td>' + badge(item.verification_status, 'verification') + '</td></tr>').join('') + '</tbody></table></div>'
                        : '<p class="text-muted mb-0">No weight specifications recorded.</p>';

                    $('#productDetailsBody').html(
                        '<div class="d-flex flex-column flex-sm-row mb-4">' + image + '<div class="ml-sm-3 mt-3 mt-sm-0 flex-grow-1"><div class="row">' +
                        detail('Category', product.category) + detail('Brand', product.brand) + detail('Variant', product.variant) + detail('Base UOM', product.base_uom) + detail('Units / carton', product.units_per_carton) + detail('Shelf life (days)', product.total_shelf_life) +
                        '</div><div>' + badge(product.verification_status, 'verification') + ' ' + badge(product.status, 'status') + '</div></div></div>' +
                        '<div class="detail-label">Description</div><p class="mb-4">' + escapeHtml(product.description) + '</p>' +
                        '<div class="row">' + detail('Batch tracking', product.requires_batch ? 'Required' : 'Not required') + detail('Expiry tracking', product.requires_expiry ? 'Required' : 'Not required') + detail('Verified by', product.verified_by) + detail('Verified at', product.verified_at) + detail('Created', product.created_at) + detail('Last updated', product.updated_at) + '</div>' +
                        '<hr><h6 class="detail-section-title">Barcodes</h6>' + barcodes + '<hr><h6 class="detail-section-title">Recent weight specifications</h6>' + weights
                    );
                }).fail(() => $('#productDetailsBody').html('<div class="alert alert-danger mb-0">Product details could not be loaded. Close this window and try again.</div>'));
            });
        });
    </script>
@endpush
