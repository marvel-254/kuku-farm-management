# Frontend Audit — Bugs Found

Audit target: KUKU Farm Management System (web variant, `public/`).

Test method: terminal browser against the local mock API server (Node/Express
with canned JSON). Login: `admin` / `admin123`.

---

## BUG-001 — `logout()` declared `async` but uses `await` (CRITICAL — FIXED)

**File:** `public/index.html:386`
**Symptom:** Login appeared to succeed but the dashboard never rendered. The
entire inline `<script>` block was silently dead.
**Root cause:** `function logout()` was written using `await fetch(...)`, but
`await` is only valid inside an `async` function. This `SyntaxError` aborted
parsing of the whole script, so `login`, `showApp`, and every other function
were `undefined`. Form submission failed with nothing in the console.
**Fix:** `public/index.html` — changed to `async function logout()`.

---

## BUG-002 — No logout button in the web UI (HIGH)

**File:** `public/index.html` (sidebar)
**Symptom:** Users can log in but cannot log out from the web interface. There
is no logout affordance in the sidebar or header.
**Root cause:** The sidebar nav (`<aside class="sidebar">`) contains links for
Dashboard, Chickens, Inventory, Sales, Expenses, Invoices, Debts, Financial, and
Reports — but no logout entry. `logout()` (fixed above) is defined but never
attached to any element. (The desktop variant `desktop/renderer/index.html:138`
has a `#logout-btn`, so this is a web-only regression.)
**Fix:** Add a logout item to the sidebar that calls `logout()`.

---

## BUG-003 — Dashboard Receivables / Payables always `$0.00` (HIGH)

**File:** `public/index.html:524` (`loadDashboardData`)
**Symptom:** On the dashboard the sidebar correctly shows "Invoices …
$3200.00 Outstanding" and "Debts … $1200.00 Outstanding", but the
"Receivables (Money Owed to You)" and "Payables (Money You Owe)" cards both read
`$0.00`.
**Root cause:** `loadDashboardData()` computes local `const receivables` and
`const payables` (used only for the net-position math) but never writes them to
the `#receivables` and `#payables` elements defined in `public/dashboard.html`.
**Fix:** Add the missing `getElementById(...).textContent` updates.

---

## BUG-004 — Reports page never loads its data (HIGH)

**File:** `public/index.html` `loadPageData()` switch (around `:490`)
**Symptom:** The Reports & Analytics page shows all zeros:
$0.00 revenue, 0 sales, 0 chickens sold, $0.00 expenses, etc.
**Root cause:** `loadPageData()` has no `case 'reports'` branch, so
`loadReports()` is never called. The standalone `<script>` inside
`public/reports.html` cannot save it either, because the page is injected via
`innerHTML` (line 435), which does **not** execute `<script>` tags. The chart's
data array is also hardcoded to zeros.
**Fix:** Add a `reports` case that calls `loadReports()` and wires up the chart
with real data.

---

## BUG-005 — Financial page metrics incomplete / partially dead (MEDIUM)

**File:** `public/index.html:939` (`updateFinancialOverview`)
**Symptom:** On Financial Overview some KPIs are correct (revenue, expenses,
net profit) but `invoice-count`, `invoice-value`, `debt-count`, `debt-value`,
and `cash-flow` stay at their `$0.00` / `0` placeholders.
**Root cause:** `updateFinancialOverview()` updates element ids
(`total-invoices`, `total-debts`, `outstanding-invoices`, `outstanding-debts`)
that **do not exist** on `public/financial.html`. The fragment's actual ids are
`invoice-count`, `invoice-value`, `debt-count`, `debt-value`, and `cash-flow`.
Additionally, the inline `<script>` at the bottom of `financial.html` is dead
code under `innerHTML` injection, and the "Revenue Chart" doughnut uses
hardcoded dummy data.
**Fix:** Align `updateFinancialOverview()` with the fragment's ids and wire the
chart to the fetched stats.

---

## BUG-006 — Inline `<script>` blocks in page fragments are dead code (MEDIUM)

**File:** `public/financial.html`, `public/reports.html`
**Symptom:** Each fragment ships a self-contained `<script>` (e.g.
`loadFinancialData()`, `loadReports()`) that is meant to run on page load.
**Root cause:** `navigateToPage()` injects fragment HTML via
`container.innerHTML = html` (index.html:435). Per the HTML spec, scripts inserted
via `innerHTML` **do not execute**. The only reason these pages show any data at
all is that `loadPageData()` re-fetches the same endpoints from `index.html`.
The fragment scripts are therefore dead weight (and misleading — they use
hardcoded `/api/...` paths that ignore `API_BASE`, and the charts render dummy
data).
**Fix:** Either inline the loader logic in `index.html` (preferred) or replace
`innerHTML` injection with `<template>` / `DOMParser` + script extraction.

---

## BUG-007 — Hardcoded `/api/...` paths ignore the configured `API_BASE` (MEDIUM)

**File:** `public/financial.html:103`, `public/reports.html:53`
**Symptom:** In a non-root deployment (e.g. behind a path prefix or on Vercel
with a separate API host) these fragments would 404.
**Root cause:** The fragment scripts use `fetch('/api/sales/stats')` instead of
`fetch(\`${API_BASE}/api/sales/stats\`)`. (Currently harmless because the
scripts don't run — see BUG-006 — but will break once they're activated.)
**Fix:** Use `API_BASE`.

---

## BUG-008 — Electron download artifact committed to repo (LOW)

**File:** `electron-v25.9.8-linux-x64.zip` (28 MB)
**Symptom:** A 28 MB binary blob sits in the repo root.
**Impact:** Blows up clone size and CI cache for no reason when the dependency is
already installed in `node_modules`.
**Fix / recommendation:** Delete the file; download Electron via `npx
electron` / the npm package at runtime. Add `/electron-*-linux-x64.zip` to
`.gitignore`.

---

## BUG-009 — Desktop vs. web page set is out of sync (LOW)

**File:** `desktop/renderer/js/app.js`, `desktop/renderer/index.html`
**Symptom:** The desktop app maintains its own copy of the page set and inline
renderers. Changes made to `public/index.html` (e.g. BUG-001, BUG-002) are not
mirrored in `desktop/renderer/`, so the two variants can drift.
**Fix / recommendation:** Consolidate page rendering into a shared module
imported by both entry points, or clearly document the divergence and keep the
sidebar in sync manually.

---

## BUG-010 — Newsprint design system present but unused (LOW)

**File:** `public/assets/css/newsprint.css`
**Symptom:** A complete design system (CSS custom properties for colors,
typography, spacing, radius/motion tokens, `.newsprint-grid`, `.section-rule`,
`.label`, etc.) ships in `public/assets/css/` but is never imported by any HTML
page. The app still uses the bespoke `professional.css` look.
**Fix / recommendation:** Migrate `index.html` (and the page fragments) to the
Newsprint token set, or remove the file to avoid dead code.
