import { describe, expect, it } from 'vitest';
import { currency, percent, roleLabel, ticketStatusLabel } from './format';

describe('utilidades de formato', () => {
  it('formatea moneda colombiana', () => {
    expect(currency(10000)).toContain('10');
  });

  it('calcula porcentajes con límites', () => {
    expect(percent(50, 100)).toBe(50);
    expect(percent(0, 0)).toBe(0);
    expect(percent(200, 100)).toBe(100);
  });

  it('traduce roles y estados', () => {
    expect(roleLabel('super_admin')).toBe('Super administrador');
    expect(ticketStatusLabel('reserved')).toBe('Separada');
  });
});
