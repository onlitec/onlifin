import { createClient } from '@supabase/supabase-js';

const SUPABASE_URL = process.env.VITE_SUPABASE_URL || 'http://onlifin-api:8000';
const SUPABASE_ANON_KEY = process.env.VITE_SUPABASE_ANON_KEY || 'dummy';

const supabase = createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

async function run() {
  const { data, error } = await supabase
    .from('transactions')
    .select('id, person_id, amount')
    .eq('user_id', 'f1ecfdc3-81e7-4d61-bcf0-6e49ba55a742')
    .eq('person_id', '836d9c11-8421-4109-877b-ed4032a5d0f1')
    .is('company_id', null);
  
  console.log("Filtered count for Alessandro:", data?.length);

  const { data: d2 } = await supabase
    .from('transactions')
    .select('id, person_id, amount')
    .eq('user_id', 'f1ecfdc3-81e7-4d61-bcf0-6e49ba55a742')
    .eq('person_id', 'c08d171e-7fef-429e-8dbd-a1c567cb1234')
    .is('company_id', null);
  
  console.log("Filtered count for Marcia:", d2?.length);
}

run();
