#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Files to fix
const filesToFix = [
  'src/pages/PWAInfo.tsx',
  'src/pages/Cards.tsx',
  'src/pages/Reconciliation.tsx',
  'src/pages/ForecastDashboard.tsx',
  'src/pages/Categories.tsx',
  'src/pages/Reports.tsx',
  'src/pages/BillsToPay.tsx',
  'src/pages/Admin.tsx',
  'src/pages/Import.tsx',
  'src/pages/Login.tsx',
  'src/pages/Chat.tsx',
  'src/pages/BillsToReceive.tsx',
  'src/pages/Dashboard.tsx',
  'src/pages/UserManagement.tsx',
  'src/pages/Transactions.tsx',
  'src/pages/AIAdmin.tsx',
  'src/pages/Accounts.tsx',
  'src/pages/ImportStatements.tsx',
  'src/pages/DashboardOld.tsx',
  'src/hooks/use-supabase-upload.ts'
];

let totalFixed = 0;
let totalHooksReplaced = 0;

filesToFix.forEach(filePath => {
  const fullPath = path.join(__dirname, filePath);
  
  if (!fs.existsSync(fullPath)) {
    console.log(`⚠️  File not found: ${filePath}`);
    return;
  }

  let content = fs.readFileSync(fullPath, 'utf8');
  const originalContent = content;
  
  // Track hooks that will be replaced
  const useStateMatches = (content.match(/\buseState\(/g) || []).length;
  const useEffectMatches = (content.match(/\buseEffect\(/g) || []).length;
  const useCallbackMatches = (content.match(/\buseCallback\(/g) || []).length;
  const useMemoMatches = (content.match(/\buseMemo\(/g) || []).length;
  const useRefMatches = (content.match(/\buseRef\(/g) || []).length;
  
  // Step 1: Replace the import statement
  // Match various patterns of React hook imports
  const importPatterns = [
    /import\s+{\s*([^}]*useState[^}]*)\s*}\s+from\s+['"]react['"]/g,
    /import\s+{\s*([^}]*useEffect[^}]*)\s*}\s+from\s+['"]react['"]/g,
    /import\s+{\s*([^}]*useCallback[^}]*)\s*}\s+from\s+['"]react['"]/g,
    /import\s+{\s*([^}]*useMemo[^}]*)\s*}\s+from\s+['"]react['"]/g,
    /import\s+{\s*([^}]*useRef[^}]*)\s*}\s+from\s+['"]react['"]/g
  ];
  
  // Check if file already has namespace import
  const hasNamespaceImport = /import\s+\*\s+as\s+React\s+from\s+['"]react['"]/.test(content);
  
  if (!hasNamespaceImport) {
    // Extract all imported hooks
    const hookImportMatch = content.match(/import\s+{\s*([^}]+)\s*}\s+from\s+['"]react['"]/);
    
    if (hookImportMatch) {
      const imports = hookImportMatch[1].split(',').map(s => s.trim());
      const hooks = imports.filter(imp => 
        imp.startsWith('use') && 
        !imp.includes('type') && 
        !imp.includes('Type')
      );
      const types = imports.filter(imp => 
        imp.includes('type') || 
        imp.includes('Type') ||
        !imp.startsWith('use')
      );
      
      // Replace the import line
      let newImport = `import * as React from 'react';`;
      if (types.length > 0) {
        newImport += `\nimport type { ${types.join(', ')} } from 'react';`;
      }
      
      content = content.replace(
        /import\s+{\s*[^}]+\s*}\s+from\s+['"]react['"]/,
        newImport
      );
    }
  }
  
  // Step 2: Replace hook usages
  // Replace useState( with React.useState(
  content = content.replace(/\buseState\(/g, 'React.useState(');
  
  // Replace useEffect( with React.useEffect(
  content = content.replace(/\buseEffect\(/g, 'React.useEffect(');
  
  // Replace useCallback( with React.useCallback(
  content = content.replace(/\buseCallback\(/g, 'React.useCallback(');
  
  // Replace useMemo( with React.useMemo(
  content = content.replace(/\buseMemo\(/g, 'React.useMemo(');
  
  // Replace useRef( with React.useRef(
  content = content.replace(/\buseRef\(/g, 'React.useRef(');
  
  // Only write if content changed
  if (content !== originalContent) {
    fs.writeFileSync(fullPath, content, 'utf8');
    const hooksReplaced = useStateMatches + useEffectMatches + useCallbackMatches + useMemoMatches + useRefMatches;
    console.log(`✅ Fixed: ${filePath} (${hooksReplaced} hooks)`);
    totalFixed++;
    totalHooksReplaced += hooksReplaced;
  } else {
    console.log(`⏭️  Skipped: ${filePath} (already correct)`);
  }
});

console.log(`\n📊 Summary:`);
console.log(`   Files fixed: ${totalFixed}`);
console.log(`   Hooks replaced: ${totalHooksReplaced}`);
console.log(`\n✅ All files processed!`);
