import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import type { BackgroundId, Geometry, Preferences, ThemeId, ThemeMode } from '../api/types';

const STORAGE_KEY = 'skatenea:appearance';

interface Appearance {
  theme: ThemeId;
  mode: ThemeMode;
  geometry: Geometry;
  background: BackgroundId;
  motion: boolean;
}

interface ThemeContextValue extends Appearance {
  setTheme: (theme: ThemeId) => void;
  setMode: (mode: ThemeMode) => void;
  setGeometry: (geometry: Geometry) => void;
  setBackground: (background: BackgroundId) => void;
  setMotion: (motion: boolean) => void;
  cycleMode: () => void;
  reset: () => void;
  applyPreferences: (preferences: Partial<Preferences>) => void;
}

const DEFAULT_APPEARANCE: Appearance = {
  theme: 'aurora',
  mode: 'dark',
  geometry: 'soft',
  background: 'aurora',
  motion: true,
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

function loadAppearance(): Appearance {
  if (typeof window === 'undefined') return DEFAULT_APPEARANCE;
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) return DEFAULT_APPEARANCE;
    const parsed = JSON.parse(raw) as Partial<Appearance>;
    return {
      theme: parsed.theme ?? DEFAULT_APPEARANCE.theme,
      mode: parsed.mode ?? DEFAULT_APPEARANCE.mode,
      geometry: parsed.geometry ?? DEFAULT_APPEARANCE.geometry,
      background: parsed.background ?? DEFAULT_APPEARANCE.background,
      motion: parsed.motion ?? DEFAULT_APPEARANCE.motion,
    };
  } catch {
    return DEFAULT_APPEARANCE;
  }
}

function applyToDocument(appearance: Appearance): void {
  const root = document.documentElement;
  root.dataset.theme = appearance.theme;
  root.dataset.mode = appearance.mode;
  root.dataset.geometry = appearance.geometry;
  root.dataset.background = appearance.background;
  root.dataset.motion = appearance.motion ? 'on' : 'off';
}

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [appearance, setAppearance] = useState<Appearance>(loadAppearance);

  useEffect(() => {
    applyToDocument(appearance);
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(appearance));
  }, [appearance]);

  const update = useCallback((partial: Partial<Appearance>) => {
    setAppearance((current) => ({ ...current, ...partial }));
  }, []);

  const setTheme = useCallback((theme: ThemeId) => update({ theme }), [update]);
  const setMode = useCallback((mode: ThemeMode) => update({ mode }), [update]);
  const setGeometry = useCallback((geometry: Geometry) => update({ geometry }), [update]);
  const setBackground = useCallback((background: BackgroundId) => update({ background }), [update]);
  const setMotion = useCallback((motion: boolean) => update({ motion }), [update]);

  const cycleMode = useCallback(() => {
    setAppearance((current) => ({
      ...current,
      mode: current.mode === 'light' ? 'dark' : current.mode === 'dark' ? 'contrast' : 'light',
    }));
  }, []);

  const reset = useCallback(() => {
    setAppearance(DEFAULT_APPEARANCE);
  }, []);

  const applyPreferences = useCallback((preferences: Partial<Preferences>) => {
    setAppearance((current) => ({
      ...current,
      theme: (preferences.theme as ThemeId | undefined) ?? current.theme,
      mode: (preferences.mode as ThemeMode | undefined) ?? current.mode,
      geometry: (preferences.geometry as Geometry | undefined) ?? current.geometry,
      background: (preferences.background as BackgroundId | undefined) ?? current.background,
    }));
  }, []);

  const value = useMemo<ThemeContextValue>(
    () => ({
      ...appearance,
      setTheme,
      setMode,
      setGeometry,
      setBackground,
      setMotion,
      cycleMode,
      reset,
      applyPreferences,
    }),
    [appearance, setTheme, setMode, setGeometry, setBackground, setMotion, cycleMode, reset, applyPreferences],
  );

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeContextValue {
  const context = useContext(ThemeContext);
  if (!context) {
    throw new Error('useTheme debe usarse dentro de ThemeProvider');
  }
  return context;
}
