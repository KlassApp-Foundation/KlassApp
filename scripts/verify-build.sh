#!/bin/bash
set -e

echo "=== Frontend Build Verification ==="

# 1. Check Tailwind directives were processed
echo -n "[1/3] Tailwind directives resolved... "
TAILWIND_COUNT=$(grep -c '@tailwind' public/css/app.css 2>/dev/null || true)
if [ "$TAILWIND_COUNT" != "0" ] 2>/dev/null; then
  echo "FAILED ($TAILWIND_COUNT @tailwind directives found — PostCSS plugin missing)"
  exit 1
fi
echo "OK"

# 2. Check CSS size is reasonable
echo -n "[2/3] CSS size check... "
CSS_SIZE=$(stat -f%z public/css/app.css 2>/dev/null || stat -c%s public/css/app.css 2>/dev/null)
if [ "$CSS_SIZE" -lt 100000 ]; then
  echo "FAILED ($CSS_SIZE bytes — expected >= 1MB, tailwind likely not processed)"
  exit 1
fi
echo "OK ($CSS_SIZE bytes)"

# 3. Check dashboard-themed-header has no !important dark color
echo -n "[3/3] No !important dark in .dashboard-themed-header... "
if grep -q 'dashboard-themed-header{background-color:#0f172a' public/css/app.css 2>/dev/null; then
  echo "FAILED (found hardcoded dark color — header will render dark)"
  exit 1
fi
echo "OK"

echo ""
echo "=== All checks passed ==="
