@extends('admin.master')
@section('title', 'Product Categories')

@push('styles')
    @include('admin.includes.datatable-style')
    <style>
        .category-stat { border: 0; border-left: 4px solid #7460ee; box-shadow: 0 2px 8px rgba(0, 0, 0, .05); }
        .category-stat .stat-value { font-size: 1.55rem; font-weight: 600; line-height: 1; }
        #categoriesDataTable td { vertical-align: middle; }
        #categoriesDataTable_wrapper .dataTables_filter { display: none; }
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
                <h5 class="page-title mb-1"><i class="mdi mdi-shape-outline mr-2"></i>Product Categories</h5>
                <p class="mb-0 text-muted">Explore the category hierarchy and its linked products</p>
            </div>
            <div class="col-md-5 align-self-center py-2">
                <nav class="d-flex justify-content-md-end" aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">Product Categories</li></ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid pb-4">
        <div class="row">
            @foreach ([
                ['label' => 'Total categories', 'value' => $summary->total ?? 0, 'icon' => 'mdi-shape-outline', 'color' => '#7460ee'],
                ['label' => 'Active categories', 'value' => $summary->active ?? 0, 'icon' => 'mdi-check-circle-outline', 'color' => '#36bea6'],
                ['label' => 'Categories with products', 'value' => $summary->with_products ?? 0, 'icon' => 'mdi-link-variant', 'color' => '#2962ff'],
                ['label' => 'Linked products', 'value' => $summary->linked_products ?? 0, 'icon' => 'mdi-cube-outline', 'color' => '#ffbc34'],
            ] as $stat)
                <div class="col-6 col-xl-3">
                    <div class="card category-stat" style="border-left-color: {{ $stat['color'] }}">
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
                <div><h5 class="mb-0">Category catalogue</h5><small class="text-muted">Server-side results remain responsive for large catalogues</small></div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshCategories"><i class="mdi mdi-refresh mr-1"></i>Refresh</button>
            </div>
            <div class="card-body">
                <div class="filter-panel p-3 mb-3">
                    <div class="row align-items-end">
                        <div class="col-lg-6 mb-2 mb-lg-0">
                            <label class="small font-weight-bold" for="categorySearch">Search categories</label>
                            <div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="mdi mdi-magnify"></i></span></div><input type="search" class="form-control" id="categorySearch" placeholder="Name, code or parent category" autocomplete="off"></div>
                        </div>
                        <div class="col-sm-5 col-lg-2 mb-2 mb-lg-0">
                            <label class="small font-weight-bold" for="statusFilter">Record status</label>
                            <select class="form-control" id="statusFilter"><option value="">All</option><option value="ACTIVE">Active</option><option value="INACTIVE">Inactive</option></select>
                        </div>
                        <div class="col-sm-5 col-lg-2 mb-2 mb-sm-0">
                            <label class="small font-weight-bold" for="associationFilter">Products</label>
                            <select class="form-control" id="associationFilter"><option value="">All</option><option value="with_products">With products</option><option value="without_products">Without products</option></select>
                        </div>
                        <div class="col-sm-2 col-lg-2 text-sm-right"><button type="button" class="btn btn-link px-0" id="clearCategoryFilters">Clear filters</button></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered w-100" id="categoriesDataTable">
                        <thead class="thead-light"><tr><th>Category</th><th>Code</th><th>SAP ID</th><th>Parent category</th><th>Child categories</th><th>Products</th><th>Active products</th><th>Status</th><th>Action</th></tr></thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryDetailsModal" tabindex="-1" role="dialog" aria-labelledby="categoryDetailsTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div><h5 class="modal-title" id="categoryDetailsTitle">Category details</h5><small class="text-muted" id="categoryDetailsSubtitle"></small></div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="categoryDetailsBody"><div class="modal-loading text-muted"><i class="mdi mdi-loading mdi-spin mr-2"></i>Loading category details…</div></div>
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

            const table = $('#categoriesDataTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searchDelay: 400,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[0, 'asc']],
                ajax: {
                    url: @json(route('product-categories.index')),
                    data: data => { data.status = $('#statusFilter').val(); data.association = $('#associationFilter').val(); },
                    error: () => toastr.error('The category list could not be loaded. Please try again.')
                },
                language: { processing: '<i class="mdi mdi-loading mdi-spin mr-1"></i> Loading categories…', emptyTable: 'No product categories are available', zeroRecords: 'No categories match your filters' },
                columns: [
                    {data: 'name', name: 'product_categories.name', render: (data, type) => type === 'display' ? '<strong>' + escapeHtml(data) + '</strong>' : data},
                    {data: 'code', name: 'product_categories.code', render: $.fn.dataTable.render.text()},
                    {data: 'sap_id', name: 'product_categories.sap_id', render: $.fn.dataTable.render.text()},
                    {data: 'parent_name', name: 'parent_categories.name', defaultContent: '—', render: $.fn.dataTable.render.text()},
                    {data: 'children_count', name: 'children_count', searchable: false},
                    {data: 'products_count', name: 'products_count', searchable: false},
                    {data: 'active_products_count', name: 'active_products_count', searchable: false},
                    {data: 'status', name: 'product_categories.status', render: (data, type) => type === 'display' ? badge(data, 'status') : data},
                    {data: 'show_url', name: 'action', orderable: false, searchable: false, className: 'text-center', render: (data, type) => type === 'display' ? '<button type="button" class="btn btn-sm btn-outline-primary view-category" data-url="' + escapeHtml(data) + '"><i class="mdi mdi-eye-outline mr-1"></i>View</button>' : data}
                ]
            });

            let searchTimer;
            $('#categorySearch').on('input', function () { clearTimeout(searchTimer); searchTimer = setTimeout(() => table.search(this.value).draw(), 350); });
            $('#statusFilter, #associationFilter').on('change', () => table.ajax.reload());
            $('#refreshCategories').on('click', () => table.ajax.reload(null, false));
            $('#clearCategoryFilters').on('click', function () { $('#categorySearch').val(''); $('#statusFilter, #associationFilter').val(''); table.search('').ajax.reload(); });

            $('#categoriesDataTable').on('click', '.view-category', function () {
                const modal = $('#categoryDetailsModal');
                $('#categoryDetailsTitle').text('Category details'); $('#categoryDetailsSubtitle').text('');
                $('#categoryDetailsBody').html('<div class="modal-loading text-muted"><i class="mdi mdi-loading mdi-spin mr-2"></i>Loading category details…</div>');
                modal.modal('show');

                $.getJSON($(this).data('url')).done(category => {
                    $('#categoryDetailsTitle').text(category.name); $('#categoryDetailsSubtitle').text(category.code || 'No code');
                    const products = category.products.length
                        ? '<div class="table-responsive"><table class="table table-sm table-hover table-bordered mb-0"><thead class="thead-light"><tr><th>Product</th><th>SKU</th><th>Brand</th><th>Primary barcode</th><th>UOM</th><th>Verification</th><th>Status</th></tr></thead><tbody>' + category.products.map(product => {
                            const visual = product.image_url ? '<img class="product-thumb mr-2" src="' + escapeHtml(product.image_url) + '" alt="">' : '<span class="product-placeholder mr-2"><i class="mdi mdi-cube-outline"></i></span>';
                            return '<tr><td><div class="d-flex align-items-center">' + visual + '<strong>' + escapeHtml(product.name) + '</strong></div></td><td>' + escapeHtml(product.sku) + '</td><td>' + escapeHtml(product.brand) + '</td><td>' + escapeHtml(product.primary_barcode) + '</td><td>' + escapeHtml(product.base_uom) + '</td><td>' + badge(product.verification_status, 'verification') + '</td><td>' + badge(product.status, 'status') + '</td></tr>';
                        }).join('') + '</tbody></table></div>'
                        : '<div class="text-center text-muted py-4"><i class="mdi mdi-cube-outline" style="font-size:32px"></i><p class="mb-0">No products are linked to this category.</p></div>';
                    const limited = category.products_count > category.products_shown ? '<div class="alert alert-info py-2 mt-3 mb-0">Showing the first ' + category.products_shown + ' of ' + category.products_count + ' products. Use the Products page to search the full catalogue.</div>' : '';

                    $('#categoryDetailsBody').html(
                        '<div class="row">' + detail('Code', category.code) + detail('SAP ID', category.sap_id) + detail('Parent category', category.parent) + detail('Status', labelize(category.status)) + detail('Products', category.products_count) + detail('Child categories', category.children_count) + detail('Created', category.created_at) + detail('Last updated', category.updated_at) + '</div>' +
                        '<hr><div class="d-flex align-items-center justify-content-between mb-2"><h6 class="mb-0">Linked products</h6><span class="text-muted small">' + category.products_count + ' total</span></div>' + products + limited
                    );
                }).fail(() => $('#categoryDetailsBody').html('<div class="alert alert-danger mb-0">Category details could not be loaded. Close this window and try again.</div>'));
            });
        });
    </script>
@endpush
