import * as React from 'react';
import { useAuth } from 'miaoda-auth-react';
import { profilesApi } from '@/db/api';
import type { Profile } from '@/types/types';

interface AuthProfileContextValue {
  profile: Profile | null;
  isLoading: boolean;
  refreshProfile: () => Promise<void>;
}

const AuthProfileContext = React.createContext<AuthProfileContextValue | undefined>(undefined);

export function AuthProfileProvider({ children }: { children: React.ReactNode }) {
  const { user } = useAuth();
  const [profile, setProfile] = React.useState<Profile | null>(null);
  const [isLoading, setIsLoading] = React.useState(true);

  const refreshProfile = React.useCallback(async () => {
    if (!user?.id) {
      setProfile(null);
      setIsLoading(false);
      return;
    }

    setIsLoading(true);

    try {
      const nextProfile = await profilesApi.getProfile(user.id);
      setProfile(nextProfile);
    } catch (error) {
      console.error('Falha ao carregar perfil autenticado:', error);
      setProfile(null);
    } finally {
      setIsLoading(false);
    }
  }, [user?.id]);

  React.useEffect(() => {
    void refreshProfile();
  }, [refreshProfile]);

  const value = React.useMemo<AuthProfileContextValue>(() => ({
    profile,
    isLoading,
    refreshProfile,
  }), [profile, isLoading, refreshProfile]);

  return (
    <AuthProfileContext.Provider value={value}>
      {children}
    </AuthProfileContext.Provider>
  );
}

export function useAuthProfile() {
  const context = React.useContext(AuthProfileContext);

  if (!context) {
    throw new Error('useAuthProfile must be used within AuthProfileProvider');
  }

  return context;
}
