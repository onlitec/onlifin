
/*
# Criar Usuário Administrador

1. Descrição
   - Cria o primeiro usuário administrador do sistema
   - Username: admin
   - Email: admin@miaoda.com
   - Senha: *M3a74g20M
   - Role: admin (atribuído automaticamente pelo trigger)

2. Detalhes Técnicos
   - Insere usuário na tabela auth.users
   - Senha é criptografada usando bcrypt
   - Trigger handle_new_user() criará automaticamente o perfil
   - Primeiro usuário recebe role 'admin' automaticamente

3. Notas
   - Este é o primeiro usuário do sistema
   - Terá acesso total a todas as funcionalidades
   - Pode criar e gerenciar outros usuários
*/

-- Criar usuário admin no sistema de autenticação
INSERT INTO auth.users (
  instance_id,
  id,
  aud,
  role,
  email,
  encrypted_password,
  email_confirmed_at,
  recovery_sent_at,
  last_sign_in_at,
  raw_app_meta_data,
  raw_user_meta_data,
  created_at,
  updated_at,
  confirmation_token,
  email_change,
  email_change_token_new,
  recovery_token
) VALUES (
  '00000000-0000-0000-0000-000000000000',
  gen_random_uuid(),
  'authenticated',
  'authenticated',
  'admin@miaoda.com',
  crypt('*M3a74g20M', gen_salt('bf')),
  NOW(),
  NOW(),
  NOW(),
  '{"provider":"email","providers":["email"]}',
  '{"username":"admin"}',
  NOW(),
  NOW(),
  '',
  '',
  '',
  ''
);
