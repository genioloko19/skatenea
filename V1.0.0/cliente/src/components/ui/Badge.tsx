import type { ReactNode } from 'react';
import { cn } from '../../lib/cn';
import type { RaffleStatus, TicketStatus } from '../../api/types';
import { raffleStatusLabel, ticketStatusLabel } from '../../lib/format';

type BadgeTone = 'neutral' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'violet';

export function Badge({ tone = 'neutral', children, className }: { tone?: BadgeTone; children: ReactNode; className?: string }) {
  return <span className={cn('badge', `badge--${tone}`, className)}>{children}</span>;
}

const raffleTone: Record<RaffleStatus, BadgeTone> = {
  draft: 'neutral',
  pending_approval: 'warning',
  approved: 'info',
  active: 'success',
  closed: 'neutral',
  finished: 'violet',
  cancelled: 'danger',
  rejected: 'danger',
};

const ticketTone: Record<TicketStatus, BadgeTone> = {
  available: 'success',
  reserved: 'warning',
  paid: 'info',
  cancelled: 'danger',
  winner: 'violet',
};

export function RaffleStatusBadge({ status }: { status: RaffleStatus }) {
  return <Badge tone={raffleTone[status]}>{raffleStatusLabel(status)}</Badge>;
}

export function TicketStatusBadge({ status }: { status: TicketStatus }) {
  return <Badge tone={ticketTone[status]}>{ticketStatusLabel(status)}</Badge>;
}
