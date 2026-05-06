/* ============================================================
   shared.js — Utilities shared across all pages
   ============================================================ */
const API_URL = 'api.php';

// ── EMPLOYEE CACHE ───────────────────────────────────────────
let empCache     = null;
let empCacheTime = 0;
const EMP_CACHE_TTL = 5 * 60 * 1000;
const EMP_AMOUNTS   = { Regular: 120, Housing: 200 };

async function warmEmpCache() {
  try {
    const res  = await fetch(`${API_URL}?action=employees`);
    const data = await res.json();
    empCache     = data.employees || [];
    empCacheTime = Date.now();
  } catch {}
}
async function getEmployees() {
  if (empCache && (Date.now() - empCacheTime) < EMP_CACHE_TTL) return empCache;
  await warmEmpCache();
  return empCache || [];
}
function invalidateEmpCache() { empCache = null; empCacheTime = 0; }

// ── UTILITIES ────────────────────────────────────────────────
function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function today() { return new Date().toISOString().slice(0,10); }
function nowTime() {
  return new Date().toLocaleTimeString('en-PH', { hour:'2-digit', minute:'2-digit', second:'2-digit', hour12: false });
}
function playBeep() {
  try {
    const ctx  = new (window.AudioContext || window.webkitAudioContext)();
    const osc  = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain); gain.connect(ctx.destination);
    osc.frequency.value = 1040; gain.gain.value = 0.07;
    osc.start(); setTimeout(() => osc.stop(), 110);
  } catch {}
}
function showToast(msg, type = 'success') {
  const t = document.createElement('div');
  t.className   = `toast ${type}`;
  t.textContent = msg;
  document.getElementById('toast-container').appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 2800);
}

// ── SHARED HEADER BUILDER ────────────────────────────────────
function buildHeader(badgeText) {
  const badge = document.getElementById('header-meal-badge');
  if (badge && badgeText) { badge.textContent = badgeText; badge.style.display = 'block'; }
}
