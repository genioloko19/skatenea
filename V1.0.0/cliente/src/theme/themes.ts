import type { BackgroundId, Geometry, ThemeId, ThemeMode } from '../api/types';

export interface ThemeDefinition {
  id: ThemeId;
  name: string;
  description: string;
  swatches: [string, string, string];
}

export const THEMES: ThemeDefinition[] = [
  { id: 'aurora', name: 'Aurora', description: 'Verdes y violetas boreales', swatches: ['#7c9cff', '#4be3c1', '#ff7ad9'] },
  { id: 'neon', name: 'Neón', description: 'Energía nocturna y contraste', swatches: ['#ff3df2', '#41f5ff', '#f9ff4d'] },
  { id: 'sunset', name: 'Atardecer', description: 'Cálido, cinematográfico', swatches: ['#ff8a5b', '#ffd166', '#ef476f'] },
  { id: 'ocean', name: 'Océano', description: 'Azules profundos y calma', swatches: ['#35c2ff', '#2ce5b4', '#7aa8ff'] },
  { id: 'forest', name: 'Bosque', description: 'Natural, orgánico, sereno', swatches: ['#7bd88f', '#c6f06b', '#f0b86e'] },
  { id: 'candy', name: 'Candy', description: 'Dulce, lúdico, vibrante', swatches: ['#ff7ac8', '#8be9fd', '#ffe066'] },
  { id: 'cyber', name: 'Cyber', description: 'Futurista y hacker', swatches: ['#00f0ff', '#ff00a8', '#b6ff00'] },
  { id: 'royal', name: 'Royal', description: 'Elegancia violeta y oro', swatches: ['#b28dff', '#7bdff2', '#ffd166'] },
  { id: 'terracotta', name: 'Terracota', description: 'Artesanal y terroso', swatches: ['#e07a5f', '#f2cc8f', '#81b29a'] },
  { id: 'mono', name: 'Monocromo', description: 'Minimalismo editorial', swatches: ['#e5e7eb', '#9ca3af', '#f9fafb'] },
  { id: 'tropical', name: 'Tropical', description: 'Fresco, solar, festivo', swatches: ['#00d1b2', '#ffdd57', '#ff6b6b'] },
  { id: 'galaxy', name: 'Galaxia', description: 'Cósmico y soñador', swatches: ['#9b8cff', '#ff8ae2', '#6ee7ff'] },
];

export const MODES: { id: ThemeMode; name: string; description: string }[] = [
  { id: 'light', name: 'Claro', description: 'Superficies luminosas' },
  { id: 'dark', name: 'Oscuro', description: 'Ideal para poca luz' },
  { id: 'contrast', name: 'Alto contraste', description: 'Máxima legibilidad' },
];

export const GEOMETRIES: { id: Geometry; name: string; description: string }[] = [
  { id: 'soft', name: 'Suave', description: 'Curvas equilibradas' },
  { id: 'sharp', name: 'Recta', description: 'Bordes precisos' },
  { id: 'pill', name: 'Píldora', description: 'Curvas generosas' },
  { id: 'organic', name: 'Orgánica', description: 'Formas asimétricas' },
];

export const BACKGROUNDS: { id: BackgroundId; name: string; description: string }[] = [
  { id: 'aurora', name: 'Aurora', description: 'Luces en movimiento' },
  { id: 'mesh', name: 'Malla', description: 'Degradados fluidos' },
  { id: 'waves', name: 'Ondas', description: 'Capas suaves' },
  { id: 'orbs', name: 'Orbes', description: 'Esferas flotantes' },
  { id: 'grid', name: 'Retícula', description: 'Estructura tecnológica' },
  { id: 'static', name: 'Estático', description: 'Sin animación' },
];

export const THEME_IDS = THEMES.map((theme) => theme.id);
export const MODE_IDS = MODES.map((mode) => mode.id);
export const GEOMETRY_IDS = GEOMETRIES.map((geometry) => geometry.id);
export const BACKGROUND_IDS = BACKGROUNDS.map((background) => background.id);
