# VitePress scaffold: read first

The TypeScript files here end in `.txt` (`config.mts.txt`, `sidebars.ts.txt`, `legacy-hash-map.ts.txt`, `theme/index.ts.txt`). That stops the design-system compiler from trying to bundle VitePress and Vue into `_ds_bundle.js`.

**Before the first build, strip the `.txt` suffix:**

```bash
cd docs-site/.vitepress
for f in $(find . -name '*.ts.txt' -o -name '*.mts.txt'); do mv "$f" "${f%.txt}"; done
```

`.vue`, `.css` and `.md` files are ready to use as they are.
