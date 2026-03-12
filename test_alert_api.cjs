// Test script para verificar API de alert_preferences
const { createClient } = require('@supabase/supabase-js');

// Configurar Supabase client
const supabaseUrl = 'http://localhost:8000';
const supabaseAnonKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im9ubGlmaW4iLCJpYXQiOjE3MzA0NTI4MDAsImV4cCI6MjA0NjAyODQwMH0.3_vwzJhKqKl2VJj6UoP2qo3sJf8t9k6t8w7z2y3x4c';

const supabase = createClient(supabaseUrl, supabaseAnonKey);

async function testAlertAPI() {
  console.log('🔍 Testando API de alert_preferences...');
  
  try {
    // 1. Tentar login
    console.log('\n1. Fazendo login...');
    const { data: signInData, error: signInError } = await supabase.auth.signInWithPassword({
      email: 'onlifinadmin@miaoda.com',
      password: 'onlifin@123'
    });
    
    if (signInError) {
      console.error('❌ Erro no login:', signInError);
      return;
    }
    
    console.log('✅ Login successful:', signInData.user?.id);
    
    // 2. Tentar buscar preferências
    console.log('\n2. Buscando preferências...');
    const { data: preferences, error: prefError } = await supabase
      .from('alert_preferences')
      .select('*')
      .eq('user_id', signInData.user.id);
    
    if (prefError) {
      console.error('❌ Erro ao buscar preferências:', prefError);
      return;
    }
    
    console.log('✅ Preferências encontradas:', preferences?.length || 0);
    if (preferences && preferences.length > 0) {
      console.log('📋 Primeira preferência:', preferences[0]);
    }
    
    // 3. Tentar criar/atualizar preferências
    console.log('\n3. Testando upsert...');
    const { data: upsertData, error: upsertError } = await supabase
      .from('alert_preferences')
      .upsert({
        user_id: signInData.user.id,
        days_before_due: 3,
        days_before_overdue: 1,
        alert_due_soon: true,
        alert_overdue: true,
        alert_paid: true,
        alert_received: true,
        toast_notifications: true,
        database_notifications: true,
        email_notifications: false,
        push_notifications: false,
        quiet_hours_start: '22:00:00',
        quiet_hours_end: '08:00:00',
        weekend_notifications: true
      })
      .select()
      .single();
    
    if (upsertError) {
      console.error('❌ Erro no upsert:', upsertError);
      return;
    }
    
    console.log('✅ Upsert successful:', upsertData);
    
    // 4. Logout
    await supabase.auth.signOut();
    console.log('\n✅ Teste concluído com sucesso!');
    
  } catch (error) {
    console.error('❌ Erro inesperado:', error);
  }
}

testAlertAPI();
