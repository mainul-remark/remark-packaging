// Carton label (100 x 150 mm). Shared by the package station (printed after saving cartons) and the carton label page.
// carton: { number, boxNumber, invoice, type, destination, weight, toleranceLower, toleranceUpper, packedAt?, contents: [{ brand, name, sku, quantity, netWeight }] }
// Fixed text (company group, from, route) comes from window.__PACK_POS_LABEL__.
(function () {
const number = value => new Intl.NumberFormat("en-US").format(value);

const code128Patterns = [
  "212222","222122","222221","121223","121322","131222","122213","122312","132212","221213","221312","231212",
  "112232","122132","122231","113222","123122","123221","223211","221132","221231","213212","223112","312131",
  "311222","321122","321221","312212","322112","322211","212123","212321","232121","111323","131123","131321",
  "112313","132113","132311","211313","231113","231311","112133","112331","132131","113123","113321","133121",
  "313121","211331","231131","213113","213311","213131","311123","311321","331121","312113","312311","332111",
  "314111","221411","431111","111224","111422","121124","121421","141122","141221","112214","112412","122114",
  "122411","142112","142211","241211","221114","413111","241112","134111","111242","121142","121241","114212",
  "124112","124211","411212","421112","421211","212141","214121","412121","111143","111341","131141","114113",
  "114311","411113","411311","113141","114131","311141","411131","211412","211214","211232","2331112"
];

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, character => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" })[character]);
}

function code128Svg(value) {
  const characters = [...String(value)];
  if (characters.some(character => character.charCodeAt(0) < 32 || character.charCodeAt(0) > 126)) throw new Error("Unsupported barcode character");
  const values = characters.map(character => character.charCodeAt(0) - 32);
  const checksum = (104 + values.reduce((sum, item, index) => sum + item * (index + 1), 0)) % 103;
  const patterns = [code128Patterns[104], ...values.map(item => code128Patterns[item]), code128Patterns[checksum], code128Patterns[106]];
  const moduleWidth = 2;
  const quietZone = 10;
  const width = (patterns.reduce((sum, pattern) => sum + [...pattern].reduce((total, digit) => total + Number(digit), 0), 0) + quietZone * 2) * moduleWidth;
  let x = quietZone * moduleWidth;
  const bars = [];
  patterns.forEach(pattern => {
    [...pattern].forEach((digit, index) => {
      const segmentWidth = Number(digit) * moduleWidth;
      if (index % 2 === 0) bars.push(`<rect x="${x}" y="0" width="${segmentWidth}" height="72"/>`);
      x += segmentWidth;
    });
  });
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${width} 72" role="img" aria-label="Barcode ${escapeHtml(value)}">${bars.join("")}</svg>`;
}

const kg = grams => `${(Number(grams || 0) / 1000).toFixed(2)} KG`;

// Tall 100 x 150 mm carton label. The scan code is a Code 128 barcode of the carton number (no QR code).
function cartonLabelHtml(carton) {
  const label = window.__PACK_POS_LABEL__ || {};
  const boxNumber = carton.boxNumber || "—";
  const lower = Number(carton.toleranceLower || 0);
  const upper = Number(carton.toleranceUpper || 0);
  const tolerance = lower === upper ? `± ${number(upper)} g` : `− ${number(lower)} / + ${number(upper)} g`;
  const contents = carton.contents || [];
  const net = contents.reduce((sum, item) => sum + Number(item.netWeight || 0), 0);
  const packed = new Date(carton.packedAt || Date.now()).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
  const compact = contents.length > 18 ? " dense" : contents.length > 10 ? " compact" : "";
  const rows = contents.map((item, index) => `<tr><td class="c">${index + 1}</td><td>${escapeHtml(item.brand)}</td><td>${escapeHtml(item.name)}</td><td>${escapeHtml(item.sku)}</td><td class="c">${escapeHtml(number(item.quantity))}</td><td class="c">${escapeHtml(kg(item.netWeight))}</td></tr>`).join("");
  return `<div class="label"><div class="frame${compact}">
    <div class="top">
      <div class="brand"><b>REMAR<span>K<sup>*</sup></span></b><small>${escapeHtml(label.group || "")}</small></div>
      <div class="box"><small>Invoice No.</small><strong>${escapeHtml(carton.invoice || "—")}</strong></div>
      <div class="box boxno"><small>Box No.</small><strong>${escapeHtml(boxNumber)}</strong></div>
    </div>
    <div class="route">
      <div><small>From (Packing Location)</small><strong>${escapeHtml(label.from || "—")}</strong></div>
      <div><small>Destination</small><strong>${escapeHtml(carton.destination || "—")}</strong></div>
      <div class="wide"><small>Route</small><strong>${escapeHtml(label.route || "—")}</strong></div>
    </div>
    <table class="items"><colgroup><col class="sl"><col class="brand"><col><col class="sku"><col class="qty"><col class="net"></colgroup><thead><tr><th class="c">SL</th><th>Brand Name</th><th>Product / SKU Name</th><th>SKU Code</th><th class="c">Qty</th><th class="c">Net Weight</th></tr></thead><tbody>${rows}</tbody></table>
    <div class="stats">
      <div><small>Packaging Date</small><strong>${escapeHtml(packed)}</strong></div>
      <div><small>Gross Weight</small><strong>${escapeHtml(kg(carton.weight))}</strong></div>
      <div><small>Net Weight</small><strong>${escapeHtml(kg(net))}</strong></div>
      <div><small>Tolerance</small><strong>${escapeHtml(tolerance)}</strong></div>
    </div>
    <div class="bar">${code128Svg(carton.number)}<b>${escapeHtml(carton.number)}</b></div>
  </div></div>`;
}

const css = `
    @page{size:100mm 150mm;margin:0}*{box-sizing:border-box}html,body{margin:0}body{font-family:Arial,Helvetica,sans-serif;color:#000;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .label{width:100mm;padding:0;break-after:page;page-break-after:always;break-inside:avoid;page-break-inside:avoid}.label:last-child{break-after:auto;page-break-after:auto}
    .frame{display:flex;flex-direction:column;gap:1.5mm;width:100%;padding:2mm;border:.4mm solid #000;border-radius:2.5mm}
    .frame>*{min-width:0}
    .top{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr) auto;gap:1.5mm;align-items:stretch}
    .brand{min-width:0}.brand b{display:block;font:900 8mm/1 "Arial Black",Impact,Arial,sans-serif;letter-spacing:-.3mm;white-space:nowrap}.brand b span{position:relative}.brand b sup{position:absolute;top:-.6mm;left:1.6mm;font-size:4.2mm;line-height:1}.brand small{display:block;margin-top:.8mm;font-size:2.4mm;font-weight:700;white-space:nowrap}
    .box{min-width:0;padding:.9mm 1.3mm;border:.3mm solid #000}.box small{display:block;font-size:1.8mm;font-weight:700;text-transform:uppercase;white-space:nowrap}.box strong{display:block;margin-top:.7mm;font-size:3mm;line-height:1.15;overflow-wrap:anywhere}.boxno{display:flex;flex-direction:column;justify-content:space-between;min-width:19mm}.boxno strong{margin:0;font-size:4.6mm;text-align:center;white-space:nowrap}
    .route{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);border:.3mm solid #000}.route>div{padding:1mm 1.5mm}.route>div:nth-child(even){border-left:.3mm solid #000}.route>div:nth-child(n+3){border-top:.3mm solid #000}.route>.wide{grid-column:1/-1;border-left:0!important}.route small{display:block;font-size:1.8mm;font-weight:700;text-transform:uppercase}.route strong{display:block;margin-top:.5mm;font-size:3mm;line-height:1.15;overflow-wrap:anywhere}
    .items{width:100%;border-collapse:collapse;table-layout:fixed;font-size:2.4mm}.items th{padding:.8mm .6mm;background:#000;color:#fff;font-size:2mm;text-align:left;text-transform:uppercase}.items td{padding:.7mm .7mm;border:.2mm solid #000;vertical-align:top;overflow-wrap:anywhere}.items .c{text-align:center}
    .items col.sl{width:6mm}.items col.brand{width:15mm}.items col.sku{width:19mm}.items col.qty{width:8mm}.items col.net{width:15mm}
    .frame.compact .items{font-size:2.1mm}.frame.compact .items td{padding:.4mm .6mm}.frame.dense .items{font-size:1.8mm}.frame.dense .items td{padding:.25mm .5mm}.frame.dense .items th{padding:.4mm .5mm;font-size:1.7mm}
    .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border:.3mm solid #000}.stats>div{padding:1mm .8mm;text-align:center}.stats>div+div{border-left:.3mm solid #000}.stats small{display:block;font-size:1.7mm;font-weight:800;text-transform:uppercase;white-space:nowrap}.stats strong{display:block;margin-top:.6mm;font-size:3.1mm;line-height:1.15;overflow-wrap:anywhere}
    .bar{padding:1mm 0 .5mm}.bar svg{display:block;width:100%;height:22mm}.bar b{display:block;margin-top:1.2mm;font-size:4.2mm;letter-spacing:.2mm;text-align:center;overflow-wrap:anywhere}.frame.compact .bar svg{height:17mm}.frame.compact .bar b{font-size:3.6mm}.frame.dense .bar svg{height:11mm}.frame.dense .bar b{margin-top:.6mm;font-size:3.2mm}
  `;

// Prints every label in one print request, one label per page, through a hidden frame so no pop-up is needed.
// Resolves when the print dialog closes (afterprint fires whether the user prints or cancels).
function printCartonLabels(cartons) {
  if (!cartons.length) return Promise.resolve();
  const frame = document.createElement("iframe");
  frame.setAttribute("aria-hidden", "true");
  frame.style.cssText = "position:fixed;right:0;bottom:0;width:0;height:0;border:0;";
  document.body.appendChild(frame);
  const doc = frame.contentDocument;
  doc.open();
  doc.write(`<!doctype html><html><head><meta charset="utf-8"><title>${cartons.length === 1 ? escapeHtml(cartons[0].number) : `${cartons.length} carton labels`}</title><style>${css}</style></head><body>${cartons.map(cartonLabelHtml).join("")}</body></html>`);
  doc.close();
  return new Promise(resolve => {
    const done = () => { resolve(); setTimeout(() => frame.remove(), 1000); };
    frame.contentWindow.addEventListener("afterprint", done);
    setTimeout(() => { frame.contentWindow.focus(); frame.contentWindow.print(); }, 250);
  });
}

window.CartonLabel = { html: cartonLabelHtml, css, print: printCartonLabels };
})();
