# Production server

Recorded **2026-08-08** for deploy and build troubleshooting.

## Host

| Field | Value |
|-------|-------|
| Host | `bansaledu` |
| SSH user | `assetban` |
| App path | `/home/assetban/public_html` |
| Shell prompt | `assetban@bansaledu public_html` |

## Runtime versions (verified on server)

| Tool | Version |
|------|---------|
| Node.js | **v22.14.0** |
| npm | **11.17.0** |

Compatible with Vite 8 and Tailwind 4 (`@tailwindcss/vite`).

## Deploy

- **Trigger:** push to `production` branch
- **Workflow:** `.github/workflows/production-deploy.yml`
- **Method:** `rsync` to server (excludes `.git`, `.github`, `node_modules`)
- **Note:** rsync does **not** use `--delete`, so stale files on the server can persist after they are removed from git

## Post-deploy (required)

`public/build` is gitignored and `node_modules` is not synced — build assets on the server after each deploy:

```bash
cd /home/assetban/public_html
npm ci
npm run build
php artisan config:clear   # when config/env changed
php artisan migrate        # when migrations were added
```

## Frontend build (Tailwind / Vite)

This app uses **Tailwind 4 via `@tailwindcss/vite`** (`vite.config.js`). There is **no** `postcss.config.js` in git.

If `npm run build` fails with:

```
Cannot find module '@tailwindcss/postcss'
Require stack: .../postcss.config.js
```

the server has a **stale** `postcss.config.js` left from an old setup or manual edit. Remove it:

```bash
rm -f /home/assetban/public_html/postcss.config.js
npm run build
```

Do **not** install `@tailwindcss/postcss`, `postcss`, or `autoprefixer` for this project.
