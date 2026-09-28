# HTTP polling & Australia performance — assettracker

**Purpose:** Inventory where Asset Tracker uses HTTP polling (and related timers), why staff in **Australia** can perceive slowness while **India** feels fine, and a **safe** improvement order.

**Status:** Review / planning only — no changes applied from this document alone.  
**Related:** Same hosting family as other Bansal properties (`docs/PRODUCTION.md` — host `bansaledu`, app path `/home/assetban/public_html`). Patterns from `bansalcrm2` `docs/HTTP_POLLING_AND_AU_PERFORMANCE.md` are **not** present here by default.  
**Last reviewed:** 2026-09-28

---

## 1. Executive summary

| Finding | Detail |
|--------|--------|
| Main AU slowness driver | **Geographic latency** to origin × **on-demand AJAX** on heavy workspace pages (not background timers) |
| Browser HTTP polling | **None found** — no `setInterval` + fetch/ajax anywhere in `resources/js` or Blade |
| Gmail / email inbox | **Manual sync only** (`GET /emails-sync` on button click); not scheduled |
| Real-time (Echo/Reverb) | **Off** — `config/broadcasting.php` defaults to `null` |
| vs bansalcrm2 | Asset Tracker is **much lighter** on background HTTP — no global 4 s office-visit poll, no Elite inbox auto-poll, no My Day heartbeat |

**One-line diagnosis for AU staff:**

> Same server region as India, but each workspace click, form save, document preview, or report load pays ~200–350 ms round-trip from Australia vs ~30–80 ms from India — **without** the extra cost of background polling on every page.

---

## 2. HTTP polling inventory (browser → server)

### 2.1 Global layout — every authenticated page

**Status:** ✅ **No background HTTP polling** (verified 2026-09-28)

| Check | Result |
|-------|--------|
| `setInterval` in `resources/js/**` | **0 matches** |
| `setInterval` in `resources/views/**` | **0 matches** |
| `wire:poll` / Livewire timers | **Not used** |
| Layout | `resources/views/layouts/app.blade.php` — Vite bundle `resources/js/app.js` only |

**Implication:** Opening dashboard, entity show, emails list, etc. does **not** start a hidden timer hitting the server. AU load is driven by **what the user does**, not idle background polls.

---

### 2.2 Workspace AJAX — on-demand (main interactive load)

SPA-style **slide-over panels** and tab workspaces fetch HTML/JSON **only when the user opens a panel, submits a form, or refreshes a list**.

| Area | Typical endpoints | Trigger | File(s) |
|------|-------------------|---------|---------|
| Business entity show | `/business-entities/{id}/…/workspace`, form fragments | Tab / panel open, form submit | `resources/js/entity-show-workspace.js`, `workspace-panel.js` |
| Documents | `…/documents/workspace`, upload, preview stream | Tab open, file click, upload | `resources/js/documents-workspace.js` |
| Compliance / FY | `…/compliance/workspace`, PATCH notes, file status | Tab open, edit, upload | `resources/js/compliance-workspace.js` |
| Bank accounts | Bank panel, import, reconciliation | Panel open, import actions | `resources/js/bank-account-modal.js`, `bank-reconciliation.js` |
| Persons / assets / contacts | `…/workspace`, `…/form/create` | Panel open | `persons-workspace.js`, `asset-show-workspace.js`, etc. |
| Admin users | `AdminUsersWorkspaceController` list HTML | Create / password reset | `resources/js/admin-users-workspace.js` |
| Email templates | `email-templates/workspace` | Panel open | `resources/js/email-templates-workspace.js` |

**Shared helper:** `resources/js/workspace-panel.js` — `apiFetch()`, `submitWorkspaceForm()`.

**AU impact:** Medium–high **per interaction** on entity show (many tabs), not a constant background drain. Optimise endpoint response time and payload size rather than “turn off polling”.

---

### 2.3 Emails & Gmail — manual only (no auto-poll)

| What | Endpoint | Interval | File |
|------|----------|----------|------|
| Gmail sync | `GET /emails-sync` | **On button click only** | `resources/views/emails/index.blade.php` → `GmailController::sync` |
| Inbox list | `GET /emails` | Full page + filter form | `MailMessageController::index` |
| Upload `.msg` | `POST /emails/upload` | On upload | same |

**Backend:** `GmailController::sync` fetches up to **10** newest messages for the current user (synchronous redirect, not queued in UI).

**Scheduled:** `routes/console.php` explicitly documents Gmail as **manual only** — `gmail:sync` exists (`app/Console/Commands/GmailSyncCommand.php`) but is **not** on the scheduler.

**AU impact:** Low unless staff click **Sync Gmail** often; each click is one round-trip + Gmail API work.

---

### 2.4 Debounced autosave — not polling

| What | Endpoint | Behaviour | File |
|------|----------|-----------|------|
| Compliance year notes | `PATCH …/compliance-years/{record}` | **800 ms debounce** after typing stops | `resources/js/compliance-workspace.js` |

Triggered only while the compliance workspace is open and the user edits notes. Not a timer on every page.

---

### 2.5 Server-side scheduled jobs (not browser polling)

Defined in `routes/console.php` (Laravel 11+ scheduler):

| Command | Schedule | Notes |
|---------|----------|--------|
| `compliance:sync-reminders` | Daily **06:30** | Creates 30/14/7-day compliance reminders |
| `depreciation:post-monthly` | **1st of month** 02:00 | Monthly depreciation posting |

**Not scheduled in repo:** `gmail:sync`, encrypted backups (`backup:schedule` registers jobs when run manually — see `app/Console/Commands/ScheduleBackup.php`).

Adds server load for all users; not geography-specific.

---

### 2.6 Timers that are NOT server polling

| File | Purpose |
|------|---------|
| `resources/js/notify.js` | Toast auto-dismiss (~6 s) — UI only |
| `resources/js/documents-workspace.js` | Preview loader max wait **15 s** — UI fallback |
| `resources/js/compliance-workspace.js` | Preview loader max wait **15 s** — UI fallback |
| `resources/js/compliance-workspace.js` | Notes save debounce **800 ms** — see §2.4 |
| `resources/js/admin-users-workspace.js` | Redirect delay **400 ms** after 423 — navigation |
| `resources/js/flatpickr-init.js`, `tomselect-init.js` | Widget init — no background server calls |
| `resources/js/form-saving-ui.js` | Global “Saving…” overlay on form submit — on demand |

---

## 3. Comparison with bansalcrm2

| Area | bansalcrm2 | assettracker |
|------|------------|--------------|
| Global office-visit poll | Was **4 s** on every admin page (now gated off by default) | **Not present** |
| Elite inbox auto-poll | **25 s** when `/emails/elite` open | **Not present** (different email stack) |
| My Day file-time heartbeat | **60 s** on client/partner detail | **Not present** |
| Notification bell poll | Disabled | **Not present** |
| Workspace / tab AJAX | Heavy client detail | Entity show, documents, compliance, bank |
| Gmail sync | N/A (SES/Elite) | **Manual** button only |
| Echo / Reverb | Off | Off (`null` driver) |

**Implication:** Asset Tracker should feel **less “busy” in the Network tab at idle** than bansalcrm2. Remaining AU slowness is mostly **latency × user actions** and **heavy report/workspace responses**.

---

## 4. Why India is fine and Australia feels slow

### 4.1 Latency model

```
Staff browser  →  CDN (if any)  →  Origin (PHP-FPM + PostgreSQL)
```

| Region | Typical round-trip to India-hosted origin |
|--------|-------------------------------------------|
| India | ~30–80 ms |
| Australia | ~200–350+ ms |

Each workspace `apiFetch`, form POST, document preview stream, or financial report page load is a full round-trip. Example: 10 tab/panel actions in one minute ≈ **2.5 s** wait (AU) vs **0.5 s** (IN) — without any background polling.

### 4.2 What it is not

- Not hidden `setInterval` polling (none in codebase)
- Not automatic Gmail inbox refresh
- Not a separate “Australia bug” in routes

### 4.3 Likely AU pain points (on-demand, not timers)

| Area | Why it hurts AU |
|------|-----------------|
| Business entity **show** page | Many workspace tabs; each open may fetch JSON/HTML |
| **Documents / compliance** preview | iframe/image load to `…/content` or `…/compliance-files/…/content` |
| **Bank import / reconciliation** | Multi-step AJAX (`bank-import`, `save-matches`) |
| **Financial reports** | Full server-rendered report pages |
| **Sync Gmail** | Blocking redirect; external Gmail API + DB write |

---

## 5. Safe improvement plan (do not skip steps)

### Phase A — Measure (no behaviour change)

- [ ] Confirm origin server region (hosting panel / `curl` timing from IN vs AU)
- [ ] Browser DevTools → Network on: dashboard, entity show (open 3 tabs), emails list, one financial report
- [ ] Confirm idle page: **no repeating XHR** after load (expected for assettracker)
- [ ] Log slow queries on heaviest workspace endpoints (documents/compliance workspace JSON)

### Phase B — Optimise on-demand paths (low risk)

| Action | Why safe |
|--------|----------|
| Cache or slim workspace JSON payloads | Fewer bytes per panel open |
| Eager-load relationships on workspace controllers | Cuts N+1 on tab open |
| Keep document preview **on click** (do not add auto-refresh poll) | Preserves current UX |
| Index/filter tuning on `mail_messages`, compliance tables | Faster email list & compliance |

### Phase C — Infrastructure (largest win for all regions)

- [ ] Origin or DB read replica closer to AU users
- [ ] PHP-FPM workers / memory for peak (entity show + reports)
- [ ] Ensure `npm run build` on deploy (`docs/PRODUCTION.md`) — stale assets ≠ polling but affects perceived speed

### Phase D — What not to add without review

| Addition | Risk |
|----------|------|
| Auto Gmail poll (e.g. every 30 s on `/emails`) | Would recreate bansalcrm2 §2.3-style AU load |
| Global notification poll on `app.blade.php` | Unnecessary; app has no bell poll today |
| Echo/Reverb without need | Complexity; only add if real-time feature required |

---

## 6. AU regression test checklist (before/after any change)

Run from an Australian network (or VPN) and from India.

| # | Scenario | Pass criteria |
|---|----------|---------------|
| 1 | Dashboard load | No repeating background XHR after page settle |
| 2 | Entity show — open Documents, Compliance, Bank tabs | Panels load; no JS errors |
| 3 | Compliance — edit notes, wait 1 s | Single PATCH after debounce; “Saved” |
| 4 | Document preview — open PDF | Preview or clear error; no endless spinner |
| 5 | Emails — **Sync Gmail** once | Redirect + status message; inbox updates |
| 6 | Financial report (P&amp;L or balance sheet) | Report renders within acceptable time |
| 7 | Bank import — upload statement | Process + match UI works |

---

## 7. File reference index

| Topic | Path |
|-------|------|
| Global layout (no polling) | `resources/views/layouts/app.blade.php` |
| Vite entry / workspace imports | `resources/js/app.js` |
| Shared `apiFetch` / panel | `resources/js/workspace-panel.js` |
| Entity show workspaces | `resources/js/entity-show-workspace.js` |
| Documents workspace | `resources/js/documents-workspace.js` |
| Compliance workspace + notes debounce | `resources/js/compliance-workspace.js` |
| Bank / reconciliation | `resources/js/bank-account-modal.js`, `bank-reconciliation.js` |
| Emails UI + Sync Gmail button | `resources/views/emails/index.blade.php` |
| Gmail manual sync | `app/Http/Controllers/Email/GmailController.php` |
| Mail inbox | `app/Http/Controllers/Email/MailMessageController.php` |
| Scheduler | `routes/console.php` |
| Broadcast config (off) | `config/broadcasting.php` |
| Gmail config | `config/gmail.php` |
| Production deploy notes | `docs/PRODUCTION.md` |

---

## 8. Priority summary

| Priority | Item | Effort | AU impact |
|----------|------|--------|-----------|
| 1 | Origin closer to AU / edge for static | Infra | Highest — every request |
| 2 | Workspace endpoint + query optimisation | Medium | High on entity show |
| 3 | Financial report / heavy query tuning | Medium | Medium when reports in use |
| 4 | PHP-FPM + PostgreSQL tuning | Infra | Medium (stability + speed) |
| 5 | **Do not** add background email/workspace polling | — | Avoid bansalcrm2-style regression |

---

*This document is for planning and handoff. Implement changes in small PRs with the checklist in §6.*
