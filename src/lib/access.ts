import type { Profile } from '@/types/types';

type AuthLikeUser = {
  app_metadata?: Record<string, unknown> | null;
  user_metadata?: Record<string, unknown> | null;
  role?: string | null;
} | null | undefined;

function coerceBoolean(value: unknown): boolean {
  if (typeof value === 'boolean') {
    return value;
  }

  if (typeof value === 'string') {
    return ['1', 'true', 't', 'yes', 'y', 'on'].includes(value.trim().toLowerCase());
  }

  if (typeof value === 'number') {
    return value === 1;
  }

  return false;
}

function readProfileSettings(profile: Profile | null | undefined): Record<string, unknown> {
  const settings = profile?.settings;
  return settings && typeof settings === 'object' ? settings : {};
}

export function getResolvedUserRole(profile: Profile | null | undefined, user: AuthLikeUser): string {
  const profileRole = typeof profile?.role === 'string' ? profile.role : null;
  const appRole = typeof user?.app_metadata?.role === 'string' ? user.app_metadata.role : null;
  const authRole = typeof user?.role === 'string' ? user.role : null;

  return (profileRole || appRole || authRole || 'user').toString();
}

export function isPlatformAdmin(profile: Profile | null | undefined, user: AuthLikeUser): boolean {
  return getResolvedUserRole(profile, user) === 'admin';
}

export function isAccountAdmin(profile: Profile | null | undefined, user: AuthLikeUser): boolean {
  if (isPlatformAdmin(profile, user)) {
    return true;
  }

  const settings = readProfileSettings(profile);
  return coerceBoolean(
    settings.account_admin
      ?? settings.is_account_admin
      ?? user?.app_metadata?.account_admin
      ?? user?.user_metadata?.account_admin
  );
}

export function canAccessAdministration(profile: Profile | null | undefined, user: AuthLikeUser): boolean {
  return isAccountAdmin(profile, user);
}

export function canAccessPlatformSettings(profile: Profile | null | undefined, user: AuthLikeUser): boolean {
  return isPlatformAdmin(profile, user);
}

export function getAccessRoleLabel(profile: Profile | null | undefined, user: AuthLikeUser): string {
  if (isPlatformAdmin(profile, user)) {
    return 'Admin da Plataforma';
  }

  if (isAccountAdmin(profile, user)) {
    return 'Admin da Conta';
  }

  return 'Usuario';
}
