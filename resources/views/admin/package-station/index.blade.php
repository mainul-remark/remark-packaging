@extends('admin.master')
@section('title', 'Package Station')

@section('content')
<div class="page-breadcrumb border-bottom mb-2">
    <div class="row">
        <div class="col-md-7 align-self-center">
            <h5 class="page-title mb-1"><i class="mdi mdi-package-variant-closed mr-2"></i>Package station</h5>
            <p class="ps-page-subtitle mb-0">Build assorted cartons by order</p>
        </div>
        <div class="col-md-5 align-self-center py-2">
            <nav class="d-flex justify-content-md-end" aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Home</a></li><li class="breadcrumb-item active">Package station</li></ol></nav>
        </div>
    </div>
</div>

<div class="container-fluid pt-3">
<div class="pack-pos{{ $order ? '' : ' no-work-order' }}"><div class="pos-main">
      <section class="page-heading" hidden>
        <div>
          <p class="eyebrow">Packaging / Packing POS</p>
          <h1>Build cartons by order</h1>
          <p>Allocate invoice products into controlled sorted or assorted cartons.</p>
        </div>
        <div class="heading-actions">
          <button class="button secondary" type="button">← All orders</button>
          <button class="button ghost" type="button">Print packing sheet</button>
        </div>
      </section>

      <section class="session-workflow panel" aria-label="Packing session workflow">
        <div class="session-workflow-body">
          <div class="session-workflow-main">
        <div class="session-workflow-header"><span><span class="step-kicker">Packing session</span><h2>Build and finish cartons</h2></span><button id="collapse-all-cartons" class="button ghost" type="button">Collapse all</button></div>
        <ol id="session-wizard" class="wizard" aria-label="Packing session steps">
          <li class="active" data-step="1"><span>1</span><div><small>Build cartons</small><strong>Add all cartons</strong></div></li>
          <li data-step="2"><span>2</span><div><small>Finish cartons</small><strong>Review &amp; confirm</strong></div></li>
        </ol>
          </div>
          <div class="session-workorder" aria-labelledby="work-order-title">
      <div class="work-order-main">
        <div class="document-icon">SO</div>
        <div>
          <div class="title-row"><h2 id="work-order-title">Loading order…</h2><span class="status-badge">IN PROGRESS</span></div>
          <p class="muted" id="work-order-subtitle">Invoice and destination details</p>
        </div>
      </div>
      <dl class="order-facts" id="order-facts"></dl>
      <div class="order-progress">
        <div><span>Invoice packing progress</span><strong id="progress-label">0 / 0 units</strong></div>
        <div class="progress-track"><span id="progress-bar"></span></div>
      </div>
    </div>
        </div>
      </section>

      <div class="packing-grid">
        <aside class="invoice-panel panel" aria-labelledby="invoice-products-title">
          <div class="panel-heading">
            <div><span class="step-kicker">Invoice lines</span><h2 id="invoice-products-title">Products to pack</h2></div>
            <span class="line-count" id="line-count">0 lines</span>
          </div>
          <label class="product-filter"><span>⌕</span><input id="product-filter" type="search" placeholder="Find Invoice"></label>
          <div class="product-list" id="invoice-product-list"></div>
          <div class="invoice-totals">
            <span><small>Invoice units</small><strong id="invoice-total">0</strong></span>
            <span><small>Packed</small><strong id="invoice-packed">0</strong></span>
            <span><small>Remaining</small><strong id="invoice-remaining">0</strong></span>
          </div>
          <section class="packed-cartons" id="packed-cartons" aria-labelledby="packed-cartons-title" hidden>
            <div class="packed-cartons-heading"><div><span class="step-kicker">Packed cartons</span><h3 id="packed-cartons-title">Already packed</h3></div><span class="line-count" id="packed-cartons-count">0 cartons</span></div>
            <div class="packed-cartons-list" id="packed-cartons-list"></div>
          </section>
        </aside>

        <div class="carton-workspace">


        <div id="session-finish-action" class="session-finish-action" hidden>
          <span><strong>Added all cartons?</strong><small>Continue once every sorted and assorted carton has been added.</small></span>
          <button id="finish-cartons" class="button primary" type="button">Review all cartons →</button>
        </div>

        <div id="carton-accordion" class="carton-accordion">
        <section class="builder-panel panel" aria-labelledby="carton-builder-title">
          <div class="builder-header" role="button" tabindex="0" aria-expanded="true">
            <div><span class="step-kicker">New package</span><h2 id="carton-builder-title">Create carton</h2></div>
            <div class="builder-header-actions">
              <span class="draft-pill">Draft carton <b id="draft-number">C010</b></span>

              <button class="button secondary edit-carton-action" type="button">Edit carton</button>
              <span class="accordion-chevron" aria-hidden="true">⌃</span>
            </div>
          </div>

          <div class="wizard-page active" data-page="1">

              <fieldset class="carton-mode-fieldset">
                  <legend>How is this carton packed?</legend>
                  <div class="mode-grid">
                      <label class="mode-card">
                          <input type="radio" name="carton-mode" value="sorted">
                          <span class="mode-icon"><i class="mdi mdi-package-variant-closed" aria-hidden="true"></i></span>
                          <span class="mode-copy"><strong>Sorted carton <i class="info-tip" tabindex="0" role="img" aria-label="One product per carton; make several identical cartons at once." data-tip="One product per carton; make several identical cartons at once.">i</i></strong><small>One product (SKU) packed into one or more identical cartons.</small></span>
                          <svg class="mode-art" viewBox="0 0 96 58" aria-hidden="true" focusable="false"><path d="M25 22h48l-5 27H30z" fill="#c98b4d"/><path d="M25 22 10 31l18 4 12-13zm48 0 14 10-18 4-13-14z" fill="#e6b875"/><path d="M40 22 31 12l-20 18 17 4zm16 0 9-11 22 20-18 4z" fill="#f1ca91"/><g fill="#2f8fd0" stroke="#d8efff" stroke-width="1"><rect x="34" y="18" width="12" height="9" rx="1"/><rect x="47" y="18" width="12" height="9" rx="1"/><rect x="40" y="28" width="12" height="9" rx="1"/><rect x="53" y="28" width="12" height="9" rx="1"/></g><rect x="41" y="39" width="19" height="7" rx="1" fill="#fff"/><text x="50.5" y="44" text-anchor="middle" font-size="4" font-weight="700" fill="#365164">SKU-01</text></svg>
                          <span class="radio-mark"></span>
                      </label>
                      <label class="mode-card">
                          <input type="radio" name="carton-mode" value="assorted">
                          <span class="mode-icon assorted-icon"><i class="mdi mdi-package-variant" aria-hidden="true"></i></span>
                          <span class="mode-copy"><strong>Assorted carton <i class="info-tip" tabindex="0" role="img" aria-label="Choose the carton, packaging items, and invoice products below." data-tip="Choose the carton, packaging items, and invoice products below.">i</i></strong><small>Build a new carton using multiple products and packaging items.</small></span>
                          <svg class="mode-art" viewBox="0 0 96 58" aria-hidden="true" focusable="false"><path d="M25 22h48l-5 27H30z" fill="#c98b4d"/><path d="M25 22 10 31l18 4 12-13zm48 0 14 10-18 4-13-14z" fill="#e6b875"/><path d="M40 22 31 12l-20 18 17 4zm16 0 9-11 22 20-18 4z" fill="#f1ca91"/><g stroke="#fff" stroke-width="1"><rect x="34" y="18" width="12" height="11" rx="1" fill="#2f8fd0"/><rect x="47" y="16" width="12" height="13" rx="1" fill="#32a66a"/><rect x="60" y="19" width="11" height="11" rx="1" fill="#ef704f"/><rect x="40" y="29" width="12" height="10" rx="1" fill="#eab545"/><rect x="53" y="29" width="12" height="10" rx="1" fill="#8d62d7"/></g><rect x="40" y="40" width="21" height="7" rx="1" fill="#fff"/><text x="50.5" y="45" text-anchor="middle" font-size="4" font-weight="700" fill="#365164">ASSORTED</text></svg>
                          <span class="radio-mark"></span>
                      </label>
                  </div>
              </fieldset>

<div id="assorted-section" class="mode-section">
              <div class="section-heading"><div><span class="step-kicker">Carton construction</span><h3>Package and protective items</h3></div><span class="tag">Required</span></div>
              <label class="field"><span>Primary carton pack</span><select id="carton-type"></select></label>
              <div id="sorted-copies" class="carton-copy-control" hidden>
                <label class="field"><span>Number of identical cartons <i class="info-tip" tabindex="0" role="img" aria-label="Each physical carton is saved separately with its own number." data-tip="Each physical carton is saved separately with its own number.">i</i></span><input id="carton-copy-count" type="number" min="1" max="100" step="1" value="1"></label>
              </div>
              <div class="dynamic-block">
                <div class="dynamic-title"><span>Other packaging items</span><button id="add-package-item" class="mini-add" type="button">＋ Add item</button></div>
                <div id="package-items" class="dynamic-rows"></div>
              </div>

              <div class="section-divider"></div>
              <div class="section-heading product-heading" hidden>
                <div><span class="step-kicker">Carton contents</span><h3>Add products</h3></div>
              </div>
              <div class="dynamic-block product-entry" hidden>
                <div class="add-product-row">
                  <div class="field product-search"><label for="product-select"><span>Invoice product</span></label><input id="product-select" type="text" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="product-suggestions" aria-autocomplete="list" placeholder="Search by code or name, or scan barcode…"><div id="product-suggestions" class="product-suggestions" role="listbox" hidden></div></div>
                  <label id="product-quantity-field" class="field quantity-field"><span>Quantity</span><input id="product-quantity" type="number" min="1" value="1"></label>
                </div>
                <div id="carton-products" class="carton-product-list" hidden></div>
              </div>
            </div>

            <div id="weight-card" class="weight-card" hidden>
              <div class="weight-calculation">
                <span class="weight-icon">⚖</span>
                <div><small>System-generated carton weight</small><strong id="system-weight">0 g</strong><p id="weight-breakdown">Select carton contents to calculate weight.</p></div>
              </div>
              <div class="weight-tolerance"><small>Total carton tolerance</small><strong id="weight-range">—</strong><p>Tolerance <b id="weight-tolerance">± 0 g</b></p></div>
              <div class="weight-machine"><small>Weight machine value</small><strong id="machine-weight">—</strong><p id="machine-weight-note">Waiting for scale reading</p></div>
              <label class="manual-weight"><span>Manual carton weight <em>Optional</em></span><div><input id="manual-weight" type="number" min="1" placeholder="0"><b>g</b></div><small>Used for presentation only; the system weight remains visible.</small></label>
            </div>
            <div id="build-actions" class="wizard-actions build-actions" hidden><button id="clear-draft" class="button ghost" type="button">Clear draft</button><div><button class="button primary add-carton-action" type="button">+ Add this carton</button></div></div>
          </div>
        </section>
        </div>

        <section id="session-review" class="global-carton-review panel" hidden>
          <div class="review-card">
            <div class="review-hero"><span>✓</span><div><small>Finish packing session</small><h3 id="review-title">Review added cartons</h3><p id="review-description">Confirm all cartons added during this packing session.</p></div></div>
            <div class="review-grid" id="review-grid"></div>
            <div class="review-list"><div class="review-list-title">Cartons in this session <span id="review-unit-count">0 cartons</span></div><div id="review-products"></div></div>
          </div>
          <div class="wizard-actions"><button id="back-to-cartons" class="button secondary" type="button">← Back to cartons</button><button id="confirm-cartons" class="button primary create-button" type="button">Confirm all cartons</button></div>
        </section>
        </div>
      </div>

      </div>
<aside class="pos-summary" aria-label="Packing summary">
    <button class="summary-toggle" type="button" aria-label="Toggle packing summary">⌃</button>
    <div class="summary-title"><div><span class="summary-icon">▣</span><span><small>Order summary</small><strong id="summary-invoice">Packing invoice</strong></span></div><span class="live-pill"><i></i> Live</span></div>
    <div class="summary-stats">
      <div><small>Packed</small><strong id="summary-packed">0</strong><span>units</span></div>
      <div><small>Remaining</small><strong id="summary-remaining">0</strong><span>units</span></div>
      <div><small>Cartons</small><strong id="summary-cartons">0</strong><span>created</span></div>
    </div>
    <div class="summary-current"><span>Current carton</span><strong><b id="summary-current-units">0</b> units · <b id="summary-current-weight">0 g</b></strong></div>
    <div class="summary-progress"><span id="summary-progress-bar"></span></div>
  </aside>



  <div id="toast" class="toast" role="status" aria-live="polite"></div></div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('pack-pos/pack-pos.css') }}?v={{ filemtime(public_path('pack-pos/pack-pos.css')) }}">
<style>
.page-wrapper,.page-breadcrumb{background:#f8f5f2}.page-breadcrumb .page-title{color:#10213a;font-size:20px;font-weight:700;text-transform:none}.ps-page-subtitle{color:#667085;font-size:12px}
.pack-pos [hidden]{display:none !important}
</style>
@endpush

@push('scripts')
<script src="{{ asset('pack-pos/sweetalert2.all.min.js') }}"></script>
<script>
    window.__PACK_POS_DATA__ = @json($data);
    window.__PACK_POS_WORK_ORDERS__ = @json($orders);
    window.__PACK_POS_WORK_ORDER_TOTAL__ = {{ $orderTotal }};
    window.__PACK_POS_LABEL__ = @json($labelConfig);
    window.__PACK_POS_URLS__ = {
        station: @json(route('package-station')),
        find: @json(route('package-station.find')),
        batch: @json(route('package-station.store')),
        labelPrints: @json(route('cartons.label-prints'))
    };
</script>
<script src="{{ asset('pack-pos/carton-label.js') }}?v={{ filemtime(public_path('pack-pos/carton-label.js')) }}"></script>
<script src="{{ asset('pack-pos/app.js') }}?v={{ filemtime(public_path('pack-pos/app.js')) }}"></script>
@endpush
