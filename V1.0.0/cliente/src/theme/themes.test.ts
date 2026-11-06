import { describe, expect, it } from 'vitest';
import { BACKGROUNDS, GEOMETRIES, MODES, THEMES } from './themes';

describe('sistema de temas', () => {
  it('define exactamente 12 temas', () => {
    expect(THEMES).toHaveLength(12);
    expect(new Set(THEMES.map((theme) => theme.id)).size).toBe(12);
  });

  it('define los 3 modos requeridos', () => {
    expect(MODES.map((mode) => mode.id)).toEqual(['light', 'dark', 'contrast']);
  });

  it('define 4 geometrías', () => {
    expect(GEOMETRIES.map((geometry) => geometry.id)).toEqual(['soft', 'sharp', 'pill', 'organic']);
  });

  it('define 6 fondos', () => {
    expect(BACKGROUNDS).toHaveLength(6);
  });

  it('cada tema tiene 3 colores de muestra', () => {
    for (const theme of THEMES) {
      expect(theme.swatches).toHaveLength(3);
      for (const color of theme.swatches) {
        expect(color).toMatch(/^#[0-9a-f]{6}$/i);
      }
    }
  });
});
