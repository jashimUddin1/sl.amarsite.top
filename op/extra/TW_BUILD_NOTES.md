# Tailwind Local Build

This project previously loaded Tailwind via the CDN (`cdn.tailwindcss.com`), which compiles styles at runtime and slows down page load.

## One-time setup

1. Install Node.js (LTS).
2. From the project root:

```bash
npm install
```

## Build CSS (production)

```bash
npm run build
```

Outputs:
- `assets/css/tailwind.min.css`

## Watch mode (development)

```bash
npm run dev
```

## Notes
- Tailwind scans all PHP/HTML/JS files based on `tailwind.config.js`.
- If you add new folders/files with Tailwind classes, make sure they match the `content` globs.
