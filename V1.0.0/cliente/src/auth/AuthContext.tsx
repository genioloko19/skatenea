import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { api } from '../api/client';
import type { Preferences, SessionPayload, User } from '../api/types';

interface AuthContextValue {
  user: User | null;
  permissions: string[];
  preferences: Preferences | null;
  loading: boolean;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<SessionPayload>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
  hasPermission: (permission: string) => boolean;
  savePreferences: (input: Partial<Preferences>) => Promise<Preferences>;
  updateLocalUser: (user: User) => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [permissions, setPermissions] = useState<string[]>([]);
  const [preferences, setPreferences] = useState<Preferences | null>(null);
  const [loading, setLoading] = useState(true);
  const bootstrapRef = useRef<Promise<void> | null>(null);

  const loadSession = useCallback(async () => {
    if (!api.hasRefreshHint() && !api.hasAccessToken()) {
      setUser(null);
      setPermissions([]);
      setPreferences(null);
      return;
    }
    try {
      const me = await api.me();
      setUser(me.user);
      setPermissions(me.permissions);
      setPreferences(me.preferences);
    } catch {
      api.clearSession();
      setUser(null);
      setPermissions([]);
      setPreferences(null);
    }
  }, []);

  useEffect(() => {
    if (!bootstrapRef.current) {
      bootstrapRef.current = loadSession().finally(() => setLoading(false));
    }
  }, [loadSession]);

  const login = useCallback(async (email: string, password: string) => {
    const session = await api.login(email, password);
    setUser(session.user);
    setPermissions(session.permissions);
    const me = await api.me().catch(() => null);
    if (me) {
      setPreferences(me.preferences);
      setUser(me.user);
      setPermissions(me.permissions);
    }
    return session;
  }, []);

  const logout = useCallback(async () => {
    await api.logout().catch(() => undefined);
    setUser(null);
    setPermissions([]);
    setPreferences(null);
  }, []);

  const refresh = useCallback(async () => {
    await loadSession();
  }, [loadSession]);

  const hasPermission = useCallback(
    (permission: string) => permissions.includes(permission) || permissions.includes('*'),
    [permissions],
  );

  const savePreferences = useCallback(async (input: Partial<Preferences>) => {
    const updated = await api.updatePreferences(input);
    setPreferences(updated);
    return updated;
  }, []);

  const updateLocalUser = useCallback((nextUser: User) => {
    setUser(nextUser);
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      permissions,
      preferences,
      loading,
      isAuthenticated: user !== null,
      login,
      logout,
      refresh,
      hasPermission,
      savePreferences,
      updateLocalUser,
    }),
    [user, permissions, preferences, loading, login, logout, refresh, hasPermission, savePreferences, updateLocalUser],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth debe usarse dentro de AuthProvider');
  }
  return context;
}
