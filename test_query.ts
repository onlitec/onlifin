import { createClient } from '@supabase/supabase-js';

const SUPABASE_URL = process.env.VITE_SUPABASE_URL || 'http://onlifin-api:8000';
const SUPABASE_ANON_KEY = process.env.VITE_SUPABASE_ANON_KEY || 'dummy';

const supabase = createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

async function test() {
  const { data, error } = await supabase
    .from('transactions')
    .select('count')
    .eq('person_id', 'c08d171e-7fef-429e-8dbd-a1c567cb1234')
    .is('company_id', null);
  console.log(data, error);
}

test();
