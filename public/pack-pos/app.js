(function () {
const state = {
  data: null,
  mode: "",
  step: 1,
  packageRows: [],
  cartonProducts: [],
  nextCarton: 1,
  drafts: [],
  activeDraft: null,
  builderTemplate: "",
  sessionCartons: [],
  sessionSpecs: [],
  batchKey: null
};

const $ = (selector, root = null) => {
  if (root) return root.querySelector(selector);
  return state.activeDraft?.root.querySelector(selector) || document.querySelector(selector);
};
const $$ = (selector, root = null) => {
  if (root) return [...root.querySelectorAll(selector)];
  const activeMatches = state.activeDraft ? [...state.activeDraft.root.querySelectorAll(selector)] : [];
  return activeMatches.length ? activeMatches : [...document.querySelectorAll(selector)];
};
const number = value => new Intl.NumberFormat("en-US").format(value);
const grams = value => `${number(Math.round(value))} g`;
const urls = window.__PACK_POS_URLS__ || {};
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";

function syncActiveDraft() {
  if (!state.activeDraft) return;
  state.activeDraft.mode = state.mode;
  state.activeDraft.step = state.step;
  state.activeDraft.packageRows = state.packageRows;
  state.activeDraft.cartonProducts = state.cartonProducts;
}

function activateDraft(draft, scroll = true) {
  syncActiveDraft();
  state.activeDraft = draft;
  state.mode = draft.mode;
  state.step = draft.step;
  state.packageRows = draft.packageRows;
  state.cartonProducts = draft.cartonProducts;
  state.drafts.forEach(item => {
    item.root.classList.toggle("active", item === draft);
    item.root.classList.toggle("collapsed", item !== draft);
    item.root.querySelector(".builder-header").setAttribute("aria-expanded", item === draft ? "true" : "false");
  });
  renderSummary();
  if (scroll) draft.root.scrollIntoView({ behavior: "smooth", block: "start" });
}

function collapseAllDrafts() {
  syncActiveDraft();
  state.drafts.forEach(draft => {
    draft.root.classList.remove("active");
    draft.root.classList.add("collapsed");
    draft.root.querySelector(".builder-header").setAttribute("aria-expanded", "false");
  });
}

function toggleDraft(draft) {
  if (draft.root.classList.contains("active") && !draft.root.classList.contains("collapsed")) {
    collapseAllDrafts();
    return;
  }
  activateDraft(draft);
}

function registerDraft(root, draftNumber) {
  prepareBuilderLayout(root);
  const draft = { root, number: draftNumber, mode: "", step: 1, packageRows: [], cartonProducts: [], completed: false };
  root.dataset.draft = draftNumber;
  root.classList.add("active");
  root.querySelector("#draft-number").textContent = draftNumber;
  state.drafts.push(draft);
  activateDraft(draft, false);
  return draft;
}

function prepareBuilderLayout(root) {
  const section = root.querySelector("#assorted-section");
  const divider = section?.querySelector(".section-divider");
  const modeFieldset = root.querySelector(".carton-mode-fieldset");
  const productRow = section?.querySelector(".add-product-row");
  const copyField = section?.querySelector("#sorted-copies");
  if (divider && modeFieldset) divider.after(modeFieldset);
  if (productRow && copyField) productRow.append(copyField);
  if (section) section.hidden = false;
}

async function loadData() {
  return structuredClone({ packagingItems: [], ...window.__PACK_POS_DATA__ });
}

function getProduct(id) { return state.data.products.find(product => product.id === id); }
function getCartonType(id) { return state.data.cartonTypes.find(type => type.id === id); }
function getPackageItem(id) { return state.data.packagingItems.find(item => item.id === id); }
function getTemplate() { return null; }
function remaining(product) { return Math.max(0, product.ordered - product.packed); }
function limitMessage(product) {
  return remaining(product) === 0
    ? `${product.name} (${product.sku}) is fully packed: ${number(product.packed)} / ${number(product.ordered)} pcs. No more can be added.`
    : `Only ${number(remaining(product))} more pcs of ${product.name} (${product.sku}) can be packed.`;
}
function currentProducts() { return state.cartonProducts; }
function currentCopyCount() { return state.mode === "sorted" ? Number($("#carton-copy-count")?.value || 1) : 1; }
function unitsPerCarton() { return currentProducts().reduce((sum, line) => sum + Number(line.quantity || 0), 0); }
function currentUnits() { return unitsPerCarton() * currentCopyCount(); }

function sortedCartonCodes(count, lines) {
  const invoice = state.data.workOrder.invoice.replace(/[^A-Za-z0-9-]/g, "-");
  const primarySku = getProduct(lines[0].productId).sku.replace(/[^A-Za-z0-9-]/g, "-");
  const prefix = `${invoice}-${primarySku}`;
  const lastSequence = state.data.existingCartons.reduce((highest, carton) => {
    if (!carton.number.startsWith(`${prefix}-`)) return highest;
    const sequence = Number(carton.number.slice(prefix.length + 1));
    return Number.isInteger(sequence) ? Math.max(highest, sequence) : highest;
  }, 0);
  return Array.from({ length: count }, (_, index) => `${prefix}-${String(lastSequence + index + 1).padStart(3, "0")}`);
}

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, character => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" })[character]);
}

// Prints through the shared label script (one label per page, hidden frame, resolves when the dialog closes).
function printCartonLabels(cartons) {
  return window.CartonLabel.print(cartons);
}

// A label is printed once. Saved cartons record that first print on the server before anything is sent to the printer,
// so the same label can never print twice from here; a repeat needs approval on the reprint page.
async function printLabelsOnce(cartons) {
  const todo = cartons.filter(carton => carton && !carton.labelPrinted);
  if (!todo.length) return false;
  try {
    const response = await fetch(urls.labelPrints, {
      method: "POST",
      headers: { Accept: "application/json", "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
      body: JSON.stringify({ carton_ids: todo.map(carton => carton.id) })
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(body.message || "Labels could not be recorded, so nothing was printed.");
    const printed = new Set(body.data.printed.map(job => job.carton_id));
    todo.forEach(carton => { carton.labelPrinted = true; });
    renderReview();
    if (body.data.already_printed.length) showToast(`${body.data.already_printed.length} label(s) were already printed and were skipped. Reprinting needs approval.`, true);
    await printCartonLabels(todo.filter(carton => printed.has(carton.id)));
    return true;
  } catch (error) {
    showToast(error.message, true);
    renderReview();
    return false;
  }
}

function printCartonLabel(carton) {
  if (carton.labelPrinted) return showToast("This label was already printed. Reprinting needs approval on the reprint page.", true);
  if (!carton.id) return showToast("Confirm all cartons first; the label carries the saved carton number.", true);
  printLabelsOnce([carton]);
}

function renderCartonCodePreview() {
  const preview = $("#carton-code-preview");
  const lines = currentProducts();
  const copies = currentCopyCount();
  if (!preview || state.mode !== "sorted" || !lines.length || !Number.isInteger(copies) || copies < 1) return;
  const codes = sortedCartonCodes(Math.min(copies, 100), lines);
  preview.textContent = copies === 1 ? `Carton code: ${codes[0]}` : `Carton codes: ${codes[0]} → ${codes.at(-1)}`;
}

function workOrderTotals() {
  return state.data.products.reduce((totals, product) => {
    totals.ordered += product.ordered;
    totals.packed += product.packed;
    return totals;
  }, { ordered: 0, packed: 0 });
}

function currentWeight() {
  const carton = getCartonType($("#carton-type").value);
  const cartonWeight = carton?.weight || 0;
  const packageWeight = state.packageRows.reduce((sum, row) => sum + (getPackageItem(row.itemId)?.weight || 0) * row.quantity, 0);
  const productWeight = state.cartonProducts.reduce((sum, row) => sum + (getProduct(row.productId)?.unitWeight || 0) * row.quantity, 0);
  return cartonWeight + packageWeight + productWeight;
}

// Lower/upper allowance in grams: per-unit product tolerances + carton tare tolerance + packaging tolerances (as WeightExpectation on the server).
function currentTolerance() {
  const carton = getCartonType($("#carton-type").value);
  const extra = (carton?.tolerance || 0) + state.packageRows.reduce((sum, row) => sum + (getPackageItem(row.itemId)?.tolerance || 0) * row.quantity, 0);
  return state.cartonProducts.reduce((total, row) => {
    const product = getProduct(row.productId);
    return { lower: total.lower + (product?.lowerTolerance || 0) * row.quantity, upper: total.upper + (product?.upperTolerance || 0) * row.quantity };
  }, { lower: extra, upper: extra });
}

function initializeHeader() {
  const wo = state.data.workOrder;
  $("#work-order-title").textContent = wo.number;
  $("#work-order-subtitle").textContent = `Invoice ${wo.invoice} · Order ${wo.order}`;
  $(".status-badge").textContent = wo.status;
  $("#summary-invoice").textContent = wo.invoice;
  $("#order-facts").innerHTML = [
    ["Customer", wo.customer], ["Destination", wo.destination], ["Order date", wo.orderDate],
    ["Priority", wo.priority], ["Invoice", wo.invoice]
  ].map(([label, value]) => `<div><dt>${label}</dt><dd title="${value}">${value}</dd></div>`).join("");
}

// With no work order selected, the invoice panel lists the work orders that can be packed.
function renderWorkOrderPicker(filter = "") {
  const query = filter.trim().toLowerCase();
  const list = (window.__PACK_POS_WORK_ORDERS__ || []).filter(wo => `${wo.number} ${wo.order}`.toLowerCase().includes(query));
  $("#invoice-products-title").textContent = "Orders";
  document.querySelector(".invoice-panel .step-kicker").textContent = "Select an order";
  $("#line-count").textContent = `${list.length} order${list.length === 1 ? "" : "s"}`;
  const total = window.__PACK_POS_WORK_ORDER_TOTAL__ || 0;
  let caption = document.querySelector("#work-order-caption");
  if (!caption) {
    caption = document.createElement("p");
    caption.id = "work-order-caption";
    caption.className = "work-order-caption";
    document.querySelector(".product-filter").after(caption);
  }
  caption.textContent = total > list.length && !query ? `Remaining orders to pack · showing ${list.length} of ${total}` : "Remaining orders to pack";
  $("#invoice-product-list").innerHTML = list.map(wo => `<a class="invoice-product work-order-link" data-order-id="${escapeHtml(wo.id)}" href="${urls.station}?order=${encodeURIComponent(wo.id)}">
      <div class="product-title"><span class="product-name"><strong>${escapeHtml(wo.number)}</strong><small>${escapeHtml(wo.order)} · ${escapeHtml(wo.status)}</small></span><span class="product-qty"><b>${number(wo.units)}</b> <small>pcs</small><small style="display:block;text-align:right">${number(wo.lines)} product${wo.lines === 1 ? "" : "s"}</small></span></div>
    </a>`).join("") || `<div class="empty-row" style="margin:18px">No orders are ready for packing.</div>`;
}

// Cartons needed to cover the whole ordered quantity at the product's pack size, e.g. 180 pcs at 40 per carton = "4 cartons + 20 assorted".
function cartonsNeededLabel(product) {
  const perCarton = Number(product.unitsPerCarton) || 0;
  if (perCarton <= 0 || !(product.ordered > 0)) return "";
  const full = Math.floor(product.ordered / perCarton);
  const loose = Math.round((product.ordered - full * perCarton) * 1000) / 1000;
  const parts = [];
  if (full) parts.push(`${number(full)} carton${full === 1 ? "" : "s"}`);
  if (loose) parts.push(`${number(loose)} assorted`);
  return `<small class="carton-need" title="${number(perCarton)} units per carton">Total: ${parts.join(" + ")}</small>`;
}

function renderPackedCartons() {
  const section = document.querySelector("#packed-cartons");
  if (!section) return;
  const cartons = state.data.existingCartons || [];
  section.hidden = !state.data.workOrder.id || cartons.length === 0;
  $("#packed-cartons-count").textContent = `${cartons.length} carton${cartons.length === 1 ? "" : "s"}`;
  $("#packed-cartons-list").innerHTML = cartons.map(carton => `<article class="packed-carton-row">
    <span class="packed-carton-icon"><i class="mdi mdi-barcode"></i></span>
    <span class="packed-carton-copy"><strong>${escapeHtml(carton.number)}</strong><small>${escapeHtml(carton.cartonType || carton.type || "—")}</small></span>
    <span class="packed-carton-quantity"><b>${number(carton.units)}</b><small>${Number(carton.units) === 1 ? "item" : "items"}</small></span>
  </article>`).join("");
}

function renderInvoiceProducts(filter = "") {
  if (!state.data.workOrder.id) return renderWorkOrderPicker(filter);
  $("#invoice-products-title").textContent = "Products to pack";
  document.querySelector(".invoice-panel .step-kicker").textContent = "Invoice lines";
  document.querySelector("#work-order-caption")?.remove();
  const query = filter.trim().toLowerCase();
  const products = state.data.products.filter(product => `${product.sku} ${product.name}`.toLowerCase().includes(query));
  $("#invoice-product-list").innerHTML = products.map(product => {
    const percent = product.ordered ? Math.min(100, product.packed / product.ordered * 100) : 0;
    const initials = product.sku.split("-").slice(1, 3).join("-");
    return `<article class="invoice-product">
      <div class="product-title"><span class="product-swatch" style="background:${product.color}">${initials}</span><span class="product-name"><strong>${product.name}</strong><small>${product.sku}</small></span><span class="product-qty"><b>${number(product.packed)}</b> / ${number(product.ordered)} <small>pcs</small>${cartonsNeededLabel(product)}</span></div>
      <div class="line-progress"><span style="width:${percent}%"></span></div>
    </article>`;
  }).join("") || `<div class="empty-row" style="margin:18px">No matching invoice products.</div>`;

  const totals = workOrderTotals();
  $("#line-count").textContent = `${state.data.products.length} lines`;
  $("#invoice-total").textContent = number(totals.ordered);
  $("#invoice-packed").textContent = number(totals.packed);
  $("#invoice-remaining").textContent = number(totals.ordered - totals.packed);
  $("#progress-label").textContent = `${number(totals.packed)} / ${number(totals.ordered)} units`;
  $("#progress-bar").style.width = `${totals.ordered ? totals.packed / totals.ordered * 100 : 0}%`;
  renderPackedCartons();
}

// Loads an order's workspace (or the order picker when id is empty) over AJAX and re-mounts the page without a reload.
let openingOrder = false;
async function openOrder(id, pushHistory = true, filterText = null) {
  if (openingOrder) return;
  openingOrder = true;
  try {
    showToast("Loading…");
    const query = id ? `?order=${encodeURIComponent(id)}` : "";
    const response = await fetch(`${urls.data}${query}`, { headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" } });
    if (!response.ok) throw new Error("load failed");
    const payload = await response.json();
    window.__PACK_POS_DATA__ = payload.data;
    window.__PACK_POS_WORK_ORDERS__ = payload.orders;
    window.__PACK_POS_WORK_ORDER_TOTAL__ = payload.orderTotal;
    mountWorkspace(filterText);
    if (pushHistory) history.pushState({ order: id }, "", `${urls.station}${query}`);
    window.scrollTo({ top: 0 });
  } catch (error) {
    showToast("Could not load the order. Please try again.", true);
  } finally {
    openingOrder = false;
  }
}

// Looks an invoice or order number up on the server, then opens that order.
async function findInvoice(rawTerm) {
  const term = rawTerm.trim();
  if (!term) return;
  try {
    showToast("Searching…");
    const response = await fetch(`${urls.find}?q=${encodeURIComponent(term)}`, { headers: { Accept: "application/json" } });
    if (response.status === 404) return showToast(`No open order or invoice found for "${term}".`, true);
    if (!response.ok) throw new Error("search failed");
    const body = await response.json();
    await openOrder(body.data.id, true, term);
  } catch (error) {
    showToast("Search failed. Please try again.", true);
  }
}

/* Searchable select: hides the native <select> (still the source of truth) and shows a filterable, scrollable list. */
let searchableOpen = null;
function closeSearchable() {
  if (!searchableOpen) return;
  searchableOpen.panel.remove();
  searchableOpen.button.setAttribute("aria-expanded", "false");
  searchableOpen = null;
}
function enhanceSearchableSelect(select) {
  if (!select) return;
  select.nextElementSibling?.classList.contains("ss-button") && select.nextElementSibling.remove();
  if (!document.getElementById("ss-styles")) {
    const style = document.createElement("style");
    style.id = "ss-styles";
    style.textContent = `
      select.ss-native { display: none !important; }
      .ss-button { width: 100%; min-width: 0; min-height: inherit; height: 100%; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 10px; border: 1px solid #cfd6de; border-radius: 8px; background: #fff; color: inherit; font: inherit; font-size: inherit; text-align: left; cursor: pointer; }
      .ss-button span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
      .ss-button::after { content: ""; flex: none; width: 6px; height: 6px; border-right: 2px solid #5b6b79; border-bottom: 2px solid #5b6b79; transform: rotate(45deg) translateY(-2px); }
      .ss-button:focus-visible, .ss-button[aria-expanded="true"] { border-color: #07547e; box-shadow: 0 0 0 3px rgba(7, 84, 126, .1); outline: none; }
      .ss-panel { position: fixed; z-index: 100000; background: #fff; border: 1px solid #cfd6de; border-radius: 9px; box-shadow: 0 10px 28px rgba(0, 40, 70, .18); overflow: hidden; font: 13px/1.35 system-ui, sans-serif; color: #1c2a36; }
      .ss-search { display: block; width: 100%; box-sizing: border-box; padding: 9px 12px; border: 0; border-bottom: 1px solid #e1e6eb; outline: none; font: inherit; }
      .ss-list { max-height: 240px; overflow-y: auto; margin: 0; padding: 4px; list-style: none; }
      .ss-option { padding: 8px 10px; border-radius: 6px; cursor: pointer; }
      .ss-option.active { background: #e8f1f7; }
      .ss-option[aria-selected="true"] { font-weight: 700; color: #07547e; }
      .ss-empty { padding: 10px; color: #7a8896; }`;
    document.head.appendChild(style);
  }
  const button = document.createElement("button");
  button.type = "button";
  button.className = "ss-button";
  button.setAttribute("aria-haspopup", "listbox");
  button.setAttribute("aria-expanded", "false");
  if (select.getAttribute("aria-label")) button.setAttribute("aria-label", select.getAttribute("aria-label"));
  const label = document.createElement("span");
  button.appendChild(label);
  select.classList.add("ss-native");
  select.after(button);
  const sync = () => { label.textContent = select.selectedOptions[0]?.textContent || "Select…"; };
  sync();
  select.addEventListener("change", sync);
  select.ssSync = sync;

  const open = () => {
    closeSearchable();
    const panel = document.createElement("div");
    panel.className = "ss-panel";
    const search = document.createElement("input");
    search.type = "text"; search.className = "ss-search"; search.placeholder = "Search…"; search.autocomplete = "off";
    const list = document.createElement("ul");
    list.className = "ss-list"; list.setAttribute("role", "listbox");
    panel.append(search, list);
    document.body.appendChild(panel);
    const rect = button.getBoundingClientRect();
    panel.style.width = `${Math.max(rect.width, 220)}px`;
    panel.style.left = `${Math.min(rect.left, window.innerWidth - panel.offsetWidth - 8)}px`;
    const below = window.innerHeight - rect.bottom;
    if (below < 300 && rect.top > below) panel.style.bottom = `${window.innerHeight - rect.top + 4}px`;
    else panel.style.top = `${rect.bottom + 4}px`;
    button.setAttribute("aria-expanded", "true");
    searchableOpen = { panel, button };
    let matches = [], active = 0;
    const render = () => {
      const q = search.value.trim().toLowerCase();
      matches = [...select.options].filter(o => !q || o.textContent.toLowerCase().includes(q));
      active = Math.max(0, matches.findIndex(o => o.value === select.value));
      if (q) active = 0;
      list.innerHTML = "";
      if (!matches.length) { list.innerHTML = `<li class="ss-empty">No results found</li>`; return; }
      matches.forEach((o, i) => {
        const li = document.createElement("li");
        li.className = "ss-option" + (i === active ? " active" : "");
        li.setAttribute("role", "option");
        li.setAttribute("aria-selected", String(o.value === select.value));
        li.textContent = o.textContent;
        li.addEventListener("mousedown", e => { e.preventDefault(); choose(o); });
        li.addEventListener("mousemove", () => { if (active !== i) { list.children[active]?.classList.remove("active"); active = i; li.classList.add("active"); } });
        list.appendChild(li);
      });
      list.children[active]?.scrollIntoView({ block: "nearest" });
    };
    const choose = o => {
      const changed = select.value !== o.value;
      select.value = o.value; sync(); closeSearchable(); button.focus();
      if (changed) select.dispatchEvent(new Event("change", { bubbles: true }));
    };
    const move = step => {
      if (!matches.length) return;
      list.children[active]?.classList.remove("active");
      active = (active + step + matches.length) % matches.length;
      list.children[active].classList.add("active");
      list.children[active].scrollIntoView({ block: "nearest" });
    };
    search.addEventListener("input", render);
    search.addEventListener("change", e => e.stopPropagation());
    search.addEventListener("keydown", e => {
      if (e.key === "ArrowDown") { e.preventDefault(); move(1); }
      else if (e.key === "ArrowUp") { e.preventDefault(); move(-1); }
      else if (e.key === "Enter") { e.preventDefault(); if (matches[active]) choose(matches[active]); }
      else if (e.key === "Escape") { e.preventDefault(); closeSearchable(); button.focus(); }
      else if (e.key === "Tab") closeSearchable();
    });
    render();
    search.focus();
  };
  button.addEventListener("click", () => { if (searchableOpen?.button === button) closeSearchable(); else open(); });
}
document.addEventListener("mousedown", event => {
  if (searchableOpen && !searchableOpen.panel.contains(event.target) && !searchableOpen.button.contains(event.target)) closeSearchable();
});
window.addEventListener("resize", closeSearchable);
window.addEventListener("scroll", event => { if (searchableOpen && !searchableOpen.panel.contains(event.target)) closeSearchable(); }, true);

function populateSelects() {
  $("#carton-type").innerHTML = `<option value="" selected disabled>Select a carton type…</option>` + state.data.cartonTypes.map(type => `<option value="${type.id}">${type.code} · ${type.name} · ${grams(type.weight)}</option>`).join("");
  enhanceSearchableSelect($("#carton-type"));
  renderProductSelect();
}

function renderProductSelect() {
  if (!$("#product-suggestions").hidden) renderProductSuggestions();
}

const productSearch = { productId: "", active: -1, matches: [] };

function matchProducts(term) {
  const text = term.trim().toLowerCase();
  if (!text) return state.data.products;
  return state.data.products.filter(product => [product.sku, product.name, product.barcode, ...(product.barcodes || []).map(code => code.barcode)].some(value => String(value || "").toLowerCase().includes(text)));
}

function notFoundMessage(term) { return `No products found with this "${term.trim()}" against current work order or invoice.`; }

function closeProductSuggestions() {
  $("#product-suggestions").hidden = true;
  $("#product-select").setAttribute("aria-expanded", "false");
  productSearch.active = -1;
}

function renderProductSuggestions() {
  const box = $("#product-suggestions");
  const term = $("#product-select").value;
  productSearch.matches = matchProducts(term);
  if (productSearch.active >= productSearch.matches.length) productSearch.active = -1;
  box.innerHTML = productSearch.matches.length
    ? productSearch.matches.map((product, index) => {
      // In sorted mode a product with less than one full carton left cannot be picked; it stays listed, greyed out, with the reason.
      const belowPackSize = state.mode === "sorted" && remaining(product) > 0 && maxSortedCartons(product) < 1;
      const disabled = remaining(product) === 0 || belowPackSize;
      const note = belowPackSize ? `${number(remaining(product))} left · less than ${number(sortedPackSize(product)) || "one"} per carton` : `${remaining(product)} left`;
      return `<div class="product-suggestion${index === productSearch.active ? " active" : ""}" role="option" data-id="${product.id}" aria-disabled="${disabled}"${belowPackSize ? ` title="Less than one full carton remains. Pack it in an assorted carton."` : ""}>${escapeHtml(product.sku)} · ${escapeHtml(product.name)}<small>${note}</small></div>`;
    }).join("")
    : `<div class="product-suggestions-empty">${escapeHtml(notFoundMessage(term))}</div>`;
  box.hidden = false;
  $("#product-select").setAttribute("aria-expanded", "true");
}

// A sorted carton always holds exactly the product's pack size; the operator only chooses how many cartons to make.
function sortedPackSize(product) { return Number(product.unitsPerCarton) > 0 ? Number(product.unitsPerCarton) : 0; }

function maxSortedCartons(product) {
  const size = sortedPackSize(product);
  return size ? Math.floor(remaining(product) / size) : 0;
}

// Picks the product for a sorted carton. No product line is shown; the carton count is limited to the full cartons still needed.
function selectSortedProduct(product) {
  const size = sortedPackSize(product);
  if (!size) { showToast(`${product.name} (${product.sku}) has no units per carton defined, so it cannot be packed as a sorted carton.`, true); return false; }
  if (maxSortedCartons(product) < 1) { showToast(`Only ${number(remaining(product))} pcs of ${product.name} (${product.sku}) remain, less than one full carton of ${number(size)}. Pack them in an assorted carton.`, true); return false; }
  state.cartonProducts.length = 0;
  state.cartonProducts.push({ productId: product.id, quantity: size });
  productSearch.productId = product.id;
  $("#product-select").value = `${product.sku} · ${product.name}`;
  closeProductSuggestions();
  clampSortedCopies();
  renderCartonProducts();
  showToast(`${product.sku}: ${number(size)} units per carton. Enter how many cartons to make.`);
  return true;
}

function clampSortedCopies() {
  const line = state.cartonProducts[0];
  const input = $("#carton-copy-count");
  if (state.mode !== "sorted" || !line || !input) return;
  const product = getProduct(line.productId);
  const max = maxSortedCartons(product);
  input.max = String(max);
  if (Number(input.value) > max) {
    input.value = String(max);
    showToast(`Only ${max} full carton${max === 1 ? "" : "s"} of ${product.sku} remain on this order.`, true);
  }
}

function chooseProduct(productId) {
  const product = getProduct(productId);
  if (!product) return;
  if (remaining(product) === 0) return showToast(limitMessage(product), true);
  productSearch.productId = product.id;
  $("#product-select").value = `${product.sku} · ${product.name}`;
  closeProductSuggestions();
  if (state.mode === "sorted") {
    // Choosing the product is enough; the next step is the number of cartons.
    if (selectSortedProduct(product)) {
      $("#carton-copy-count").focus();
      $("#carton-copy-count").select();
    } else {
      productSearch.productId = "";
      $("#product-select").value = "";
    }
    return;
  }
  $("#product-quantity").focus();
  $("#product-quantity").select();
}

// Enter on either field: use the chosen product, else an exact barcode/SKU, else the highlighted or only suggestion.
function submitProductEntry() {
  const term = $("#product-select").value.trim();
  if (!term) return showToast("Search or scan an invoice product first.", true);
  const lower = term.toLowerCase();
  let product = getProduct(productSearch.productId);
  let multiplier = 1;
  if (!product) {
    // A case barcode carries a unit multiplier: the Quantity box then counts cases.
    for (const item of state.data.products) {
      const code = (item.barcodes || []).find(entry => entry.barcode === term);
      if (code) { product = item; multiplier = code.multiplier; break; }
    }
  }
  if (!product) product = state.data.products.find(item => item.barcode === term || item.sku.toLowerCase() === lower);
  if (!product) {
    const matches = matchProducts(term);
    product = productSearch.active >= 0 ? productSearch.matches[productSearch.active] : matches.length === 1 ? matches[0] : null;
    if (!product && matches.length > 1) return renderProductSuggestions(), showToast("Select one of the matching products.", true);
  }
  if (!product) return renderProductSuggestions(), showToast(notFoundMessage(term), true);
  if (state.mode === "sorted") {
    if (selectSortedProduct(product)) {
      $("#carton-copy-count").focus();
      $("#carton-copy-count").select();
    } else {
      resetProductEntry();
    }
    return;
  }
  if (!addProduct(product.id, Number($("#product-quantity").value) * multiplier)) return;
  resetProductEntry();
}

function resetProductEntry() {
  productSearch.productId = "";
  $("#product-select").value = "";
  $("#product-quantity").value = "1";
  closeProductSuggestions();
  $("#product-select").focus();
}

function renderRegisteredBox() {
  renderCartonCodePreview();
  renderWeight();
}

function renderPackageRows() {
  $("#package-items").innerHTML = state.packageRows.length ? state.packageRows.map((row, index) => `<div class="dynamic-row" data-index="${index}">
    <select aria-label="Packaging item">${state.data.packagingItems.map(item => `<option value="${item.id}" ${item.id === row.itemId ? "selected" : ""}>${item.code} · ${item.name} · ${item.weight} g</option>`).join("")}</select>
    <input type="number" min="1" value="${row.quantity}" aria-label="Packaging item quantity">
    <button class="remove-row" type="button" aria-label="Remove packaging item">×</button>
  </div>`).join("") : `<div class="empty-row">No additional packaging items. Add bubble wrap, dividers, paper, or silica if required.</div>`;
  $$("#package-items select").forEach(enhanceSearchableSelect);
}

function renderCartonProducts() {
  $("#carton-products").innerHTML = state.mode === "sorted" ? "" : state.cartonProducts.length ? state.cartonProducts.map((line, index) => {
    const product = getProduct(line.productId);
    return `<div class="carton-product" data-index="${index}"><span><strong>${product.sku} · ${product.name}</strong><small>${grams(product.unitWeight)} each · ${remaining(product)} invoice units available</small></span><input class="carton-product-quantity" type="number" min="1" max="${remaining(product)}" step="1" value="${line.quantity}" aria-label="Quantity for ${product.sku}"><button class="remove-row" type="button" aria-label="Remove product">×</button></div>`;
  }).join("") : `<div class="empty-row">No products added. Search an invoice product by code or name, or scan a barcode.</div>`;
  renderProductSelect();
  renderWeight();
}

function renderWeight() {
  const weight = currentWeight();
  $("#system-weight").textContent = grams(weight);
  const tolerance = currentTolerance();
  const fmt = value => Number(value.toFixed(3)).toLocaleString("en-US");
  $("#weight-tolerance").textContent = tolerance.lower === tolerance.upper ? `± ${fmt(tolerance.upper)} g` : `− ${fmt(tolerance.lower)} / + ${fmt(tolerance.upper)} g`;
  $("#weight-range").textContent = weight > 0 ? `${fmt(weight - tolerance.lower)} – ${fmt(weight + tolerance.upper)} g` : "—";
  {
    const carton = getCartonType($("#carton-type").value);
    const packageWeight = state.packageRows.reduce((sum, row) => sum + (getPackageItem(row.itemId)?.weight || 0) * row.quantity, 0);
    const productWeight = state.cartonProducts.reduce((sum, row) => sum + (getProduct(row.productId)?.unitWeight || 0) * row.quantity, 0);
    $("#weight-breakdown").textContent = `${grams(productWeight)} products + ${grams(carton?.weight || 0)} carton + ${grams(packageWeight)} packaging.`;
  }
  renderSummary();
}

function renderHistory() {
  const historyBody = document.querySelector("#carton-history-body");
  if (historyBody) historyBody.innerHTML = state.data.existingCartons.map(carton => `<tr><td>${carton.number}</td><td>${carton.type}</td><td>${number(carton.units)} units</td><td>${grams(carton.weight)}</td><td><span class="table-status ${carton.status === "SEALED" ? "sealed" : ""}">${carton.status}</span></td></tr>`).join("");
}

function renderSummary() {
  const totals = workOrderTotals();
  const packedPercent = totals.packed / totals.ordered * 100;
  $("#summary-packed").textContent = number(totals.packed);
  $("#summary-remaining").textContent = number(totals.ordered - totals.packed);
  $("#summary-cartons").textContent = number(state.data.existingCartons.length);
  $("#summary-current-units").textContent = number(currentUnits());
  $("#summary-current-weight").textContent = grams(currentWeight());
  $("#summary-progress-bar").style.width = `${packedPercent}%`;
}

function chooseMode(mode) {
  const previous = state.mode;
  state.mode = mode;
  if (state.activeDraft) state.activeDraft.mode = mode;
  $$(".mode-card").forEach(card => card.classList.toggle("selected", $("input", card).value === mode));
  // Construction is always available; carton contents and actions follow the selected mode.
  const chosen = mode === "sorted" || mode === "assorted";
  [".product-heading", ".product-entry", "#weight-card", "#build-actions"].forEach(selector => { const element = $(selector); if (element) element.hidden = !chosen; });
  const copies = $("#sorted-copies");
  if (copies) copies.hidden = mode !== "sorted";
  const quantity = $("#product-quantity-field");
  if (quantity) quantity.hidden = mode === "sorted";
  const productList = $("#carton-products");
  if (productList) productList.hidden = mode !== "assorted";
  if (mode !== "sorted" && $("#carton-copy-count")) $("#carton-copy-count").value = "1";
  // Sorted and assorted cartons build their contents differently, so changing type starts the contents again.
  if (previous !== mode && state.cartonProducts.length && (mode === "sorted" || previous === "sorted")) {
    state.cartonProducts.length = 0;
    productSearch.productId = "";
    $("#product-select").value = "";
    renderCartonProducts();
    if (mode === "sorted") showToast("Choose the product for the sorted carton.");
  }
  renderWeight();
}

function syncFinishAction() {
  const action = document.querySelector("#session-finish-action");
  if (action) action.hidden = state.step === 2 || !state.sessionCartons.length;
}

async function confirmCartons(event) {
  const button = event.currentTarget;
  const finish = () => {
    button.disabled = true;
    button.textContent = "Cartons confirmed";
    showToast(`${state.sessionCartons.length} cartons confirmed for ${state.data.workOrder.invoice}.`);
  };
  button.disabled = true;
  button.textContent = "Saving cartons…";
  const pending = state.sessionSpecs.filter(item => !item.saved);
  if (!pending.length) return finish();
  state.batchKey = state.batchKey || (crypto.randomUUID ? crypto.randomUUID() : "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c => (c ^ Math.random() * 16 >> c / 4).toString(16)));
  try {
    const response = await fetch(urls.batch, {
      method: "POST",
      headers: { Accept: "application/json", "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
      body: JSON.stringify({ order_id: state.data.workOrder.id, idempotency_key: state.batchKey, cartons: pending.map(item => item.spec) })
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) {
      const firstError = body.errors ? Object.values(body.errors).flat()[0] : null;
      throw new Error(firstError || body.message || "Cartons could not be saved.");
    }
    const saved = body.data.cartons;
    const created = pending.flatMap(item => item.cartons);
    created.forEach((carton, index) => {
      const record = saved[index];
      if (!record) return;
      Object.assign(carton, {
        weightFlag: record.weight_flag || null,
        number: record.carton_number, boxNumber: record.box_number, id: record.id, status: "CONTENTS VERIFIED", systemWeight: Number(record.system_weight_g),
        toleranceLower: Number(record.weight_tolerance_lower_g), toleranceUpper: Number(record.weight_tolerance_upper_g), weight: Number(record.manual_weight_g || record.system_weight_g)
      });
    });
    // A later confirm in this session (after more cartons are added) must be a new batch that skips what is already saved.
    pending.forEach(item => { item.saved = true; item.draft?.root.classList.add("saved"); });
    state.batchKey = null;
    renderReview();
    renderHistory();
    finish();
    // Once the labels have gone to the printer, return to the package station.
    if (await printLabelsOnce(created.filter(Boolean))) await openOrder("");
  } catch (error) {
    button.disabled = false;
    button.textContent = "Confirm all cartons";
    showToast(error.message, true);
  }
}

function goToStep(step) {
  if (step === 2 && !state.sessionCartons.length) {
    showToast("Add at least one carton before finishing the packing session.", true);
    return;
  }
  state.step = step;
  if (state.activeDraft) state.activeDraft.step = step;
  document.querySelector("#carton-accordion").hidden = step === 2;
  syncFinishAction();
  document.querySelector("#session-review").hidden = step !== 2;
  document.querySelectorAll("#session-wizard li").forEach(item => {
    const itemStep = Number(item.dataset.step);
    item.classList.toggle("active", itemStep === step);
    item.classList.toggle("complete", itemStep < step);
    $("li > span", item).textContent = itemStep < step ? "✓" : itemStep;
  });
  if (step === 2) renderReview();
  document.querySelector(".session-workflow").scrollIntoView({ behavior: "smooth", block: "start" });
}

function addPackageRow() {
  if (!state.data.packagingItems.length) return showToast("No packaging materials are set up. Add them in Packaging Setup.", true);
  const unused = state.data.packagingItems.find(item => !state.packageRows.some(row => row.itemId === item.id));
  state.packageRows.push({ itemId: unused?.id || state.data.packagingItems[0].id, quantity: 1 });
  renderPackageRows();
  renderWeight();
}

function addProduct(productId, quantity = 1, fromScanner = false) {
  const product = getProduct(productId);
  if (!product) return showToast("Choose a valid invoice product.", true), false;
  const existing = state.cartonProducts.find(line => line.productId === productId);
  const alreadyInDraft = existing?.quantity || 0;
  const requested = Number(quantity);
  if (!Number.isInteger(requested) || requested < 1) return showToast("Quantity must be at least 1.", true), false;
  if (alreadyInDraft + requested > remaining(product)) return showToast(limitMessage(product), true), false;
  if (existing) existing.quantity += requested;
  else state.cartonProducts.push({ productId, quantity: requested });
  renderCartonProducts();
  showToast(fromScanner ? `${product.sku} added by simulated scan.` : `${requested} × ${product.sku} added to this carton.`);
  return true;
}

function validateCurrentCarton() {
  const lines = currentProducts();
  if (!state.mode) return showToast("Choose a sorted or assorted carton first.", true), false;
  if (!lines.length) return showToast("Add at least one product to the carton.", true), false;
  if (state.mode === "sorted" && lines.length > 1) return showToast("A sorted carton holds one product. Choose assorted to pack several products together.", true), false;
  if (state.mode === "sorted" && Number(lines[0].quantity) !== sortedPackSize(getProduct(lines[0].productId))) return showToast("Choose the product again to set its carton size.", true), false;
  const copies = currentCopyCount();
  if (!Number.isInteger(copies) || copies < 1 || copies > 100) {
    showToast("Carton copies must be a whole number between 1 and 100.", true);
    return false;
  }
  for (const line of lines) {
    const product = getProduct(line.productId);
    if (!product || line.quantity * copies > remaining(product)) {
      showToast(product ? limitMessage(product) : "A product is not on this order.", true);
      return false;
    }
  }
  if (!$("#carton-type").value) return showToast("Select the primary carton pack.", true), false;
  return true;
}

function renderReview() {
  const cartons = state.sessionCartons;
  const totalUnits = cartons.reduce((sum, carton) => sum + Number(carton.units), 0);
  const totalWeight = cartons.reduce((sum, carton) => sum + Number(carton.weight), 0);
  $("#review-title").textContent = "Review added cartons";
  $("#review-description").textContent = `${cartons.length} carton${cartons.length === 1 ? "" : "s"} are ready to confirm for this order.`;
  $("#review-grid").innerHTML = [
    ["Cartons", cartons.length], ["Sorted", cartons.filter(carton => carton.type === "Sorted").length], ["Assorted", cartons.filter(carton => carton.type === "Assorted").length], ["Total units", totalUnits]
  ].map(([label, value]) => `<div class="review-stat"><small>${label}</small><strong>${value}</strong></div>`).join("");
  $("#review-unit-count").textContent = `${cartons.length} cartons · ${grams(totalWeight)}`;
  $("#review-products").innerHTML = cartons.map(carton => `<div class="review-product-line carton-review-line" data-carton-number="${carton.number}"><span><b>${carton.number}</b><small>${carton.type}${carton.weightFlag ? ` · <em class="weight-flag">${WEIGHT_FLAG_LABEL[carton.weightFlag] || carton.weightFlag}</em>` : ""}</small></span><strong><b>${number(carton.units)}</b> units</strong><strong><b>${grams(carton.weight)}</b> weight</strong></div>`).join("");
}

function resetDraft(resetMode = true) {
  state.cartonProducts = [];
  state.packageRows = [];
  if (state.activeDraft) {
    state.activeDraft.cartonProducts = state.cartonProducts;
    state.activeDraft.packageRows = state.packageRows;
  }
  $("#manual-weight").value = "";
  if ($("#carton-copy-count")) $("#carton-copy-count").value = "1";
  $("#product-quantity").value = "1";
  $("#product-select").value = "";
  productSearch.productId = "";
  if (resetMode) {
    state.mode = "";
    $$("input[name='carton-mode']").forEach(input => { input.checked = false; });
    chooseMode("");
  }
  renderCartonProducts();
  renderPackageRows();
  renderWeight();
  goToStep(1);
}

function addCartonDraft() {
  syncActiveDraft();
  const wrapper = document.querySelector("#carton-accordion");
  const template = document.createElement("template");
  template.innerHTML = state.builderTemplate.trim();
  const root = template.content.firstElementChild;
  const draftNumber = `C${String(state.nextCarton).padStart(3, "0")}`;
  state.nextCarton += 1;
  wrapper.appendChild(root);
  registerDraft(root, draftNumber);
  populateSelects();
  renderRegisteredBox();
  renderPackageRows();
  renderCartonProducts();
  chooseMode("");
  bindBuilderEvents(root);
  root.scrollIntoView({ behavior: "smooth", block: "start" });
  showToast(`Draft carton ${draftNumber} added.`);
}

// The operator's measured weight (manual entry, else the machine reading) against the system weight and its tolerance range.
function currentWeightFlag() {
  const entered = Number($("#manual-weight").value || 0) || Number($("#machine-weight")?.dataset.grams || 0);
  if (!(entered > 0)) return null;
  const weight = currentWeight();
  const tolerance = currentTolerance();
  const lower = weight - tolerance.lower;
  const upper = weight + tolerance.upper;
  if (entered > upper) return { flag: "OVERWEIGHT", entered, lower, upper, difference: entered - upper };
  if (entered < lower) return { flag: "UNDERWEIGHT", entered, lower, upper, difference: lower - entered };
  return null;
}

const WEIGHT_FLAG_LABEL = { OVERWEIGHT: "Overweight", UNDERWEIGHT: "Underweight" };

async function confirmWeightMismatch(result) {
  if (!window.Swal) {
    showToast("The confirmation dialog could not be loaded. Reload the page and try again.", true);
    return false;
  }
  const over = result.flag === "OVERWEIGHT";
  const answer = await window.Swal.fire({
    icon: "warning",
    title: `Carton is ${over ? "overweight" : "underweight"}`,
    html: `<p style="margin:0 0 8px">The entered weight <b>${number(result.entered)} g</b> is <b>${number(Math.round(result.difference * 1000) / 1000)} g ${over ? "above" : "below"}</b> the allowed range <b>${number(Math.round(result.lower))} – ${number(Math.round(result.upper))} g</b>.</p><p style="margin:0">Add this carton anyway? It will be saved and flagged as <b>${WEIGHT_FLAG_LABEL[result.flag]}</b>.</p>`,
    showCancelButton: true,
    confirmButtonText: "Yes, add carton",
    cancelButtonText: "Review weight",
    confirmButtonColor: "#07956f",
    cancelButtonColor: "#667085",
    reverseButtons: true,
    focusCancel: true
  });
  return answer.isConfirmed;
}

// Takes an added (not yet saved) carton back into editing: its units return to the invoice balance and the draft becomes editable again.
function reopenDraft(draft) {
  const entry = draft.entry;
  if (!draft.completed || !entry) return;
  if (entry.saved) return showToast("Saved cartons can't be edited here.", true);
  entry.lines.forEach(line => {
    const product = getProduct(line.productId);
    if (product) product.packed = Math.max(0, product.packed - line.quantity * entry.copies);
  });
  const removed = new Set(entry.cartons);
  state.sessionCartons = state.sessionCartons.filter(carton => !removed.has(carton));
  state.data.existingCartons = state.data.existingCartons.filter(carton => !removed.has(carton));
  state.sessionSpecs = state.sessionSpecs.filter(item => item !== entry);
  draft.entry = null;
  draft.completed = false;
  draft.root.classList.remove("completed");
  draft.root.querySelector(".draft-pill").innerHTML = `Draft carton <b id="draft-number">${draft.number}</b>`;
  draft.root.querySelectorAll("input, select, button").forEach(control => { control.disabled = false; });
  activateDraft(draft);
  renderRegisteredBox();
  renderPackageRows();
  renderCartonProducts();
  renderInvoiceProducts();
  renderHistory();
  renderWeight();
  syncFinishAction();
  showToast(`Carton ${draft.number} reopened. Update it and add it again.`);
}

async function createCarton() {
  if (state.confirmingWeight) return;
  if (!validateCurrentCarton()) return;
  const result = currentWeightFlag();
  state.weightAck = null;
  if (result) {
    state.confirmingWeight = true;
    const confirmed = await confirmWeightMismatch(result).finally(() => { state.confirmingWeight = false; });
    if (!confirmed) return;
    state.weightAck = result;
  }
  addValidatedCarton();
}

function addValidatedCarton() {
  const weightAck = state.weightAck;
  state.weightAck = null;
  const lines = currentProducts();
  const copies = currentCopyCount();
  const units = unitsPerCarton();
  const calculatedWeight = currentWeight();
  const cartonMode = state.mode;
  const cartonType = getCartonType($("#carton-type").value);
  lines.forEach(line => { const product = getProduct(line.productId); product.packed = Math.min(product.ordered, product.packed + Number(line.quantity) * copies); });
  const cartonNumbers = Array.from({ length: copies }, (_, index) => copies > 1
    ? `${state.data.workOrder.number}-${state.activeDraft.number}-${index + 1}`
    : `${state.data.workOrder.number}-${state.activeDraft.number}`);
  const manual = Number($("#manual-weight").value || 0);
  const contents = lines.map(line => { const product = getProduct(line.productId); return { sku: product.sku, name: product.name, brand: product.brand || "—", quantity: Number(line.quantity), netWeight: Number(line.quantity) * Number(product.unitWeight || 0) }; });
  const machineWeight = Number($("#machine-weight")?.dataset.grams || 0);
  const spec = {
    packaging_mode: cartonMode === "sorted" ? "SORTED" : "ASSORTED",
    draft_reference: state.activeDraft.number,
    copies,
    carton_type_id: $("#carton-type").value,
    items: lines.map(line => ({ order_item_id: line.productId, quantity: Number(line.quantity) })),
    materials: state.packageRows.filter(row => Number(row.quantity) > 0).map(row => ({ material_id: row.itemId, quantity: Number(row.quantity) })),
    ...(machineWeight > 0 ? { machine_weight_g: machineWeight } : {}),
    ...(manual > 0 ? { manual_weight_g: manual } : {}),
    ...(weightAck ? { weight_flag_acknowledged: true } : {})
  };
  cartonNumbers.slice().reverse().forEach(cartonNumber => {
    const carton = { number: cartonNumber, weightFlag: weightAck?.flag || null, units, type: cartonMode === "sorted" ? "Sorted" : "Assorted", cartonType: cartonType?.name || cartonType?.code || "—", status: "CONTENTS VERIFIED", weight: manual || calculatedWeight, invoice: state.data.workOrder.invoice, workOrder: state.data.workOrder.number, destination: state.data.workOrder.destination, contents };
    state.data.existingCartons.unshift(carton);
    state.sessionCartons.unshift(carton);
  });
  const sessionEntry = { spec, draft: state.activeDraft, copies, lines: lines.map(line => ({ productId: line.productId, quantity: Number(line.quantity) })), cartons: cartonNumbers.map(number => state.sessionCartons.find(carton => carton.number === number)) };
  state.sessionSpecs.push(sessionEntry);
  state.activeDraft.entry = sessionEntry;
  syncFinishAction();
  state.activeDraft.completed = true;
  state.activeDraft.root.classList.add("completed");
  state.activeDraft.root.querySelector(".draft-pill").innerHTML = copies > 1 ? `<b>${copies}</b> cartons added` : `Carton <b>${cartonNumbers[0]}</b> added`;
  state.activeDraft.root.querySelectorAll("input, select, button:not(.edit-carton-action)").forEach(control => { control.disabled = true; });
  const confirmButton = document.querySelector("#confirm-cartons");
  confirmButton.disabled = false;
  confirmButton.textContent = "Confirm all cartons";
  renderInvoiceProducts();
  renderHistory();
  addCartonDraft();
  const codeMessage = copies > 1 ? `${cartonNumbers[0]} to ${cartonNumbers.at(-1)}` : cartonNumbers[0];
  showToast(`${copies} carton${copies === 1 ? "" : "s"} added: ${codeMessage}.`);
}

let toastTimer;
function showToast(message, error = false) {
  const toast = $("#toast");
  toast.textContent = message;
  toast.classList.toggle("error", error);
  toast.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove("show"), 2800);
}

function bindBuilderEvents(root) {
  root.querySelector(".edit-carton-action").addEventListener("click", event => {
    event.stopPropagation();
    const draft = state.drafts.find(item => item.root === root);
    if (draft) reopenDraft(draft);
  });
  root.querySelector(".builder-header").addEventListener("click", event => {
    if (event.target.closest("button")) return;
    const draft = state.drafts.find(item => item.root === root);
    if (draft) toggleDraft(draft);
  });
  root.querySelector(".builder-header").addEventListener("keydown", event => {
    if (event.target.closest("button") || !["Enter", " "].includes(event.key)) return;
    event.preventDefault();
    const draft = state.drafts.find(item => item.root === root);
    if (draft) toggleDraft(draft);
  });
  $$("input[name='carton-mode']").forEach(input => input.addEventListener("change", () => chooseMode(input.value)));
  $("#carton-copy-count").addEventListener("input", () => { clampSortedCopies(); renderCartonCodePreview(); renderWeight(); });
  $("#carton-type").addEventListener("change", renderWeight);
  $("#manual-weight").addEventListener("input", renderSummary);
  $("#add-package-item").addEventListener("click", addPackageRow);
  $("#package-items").addEventListener("change", event => {
    const row = event.target.closest(".dynamic-row"); if (!row) return;
    const item = state.packageRows[Number(row.dataset.index)];
    if (event.target.matches("select")) item.itemId = event.target.value;
    if (event.target.matches("input")) item.quantity = Math.max(1, Number(event.target.value || 1));
    renderWeight();
  });
  $("#package-items").addEventListener("click", event => {
    const row = event.target.closest(".dynamic-row"); if (!row || !event.target.matches(".remove-row")) return;
    state.packageRows.splice(Number(row.dataset.index), 1); renderPackageRows(); renderWeight();
  });
  const productInput = $("#product-select");
  productInput.addEventListener("input", () => {
    productSearch.productId = "";
    productSearch.active = -1;
    // Typing over a chosen sorted product un-selects it until another one is picked.
    if (state.mode === "sorted" && state.cartonProducts.length) { state.cartonProducts.length = 0; renderCartonProducts(); }
    renderProductSuggestions();
  });
  productInput.addEventListener("focus", () => {
    // In sorted mode the box behaves like a picker: focusing it selects the text so another product can replace the current one.
    if (state.mode === "sorted" && productSearch.productId) productInput.select();
    if (!productSearch.productId || state.mode === "sorted") renderProductSuggestions();
  });
  productInput.addEventListener("keydown", event => {
    const open = !$("#product-suggestions").hidden;
    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
      event.preventDefault();
      if (!open) renderProductSuggestions();
      const count = productSearch.matches.length; if (!count) return;
      productSearch.active = (productSearch.active + (event.key === "ArrowDown" ? 1 : -1) + count) % count;
      renderProductSuggestions();
      $(".product-suggestion.active", $("#product-suggestions"))?.scrollIntoView({ block: "nearest" });
    } else if (event.key === "Enter") { event.preventDefault(); submitProductEntry(); }
    else if (event.key === "Escape") closeProductSuggestions();
  });
  $("#product-suggestions").addEventListener("mousedown", event => {
    event.preventDefault();
    const option = event.target.closest(".product-suggestion"); if (option) chooseProduct(option.dataset.id);
  });
  productInput.addEventListener("blur", () => closeProductSuggestions());
  $("#product-quantity").addEventListener("keydown", event => { if (event.key === "Enter") { event.preventDefault(); submitProductEntry(); } });
  $("#carton-copy-count").addEventListener("keydown", event => {
    if (event.key !== "Enter") return;
    event.preventDefault();
    // A sorted carton's product is already chosen, so Enter here only confirms the copy count.
    if (state.mode === "sorted" && state.cartonProducts.length) return;
    submitProductEntry();
  });
  $("#carton-products").addEventListener("click", event => {
    const row = event.target.closest(".carton-product"); if (!row || !event.target.matches(".remove-row")) return;
    state.cartonProducts.splice(Number(row.dataset.index), 1); renderCartonProducts();
  });
  $("#carton-products").addEventListener("input", event => {
    if (!event.target.matches(".carton-product-quantity")) return;
    const row = event.target.closest(".carton-product");
    const line = state.cartonProducts[Number(row.dataset.index)];
    const product = getProduct(line.productId);
    const requested = Number(event.target.value);
    if (!Number.isInteger(requested) || requested < 1) return;
    const available = remaining(product);
    if (requested > available) {
      line.quantity = available;
      event.target.value = available;
      showToast(limitMessage(product), true);
    } else {
      line.quantity = requested;
    }
    renderWeight();
  });
  $("#carton-products").addEventListener("change", event => {
    if (!event.target.matches(".carton-product-quantity")) return;
    const line = state.cartonProducts[Number(event.target.closest(".carton-product").dataset.index)];
    if (!Number.isInteger(Number(event.target.value)) || Number(event.target.value) < 1) {
      event.target.value = line.quantity;
      showToast("Product quantity must be at least 1.", true);
    }
  });
  $(".add-carton-action").addEventListener("click", createCarton);
  $("#clear-draft").addEventListener("click", () => { resetDraft(true); showToast("Draft carton cleared."); });
}

function bindSummaryToggle() {
  const dock = document.querySelector(".pos-summary");
  const toggle = dock && dock.querySelector(".summary-toggle");
  if (!toggle) return;
  const apply = collapsed => {
    dock.classList.toggle("collapsed", collapsed);
    toggle.textContent = collapsed ? "⌃ Summary" : "⌄";
    toggle.setAttribute("aria-expanded", String(!collapsed));
    toggle.title = collapsed ? "Show order summary" : "Hide work order summary";
  };
  let saved = false;
  try { saved = localStorage.getItem("packPosSummaryCollapsed") === "1"; } catch (error) {}
  apply(saved);
  toggle.addEventListener("click", () => {
    const collapsed = !dock.classList.contains("collapsed");
    apply(collapsed);
    try { localStorage.setItem("packPosSummaryCollapsed", collapsed ? "1" : "0"); } catch (error) {}
  });
}

function bindEvents() {
  bindSummaryToggle();
  $("#product-filter").addEventListener("input", event => { if (!state.data.workOrder.id) renderWorkOrderPicker(event.target.value); });
  $("#product-filter").addEventListener("keydown", event => {
    if (event.key === "Enter") { event.preventDefault(); findInvoice(event.target.value); }
  });
  $("#product-filter").addEventListener("search", event => {
    if (event.target.value.trim()) findInvoice(event.target.value);
  });
  $("#invoice-product-list").addEventListener("click", event => {
    const link = event.target.closest("a.work-order-link");
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
    event.preventDefault();
    openOrder(link.dataset.orderId);
  });
  window.addEventListener("popstate", () => openOrder(new URLSearchParams(window.location.search).get("order") || "", false));
  document.querySelector("#finish-cartons").addEventListener("click", () => goToStep(2));
  document.querySelector("#collapse-all-cartons").addEventListener("click", collapseAllDrafts);
  document.querySelector("#back-to-cartons").addEventListener("click", () => goToStep(1));
  document.querySelector("#review-products").addEventListener("click", event => {
    const button = event.target.closest(".print-carton-label");
    if (!button) return;
    const row = button.closest(".carton-review-line");
    const carton = state.sessionCartons.find(item => item.number === row.dataset.cartonNumber);
    if (carton) printCartonLabel(carton);
  });
  document.querySelector("#confirm-cartons").addEventListener("click", confirmCartons);
  
}

// Totals and the carton builder only make sense once a work order is selected.
function syncWorkOrderVisibility() {
  const selected = Boolean(state.data.workOrder.id);
  document.querySelector(".pack-pos")?.classList.toggle("no-work-order", !selected);
  document.querySelector(".invoice-totals").hidden = !selected;
  document.querySelector("#carton-accordion").hidden = !selected;
  document.querySelector(".session-workflow-main").hidden = !selected;
}

// Builds (or rebuilds) the whole workspace from window.__PACK_POS_*; every piece of per-order state is reset first.
function mountWorkspace(filterText = null) {
  const host = document.querySelector("#carton-accordion");
  Object.assign(state, { mode: "", step: 1, packageRows: [], cartonProducts: [], nextCarton: 1, drafts: [], activeDraft: null, sessionCartons: [], sessionSpecs: [], batchKey: null });
  state.data = structuredClone({ packagingItems: [], ...window.__PACK_POS_DATA__ });
  if (!state.builderTemplate) state.builderTemplate = host.querySelector(".builder-panel").outerHTML;
  host.innerHTML = state.builderTemplate;
  const initialRoot = host.querySelector(".builder-panel");
  resetSessionUi();
  productSearch.productId = "";
  productSearch.active = -1;
  // An open order keeps its code in the search box (what was typed, else the order number); the picker starts empty.
  document.querySelector("#product-filter").value = state.data.workOrder.id ? (filterText ?? state.data.workOrder.number) : "";
  registerDraft(initialRoot, `C${String(state.nextCarton).padStart(3, "0")}`);
  state.nextCarton += 1;
  initializeHeader();
  populateSelects();
  renderInvoiceProducts();
  renderRegisteredBox();
  renderPackageRows();
  renderCartonProducts();
  chooseMode("");
  renderHistory();
  renderSummary();
  syncWorkOrderVisibility();
  bindBuilderEvents(initialRoot);
}

// Puts the wizard, review panel and confirm button back to their fresh-session look.
function resetSessionUi() {
  document.querySelector("#session-review").hidden = true;
  document.querySelector("#session-finish-action").hidden = true;
  document.querySelector("#review-grid").innerHTML = "";
  document.querySelector("#review-products").innerHTML = "";
  document.querySelectorAll("#session-wizard li").forEach(item => {
    const itemStep = Number(item.dataset.step);
    item.classList.toggle("active", itemStep === 1);
    item.classList.remove("complete");
    item.querySelector(":scope > span").textContent = itemStep;
  });
  const confirm = document.querySelector("#confirm-cartons");
  confirm.disabled = false;
  confirm.textContent = "Confirm all cartons";
}

async function init() {
  const host = document.querySelector("#carton-accordion");
  if (!host || host.dataset.packPosReady) return;
  host.dataset.packPosReady = "1";
  mountWorkspace();
  bindEvents();
}

init();
})();
