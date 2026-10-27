@extends('admin.master')
@section('title', 'Brands')

@push('styles')
    @include('admin.includes.datatable-style')
    <style>
        .brand-stat { border: 0; border-left: 4px solid #7460ee; box-shadow: 0 2px 8px rgba(0, 0, 0, .05); }
        .brand-stat .stat-value { font-size: 1.55rem; font-weight: 600; line-height: 1; }
        #brandsDataTable td { vertical-align: middle; }
        #brandsDataTable_wrapper .dataTables_filter { display: none; }
        .filter-panel { background: #f8f9fa; border: 1px solid #edf0f2; border-radius: 6px; }
        .detail-label { color: #6c757d; font-size: .74rem; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 2px; }
        .detail-value { color: #2b2f33; font-weight: 500; overflow-wrap: anywhere; }
        .product-thumb, .product-placeholder { width: 38px; height: 38px; border-radius: 7px; }
        .product-thumb { object-fit: cover; border: 1px solid #e9ecef; }
        .product-placeholder { display: inline-flex; align-items: center; justify-content: center; color: #6c757d; background: #f1f3f5; font-size: 18px; }
        .modal-loading { min-height: 260px; display: flex; align-items: center; justify-content: center; }
        @media (max-width: 767.98px) { .dataTables_length, .dataTables_info { text-align: left !important; } }
    </style>
@endpush

@section('content')
    <div class="page-breadcrumb border-bottom mb-3">
        <div class="row">
            <div class="col-md-7 align-self-center">
                <h5 class="page-title mb-1"><i class="mdi mdi-tag-multiple mr-2"></i>Brands</h5>
                <p class="mb-0 text-muted">Manage brand visibility and inspect linked products</p>
            </div>
            <div class="col-md-5 align-self-center py-2">
                <nav class="d-flex justify-content-md-end" aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">Brands</li></ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid pb-4">
        <div class="row">
            @foreach ([
                ['label' => 'Total brands', 'value' => $summary->total ?? 0, 'icon' => 'mdi-tag-multiple', 'color' => '#7460ee'],
                ['label' => 'Active brands', 'value' => $summary->active ?? 0, 'icon' => 'mdi-check-circle-outline', 'color' => '#36bea6'],
                ['label' => 'Brands with products', 'value' => $summary->with_products ?? 0, 'icon' => 'mdi-link-variant', 'color' => '#2962ff'],
                ['label' => 'Linked products', 'value' => $summary->linked_products ?? 0, 'icon' => 'mdi-cube-outline', 'color' => '#ffbc34'],
            ] as $stat)
                <div class="col-6 col-xl-3">
                    <div class="card brand-stat" style="border-left-color: {{ $stat['color'] }}">
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
                <div><h5 class="mb-0">Brand catalogue</h5><small class="text-muted">Server-side results remain responsive for large catalogues</small></div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshBrands"><i class="mdi mdi-refresh mr-1"></i>Refresh</button>
            </div>
            <div class="card-body">
                <div class="filter-panel p-3 mb-3">
                    <div class="row align-items-end">
                        <div class="col-lg-6 mb-2 mb-lg-0">
                            <label class="small font-weight-bold" for="brandSearch">Search brands</label>
                            <div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="mdi mdi-magnify"></i></span></div><input type="search" class="form-control" id="brandSearch" placeholder="Name, code or parent brand" autocomplete="off"></div>
                        </div>
                        <div class="col-sm-5 col-lg-2 mb-2 mb-lg-0">
                            <label class="small font-weight-bold" for="statusFilter">Record status</label>
                            <select class="form-control" id="statusFilter"><option value="">All</option><option value="ACTIVE">Active</option><option value="INACTIVE">Inactive</option></select>
                        </div>
                        <div class="col-sm-5 col-lg-2 mb-2 mb-sm-0">
                            <label class="small font-weight-bold" for="associationFilter">Products</label>
                            <select class="form-control" id="associationFilter"><option value="">All</option><option value="with_products">With products</option><option value="without_products">Without products</option></select>
                        </div>
                        <div class="col-sm-2 col-lg-2 text-sm-right"><button type="button" class="btn btn-link px-0" id="clearBrandFilters">Clear filters</button></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered w-100" id="brandsDataTable">
                        <thead class="thead-light"><tr><th>Brand</th><th>Code</th><th>SAP ID</th><th>Parent brand</th><th>Products</th><th>Active products</th><th>Status</th><th>Action</th></tr></thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="brandDetailsModal" tabindex="-1" role="dialog" aria-labelledby="brandDetailsTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div><h5 class="modal-title" id="brandDetailsTitle">Brand details</h5><small class="text-muted" id="brandDetailsSubtitle"></small></div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="brandDetailsBody"><div class="modal-loading text-muted"><i class="mdi mdi-loading mdi-spin mr-2"></i>Loading brand details…</div></div>
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
            const detail = (label, value) => '<div class="col-6 col-lg-3 mb-3"><div class="detail-label">' + escapeHtml(label) + '</div><div class="detail-value">' + escapeHtml(value) + '</div></div>';

            const table = $('#brandsDataTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searchDelay: 400,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[0, 'asc']],
                ajax: {
                    url: @json(route('brands.index')),
                    data: data => { data.status = $('#statusFilter').val(); data.association = $('#associationFilter').val(); },
                    error: () => toastr.error('The brand list could not be loaded. Please try again.')
                },
                language: { processing: '<i class="mdi mdi-loading mdi-spin mr-1"></i> Loading brands…', emptyTable: 'No brands are available', zeroRecords: 'No brands match your filters' },
                columns: [
                    {data: 'name', name: 'brands.name', render: (data, type) => type === 'display' ? '<strong>' + escapeHtml(data) + '</strong>' : data},
                    {data: 'code', name: 'brands.code', render: $.fn.dataTable.render.text()},
                    {data: 'sap_id', name: 'brands.sap_id', render: $.fn.dataTable.render.text()},
                    {data: 'parent_name', name: 'parent_brands.name', defaultContent: '—', render: $.fn.dataTable.render.text()},
                    {data: 'products_count', name: 'products_count', searchable: false},
                    {data: 'active_products_count', name: 'active_products_count', searchable: false},
                    {data: 'status', name: 'brands.status', render: (data, type) => type === 'display' ? badge(data, 'status') : data},
                    {data: 'show_url', name: 'action', orderable: false, searchable: false, className: 'text-center', render: (data, type) => type === 'display' ? '<button type="button" class="btn btn-sm btn-outline-primary view-brand" data-url="' + escapeHtml(data) + '"><i class="mdi mdi-eye-outline mr-1"></i>View</button>' : data}
                ]
            });

            let searchTimer;
            $('#brandSearch').on('input', function () { clearTimeout(searchTimer); searchTimer = setTimeout(() => table.search(this.value).draw(), 350); });
            $('#statusFilter, #associationFilter').on('change', () => table.ajax.reload());
            $('#refreshBrands').on('click', () => table.ajax.reload(null, false));
            $('#clearBrandFilters').on('click', function () { $('#brandSearch').val(''); $('#statusFilter, #associationFilter').val(''); table.search('').ajax.reload(); });

            $('#brandsDataTable').on('click', '.view-brand', function () {
                const modal = $('#brandDetailsModal');
                $('#brandDetailsTitle').text('Brand details'); $('#brandDetailsSubtitle').text('');
                $('#brandDetailsBody').html('<div class="modal-loading text-muted"><i class="mdi mdi-loading mdi-spin mr-2"></i>Loading brand details…</div>');
                modal.modal('show');

                $.getJSON($(this).data('url')).done(brand => {
                    $('#brandDetailsTitle').text(brand.name); $('#brandDetailsSubtitle').text(brand.code || 'No code');
                    const products = brand.products.length
                        ? '<div class="table-responsive"><table class="table table-sm table-hover table-bordered mb-0"><thead class="thead-light"><tr><th>Product</th><th>SKU</th><th>Category</th><th>Primary barcode</th><th>UOM</th><th>Verification</th><th>Status</th></tr></thead><tbody>' + brand.products.map(product => {
                            const visual = product.image_url ? '<img class="product-thumb mr-2" src="' + escapeHtml(product.image_url) + '" alt="">' : '<span class="product-placeholder mr-2"><i class="mdi mdi-cube-outline"></i></span>';
                            return '<tr><td><div class="d-flex align-items-center">' + visual + '<strong>' + escapeHtml(product.name) + '</strong></div></td><td>' + escapeHtml(product.sku) + '</td><td>' + escapeHtml(product.category) + '</td><td>' + escapeHtml(product.primary_barcode) + '</td><td>' + escapeHtml(product.base_uom) + '</td><td>' + badge(product.verification_status, 'verification') + '</td><td>' + badge(product.status, 'status') + '</td></tr>';
                        }).join('') + '</tbody></table></div>'
                        : '<div class="text-center text-muted py-4"><i class="mdi mdi-cube-off-outline" style="font-size:32px"></i><p class="mb-0">No products are linked to this brand.</p></div>';
                    const limited = brand.products_count > brand.products_shown ? '<div class="alert alert-info py-2 mt-3 mb-0">Showing the first ' + brand.products_shown + ' of ' + brand.products_count + ' products. Use the Products page to search the full catalogue.</div>' : '';

                    $('#brandDetailsBody').html(
                        '<div class="row">' + detail('Code', brand.code) + detail('SAP ID', brand.sap_id) + detail('Parent brand', brand.parent) + detail('Status', labelize(brand.status)) + detail('Products', brand.products_count) + detail('Child brands', brand.children_count) + detail('Created', brand.created_at) + detail('Last updated', brand.updated_at) + '</div>' +
                        '<hr><div class="d-flex align-items-center justify-content-between mb-2"><h6 class="mb-0">Linked products</h6><span class="text-muted small">' + brand.products_count + ' total</span></div>' + products + limited
                    );
                }).fail(() => $('#brandDetailsBody').html('<div class="alert alert-danger mb-0">Brand details could not be loaded. Close this window and try again.</div>'));
            });
        });
    </script>
@endpush
