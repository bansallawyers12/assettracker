---
paths:
  - 'resources/views/vendors/**'
  - 'resources/js/vendors-workspace.js'
  - 'app/Http/Controllers/VendorController.php'
  - 'app/Http/Controllers/VendorsWorkspaceController.php'
---

# Vendors

## Vendors index is a workspace SPA
Create/edit/delete (and bulk link actions) use the shared entity workspace panel + JSON list refresh — same pattern as email templates. Do not restore full-page create/edit as the primary UX; keep create/edit routes as redirects into `vendors.index` with `panel` query params. JSON mutations return `list_html` and `unlinked_html`.
