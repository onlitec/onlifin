import { createClient } from '@supabase/supabase-js';

const SUPABASE_URL = process.env.VITE_SUPABASE_URL || 'http://onlifin-api:8000';
const SUPABASE_ANON_KEY = process.env.VITE_SUPABASE_ANON_KEY || 'dummy';

const supabase = createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

async function run() {
  const userId = 'f1ecfdc3-81e7-4d61-bcf0-6e49ba55a742';

  // For Alessandro
  let q1 = supabase
      .from('transactions')
      .select('amount, category:categories(name, color)')
      .eq('user_id', userId)
      .eq('type', 'expense')
      .eq('is_transfer', false)
      .gte('date', '2025-09-01')
      .lte('date', '2026-03-31')
      .is('company_id', null)
      .eq('person_id', '836d9c11-8421-4109-877b-ed4032a5d0f1');
      
  const { data: d1 } = await q1;
  console.log("Alessandro expense txs:", d1?.length);

  // For Marcia
  let q2 = supabase
      .from('transactions')
      .select('amount, category:categories(name, color)')
      .eq('user_id', userId)
      .eq('type', 'expense')
      .eq('is_transfer', false)
      .gte('date', '2025-09-01')
      .lte('date', '2026-03-31')
      .is('company_id', null)
      .eq('person_id', 'c08d171e-7fef-429e-8dbd-a1c567cb1234');
      
  const { data: d2 } = await q2;
  console.log("Marcia expense txs:", d2?.length);
  
  // For General/All
  let q3 = supabase
      .from('transactions')
      .select('amount, category:categories(name, color)')
      .eq('user_id', userId)
      .eq('type', 'expense')
      .eq('is_transfer', false)
      .gte('date', '2025-09-01')
      .lte('date', '2026-03-31')
      .is('company_id', null);
      
  const { data: d3 } = await q3;
  console.log("General expense txs:", d3?.length);
}

run();
