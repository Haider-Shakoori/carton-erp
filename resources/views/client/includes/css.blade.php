<link rel="stylesheet" href="{{ asset('vendor/fontawesome/6.5.0/css/all.min.css') }}">
<link href="{{ asset('vendor/bootstrap/css/bootstrap-5.3.2.min.css') }}" rel="stylesheet">
<script src="{{ asset('vendor/chartjs/chart-4.4.1.min.js') }}"></script>
<link href="{{ asset('vendor/tom-select/tom-select.css') }}" rel="stylesheet">
<script src="{{ asset('vendor/tom-select/tom-select.complete.min.js') }}"></script>

<style>
    :root{
  --bg:#f8fafc;--fg:#0f1830;--muted:#6b7386;--line:#e5e7eb;
  --card:#fff;--brand:#0e7afe;--good:#0fbf61;--bad:#ef3e4a;--warn:#f59e0b;
  --radius:16px;--shadow:0 4px 16px rgba(0,0,0,.08);
}

html,body{
  background:var(--bg);
  color:var(--fg);
  font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
  margin:0;padding:0;
  overflow-x:hidden;
}

.container-tight{
  max-width:520px;
  margin:0 auto;
  overflow-x:hidden;
  overflow-y:visible;
  position:relative;
  z-index:auto;
}

/* 🟢 HEADER FIX — make topbar sticky like nav */
.hdr{
  position:fixed;
  top:0;
  left:0;
  right:0;
  background:var(--card);
  border-bottom:1px solid var(--line);
  z-index:99999 !important;
  box-shadow:0 4px 10px rgba(0,0,0,0.05);
}

.hdr-row{
  padding:10px 14px;
  display:flex;
  align-items:center;
  justify-content:space-between;
}

.avatar{
  width:36px;height:36px;
  border-radius:50%;
  display:grid;place-items:center;
  background:var(--brand);color:#fff;
  font-weight:700;cursor:pointer;
}

/* 🟢 DROPDOWN FIX — always above all cards */
.dropdown-card,
.dropdown-menu{
  position:fixed !important;
  top:60px !important;
  right:calc(50% - 250px / 2);
  background:#fff;
  border:1px solid var(--line);
  border-radius:12px;
  box-shadow:0 10px 30px rgba(0,0,0,0.25);
  z-index:999999 !important;
  width:220px;
  display:none;
}
.dropdown-menu.show{display:block;}

.dropdown-card .item{
  display:flex;align-items:center;gap:10px;
  padding:10px 12px;
  color:var(--fg);text-decoration:none;
}
.dropdown-card .item:hover{background:#f6f8ff;}
.dropdown-divider{height:1px;background:var(--line);margin:4px 0;}

/* 🟢 BODY & CARD STYLES */
.main{padding:70px 10px 90px !important;}
.section-title{font-weight:700;font-size:15px;margin:14px 4px 10px;}
.wallets{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;}
.wallet{border-radius:16px;padding:14px;background:var(--card);border:1px solid var(--line);box-shadow:var(--shadow);}
.wallet .amt{font-weight:800;font-size:18px;margin-top:4px;}
.wallet.positive .amt{color:var(--good)}
.wallet.negative .amt{color:var(--bad)}
.wallet .meta{color:#8a93a7;font-size:11px;}

.cardx{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  padding:18px 16px;
  position:relative;
  z-index:1;
  overflow:visible;
}

.list{display:flex;flex-direction:column;gap:10px;}
.rowcard{
  display:flex;gap:12px;padding:12px;
  border-radius:14px;background:#fff;
  border:1px solid var(--line);
  box-shadow:var(--shadow);
  position:relative;
  z-index:1;
}
.rowcard .ic{
  width:36px;height:36px;border-radius:12px;
  display:grid;place-items:center;
  background:#eef4ff;color:var(--brand);
}
.rowcard .ic.green{background:#ebfff5;color:var(--good);}
.rowcard .ic.yellow{background:#fff7e9;color:var(--warn);}
.rowcard .ic.red{background:#ffeef0;color:var(--bad);}
.rowcard .title{font-weight:700;font-size:14px;}
.rowcard .sub{font-size:12px;color:#8a93a7;}
.rowcard .amt{margin-left:auto;font-weight:800;}
.amt.pos{color:var(--good)}
.amt.neg{color:var(--bad)}

/* ========== FIXED FILTERS SECTION ========== */
.filters {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
  margin-top: 8px;
  position: relative;
  z-index: 5;
}

.filters .wide {
  grid-column: 1;
}

.filters label {
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
  margin-bottom: 4px;
  display: block;
}

/* Select wrapper for full clickable area */
.select-wrapper {
  position: relative;
  width: 100%;
  cursor: pointer;
}

.select-wrapper::after {
  content: "▼";
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 12px;
  color: #6b7386;
  pointer-events: none;
}

/* Fixed select styling */
.filters select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  width: 100%;
  height: 44px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #f9fafb;
  padding: 10px 36px 10px 12px;
  font-size: 14px;
  color: #0f1830;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);
  transition: all 0.2s ease;
  cursor: pointer;
}

/* Fixed date input - remove calendar icon */
.filters input[type="date"] {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  width: 100%;
  height: 44px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #f9fafb;
  padding: 10px 12px;
  font-size: 14px;
  color: #0f1830;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.03);
  transition: all 0.2s ease;
  cursor: pointer;
}

/* Remove calendar icon for Webkit browsers */
.filters input[type="date"]::-webkit-calendar-picker-indicator {
  display: none;
}

/* Remove calendar icon for Firefox */
.filters input[type="date"]::-moz-calendar-picker-indicator {
  display: none;
}

/* Focus states */
.filters select:focus,
.filters input[type="date"]:focus {
  background: #fff;
  border-color: #0e7afe;
  box-shadow: 0 0 0 3px rgba(14,122,254,0.1);
  outline: none;
}

/* Apply button */
.filters .btn-ghost {
  background: linear-gradient(135deg, #0e7afe, #2563eb);
  border: none;
  border-radius: 12px;
  color: #fff;
  font-weight: 600;
  height: 44px;
  letter-spacing: 0.2px;
  transition: all 0.2s ease;
  margin-top: 4px;
  box-shadow: 0 3px 10px rgba(14,122,254,0.3);
  cursor: pointer;
  width: 100%;
}

.filters .btn-ghost:hover {
  opacity: 0.95;
  transform: translateY(-1px);
}
.chip{
  display:inline-flex;align-items:center;gap:8px;
  background:#f1f5ff;color:var(--fg);
  border:1px solid #e2e8ff;
  padding:8px 12px;border-radius:999px;
  font-size:12px;box-shadow:var(--shadow);
  white-space:nowrap;
}

.tabpane{display:none;min-height:60vh;}
.tabpane.active{display:block;animation:fade .18s ease-out;}
@keyframes fade{from{opacity:.3;transform:translateY(6px);}to{opacity:1;transform:none;}}

.ptr{position:relative;height:0;overflow:visible;}
.ptr-indicator{
  position:absolute;top:-30px;left:50%;
  transform:translateX(-50%);
  background:#fff;color:var(--fg);
  border:1px solid var(--line);
  border-radius:999px;
  padding:4px 10px;font-size:12px;
  box-shadow:var(--shadow);
  opacity:0;transition:opacity .15s;
}
.ptr-indicator.show{opacity:1;}

.hero{
  background:linear-gradient(135deg,#eff6ff,#ffffff);
  border:1px solid var(--line);
  border-radius:18px;box-shadow:var(--shadow);
  padding:16px;display:flex;gap:12px;align-items:center;
}

.currency-card{
  width:100%;
  background:#fff;border:1px solid var(--line);
  border-radius:16px;box-shadow:var(--shadow);
  padding:12px 16px;margin-bottom:10px;
}
.currency-card .head{display:flex;justify-content:space-between;align-items:center;}
.currency-card .amount{font-weight:800;font-size:18px;margin-top:4px;}

.summary-row{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;}
.summary-chip{
  flex:1;background:#f8fafc;border-radius:12px;
  padding:8px 10px;text-align:center;
  font-size:13px;border:1px solid var(--line);
}
.summary-chip strong{display:block;font-weight:800;color:var(--fg);}

.modal-dialog{max-width:360px;margin:auto;}
.modal-body p{margin-bottom:4px;font-size:14px;}

#hdrDropdown.show { display: block !important; }
.toggle-pass {
  position: absolute;
  right: 12px;
  top: 37px;
  color: #8a93a7;
  cursor: pointer;
  font-size: 16px;
}

.toggle-pass:hover {
  color: var(--brand);
}

.summary-chip.credit strong {
  color: var(--good);
}

.summary-chip.debit strong {
  color: var(--bad);
}

.currency-card {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 16px;
  box-shadow: var(--shadow);
  padding: 14px 16px;
  margin-bottom: 12px;
  transition: all 0.2s ease-in-out;
}

.currency-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

.currency-card .amount {
  font-size: 20px;
  font-weight: 800;
  margin-top: 6px;
}

.currency-card .head .code {
  font-size: 15px;
}

/* ============ Exchange Summary Gradient Cards ============ */
.gradient-card {
  width: 100%;
  color: #fff;
  border-radius: 16px;
  padding: 18px 20px;
  margin-bottom: 12px;
  background: linear-gradient(135deg, #0e7afe, #00c6ff);
  box-shadow: 0 4px 14px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  transition: all 0.3s ease-in-out;
}

.gradient-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.25);
}

.gradient-card .g-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
  opacity: 0.9;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.gradient-card .g-title {
  font-weight: 600;
}

.gradient-card .g-code {
  font-weight: 700;
}

.gradient-card .g-bottom {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.gradient-card .g-symbol {
  font-size: 18px;
  opacity: 0.9;
}

.gradient-card .g-value {
  font-size: 24px;
  font-weight: 800;
  text-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

#xSumBase, #xSumTarget {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
}

/* ---- Remittance Summary Cards ---- */
#rSumRow {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 12px;
}

.remit-summary-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 18px;
  border-radius: 14px;
  color: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transition: all 0.3s ease;
  width: 100%;
}

.remit-summary-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0,0,0,0.25);
}

.rs-left {
  display: flex;
  flex-direction: column;
}

.rs-code {
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.rs-symbol {
  font-size: 12px;
  opacity: 0.85;
}

.rs-right {
  font-size: 22px;
  font-weight: 800;
  text-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

/* ---- Pending & Approved Status Tiles ---- */
.status-tile {
  border-radius: 14px;
  padding: 16px;
  color: #fff;
  text-align: center;
  font-weight: 600;
  box-shadow: 0 3px 10px rgba(0,0,0,0.15);
  transition: all 0.3s ease;
}

.status-tile:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.25);
}

.status-tile.pending {
  background: linear-gradient(135deg, #f97316, #fb923c);
}

.status-tile.approved {
  background: linear-gradient(135deg, #10b981, #34d399);
}

.s-label {
  font-size: 14px;
  opacity: 0.9;
  margin-bottom: 4px;
}

.s-value {
  font-size: 22px;
  font-weight: 800;
}

/* ===== Wallet Grid ===== */
#walletGrid.wallets {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(165px, 1fr));
  gap: 14px;
}

/* ===== Wallet Tile ===== */
.wallet-tile {
  position: relative;
  border-radius: 16px;
  padding: 14px 16px;
  color: #fff;
  background: linear-gradient(135deg, #0e7afe, #2563eb);
  box-shadow: 0 8px 16px rgba(0,0,0,0.15);
  backdrop-filter: blur(6px);
  transition: all 0.3s ease;
  overflow: hidden;
}

.wallet-tile:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 22px rgba(0,0,0,0.25);
}

/* ===== Variants ===== */
.wallet-tile.positive {
  background: linear-gradient(135deg, #0fbf61, #34d399);
}
.wallet-tile.negative {
  background: linear-gradient(135deg, #ef4444, #f87171);
}

/* ===== Inner Content ===== */
.wt-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  opacity: 0.9;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.wt-amount {
  font-size: 22px;
  font-weight: 800;
  margin: 6px 0 4px;
  text-shadow: 0 2px 6px rgba(0,0,0,0.3);
}

.wt-meta {
  font-size: 13px;
  opacity: 0.95;
  display: flex;
  align-items: center;
  gap: 6px;
}

.wt-meta .divider {
  color: rgba(255,255,255,0.4);
}

.cr-label {
  color: #caffbf;
  font-weight: 700;
}

.dr-label {
  color: #ffd6d6;
  font-weight: 700;
}

/* Smooth layout on small screens */
@media (max-width: 480px) {
  .wallet-tile {
    padding: 12px;
  }
  .wt-amount {
    font-size: 20px;
  }
}

#remitModal .modal-content {
  border-radius: 12px;
  overflow: hidden;
}
#remitModal table th {
  color: #444;
  font-weight: 600;
  width: 40%;
}
#remitModal table td {
  color: #222;
}

/* ===== Premium Sticky Tabbar ===== */
.tabbar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 64px;
  display: flex;
  justify-content: space-around;
  align-items: center;
  background: linear-gradient(135deg, #0e1b2c 0%, #1c3b61 100%);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  z-index: 999;
  padding: 0 10px;
}

/* Each Tab Item */
.tabbar a {
  flex: 1;
  text-align: center;
  text-decoration: none;
  color: #a0aec0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 500;
  height: 100%;
  transition: all 0.25s ease;
  border-radius: 10px;
  position: relative;
}

/* Icon */
.tabbar a i {
  font-size: 18px;
  transition: transform 0.25s ease, color 0.25s ease;
}

/* Hover */
.tabbar a:hover {
  color: #fff;
  background: rgba(255,255,255,0.05);
}

/* Active Tab */
.tabbar a.active {
  color: #fff;
  background: linear-gradient(135deg, #0e7afe, #2563eb);
  box-shadow: inset 0 0 10px rgba(255,255,255,0.15);
}

/* Active Icon Animation */
.tabbar a.active i {
  transform: scale(1.15);
  color: #fff;
}

/* Underline indicator */
.tabbar a.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 25%;
  width: 50%;
  height: 3px;
  border-radius: 2px;
  background: #fff;
  box-shadow: 0 0 8px rgba(255,255,255,0.8);
}

/* Labels */
.tabbar a span {
  font-size: 11px;
  opacity: 0.9;
}

/* Mobile Feel */
@media (max-width: 480px) {
  .tabbar {
    height: 60px;
    padding-bottom: 6px;
  }
  .tabbar a i {
    font-size: 17px;
  }
  .tabbar a span {
    font-size: 10px;
  }
}

/* Optional: Dark Theme Friendly */
body.dark .tabbar {
  background: linear-gradient(135deg, #0b1120, #1f2937);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

#filtersPanel {
  border: 1px solid var(--line);
  border-radius: var(--radius);
  background: var(--card);
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  transition: all .25s ease;
}

#toggleFilters {
  border-radius: 8px;
  font-weight: 500;
}

#jDownload {
  font-weight: 600;
  border-radius: 10px;
  padding: 10px 0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}

#filtersPanel, #exchangeFiltersPanel {
  border: 1px solid var(--line);
  border-radius: var(--radius);
  background: var(--card);
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  transition: all .25s ease;
}

#toggleFilters, #toggleExchangeFilters {
  border-radius: 8px;
  font-weight: 500;
}

#remitFiltersPanel {
  border: 1px solid var(--line);
  border-radius: var(--radius);
  background: var(--card);
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  transition: all .25s ease;
}

#toggleRemitFilters {
  border-radius: 8px;
  font-weight: 500;
}

#exchangeRates .rate-row:last-child {
  border-bottom: none;
}

#exchangeRates .rate-row {
  font-size: 14px;
}

#exchangeRates .fw-semibold {
  color: var(--fg);
}

</style>
