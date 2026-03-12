import fs from 'fs';

const content = fs.readFileSync('src/pages/Reports.tsx', 'utf-8');
console.log(content.includes('companyId, personId, reportType'));
