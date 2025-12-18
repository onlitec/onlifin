#!/bin/bash

# Restart Dev Server Script
# This script clears all Vite caches and restarts the development server

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║              🔄 Restarting Dev Server with Fixed Code               ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""

echo "📋 Step 1: Clearing all Vite caches..."
rm -rf node_modules/.vite dist .vite
echo "✅ Caches cleared"
echo ""

echo "📋 Step 2: Verifying cache removal..."
if [ -d "node_modules/.vite" ]; then
    echo "❌ Warning: node_modules/.vite still exists"
else
    echo "✅ Confirmed: node_modules/.vite removed"
fi
echo ""

echo "📋 Step 3: Verifying source code..."
MIXED_IMPORTS=$(grep -r "import.*{.*}.*from ['\"]react['\"]" src --include="*.tsx" --include="*.ts" 2>/dev/null | grep -v "react-router" | grep -v "react-hook-form" | grep -v "react-dom" | grep -v "next-themes" | grep -v "react-dropzone" | wc -l)
echo "   Mixed React imports found: $MIXED_IMPORTS"
if [ "$MIXED_IMPORTS" -eq 0 ]; then
    echo "✅ Source code is clean"
else
    echo "❌ Warning: Mixed imports still exist"
fi
echo ""

echo "📋 Step 4: Starting dev server..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🚀 Dev server starting with FIXED code..."
echo ""
echo "⚠️  IMPORTANT: After the server starts, hard refresh your browser:"
echo "   • Chrome/Edge: Ctrl+Shift+R (Win) or Cmd+Shift+R (Mac)"
echo "   • Firefox: Ctrl+F5 (Win) or Cmd+Shift+R (Mac)"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

npm run dev
