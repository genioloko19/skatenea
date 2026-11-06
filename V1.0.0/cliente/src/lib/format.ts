import type { RaffleStatus, Role, TicketStatus } from '../api/types';

const currencyFormatter = new Intl.NumberFormat('es-CO', {
  style: 'currency',
  currency: 'COP',
  maximumFractionDigits: 0,
});

const dateFormatter = new Intl.DateTimeFormat('es-CO', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
});

const dateTimeFormatter = new Intl.DateTimeFormat('es-CO', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
});

export function currency(value: number | string | null | undefined): string {
  return currencyFormatter.format(Number(value ?? 0));
}

export function formatDate(value: string | null | undefined): string {
  if (!value) return '—';
  const date = new Date(value.includes('T') ? value : value.replace(' ', 'T') + 'Z');
  return Number.isNaN(date.getTime()) ? '—' : dateFormatter.format(date);
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) return '—';
  const date = new Date(value.includes('T') ? value : value.replace(' ', 'T') + 'Z');
  return Number.isNaN(date.getTime()) ? '—' : dateTimeFormatter.format(date);
}

export function roleLabel(role: Role): string {
  return { super_admin: 'Super administrador', leader: 'Líder', seller: 'Vendedor', buyer: 'Comprador' }[role];
}

export function raffleStatusLabel(status: RaffleStatus): string {
  return {
    draft: 'Borrador',
    pending_approval: 'Pendiente de aprobación',
    approved: 'Aprobada',
    active: 'Activa',
    closed: 'Cerrada',
    finished: 'Finalizada',
    cancelled: 'Cancelada',
    rejected: 'Rechazada',
  }[status];
}

export function ticketStatusLabel(status: TicketStatus): string {
  return {
    available: 'Disponible',
    reserved: 'Separada',
    paid: 'Pagada',
    cancelled: 'Anulada',
    winner: 'Ganadora',
  }[status];
}

export function percent(part: number, total: number): number {
  if (total <= 0) return 0;
  return Math.min(100, Math.round((part / total) * 100));
}
