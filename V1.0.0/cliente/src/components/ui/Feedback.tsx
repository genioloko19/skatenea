import type { ReactNode } from 'react';
import { Loader2 } from 'lucide-react';
import { cn } from '../../lib/cn';

export function Spinner({ label = 'Cargando', className }: { label?: string; className?: string }) {
  return (
    <span className={cn('spinner', className)} role="status" aria-label={label}>
      <Loader2 size={20} className="spin" />
    </span>
  );
}

export function PageLoader({ label = 'Cargando información…' }: { label?: string }) {
  return (
    <div className="page-loader" role="status">
      <Spinner />
      <span>{label}</span>
    </div>
  );
}

export function EmptyState({
  icon,
  title,
  description,
  action,
}: {
  icon?: ReactNode;
  title: string;
  description?: string;
  action?: ReactNode;
}) {
  return (
    <div className="empty-state">
      {icon && <div className="empty-state__icon" aria-hidden="true">{icon}</div>}
      <h3>{title}</h3>
      {description && <p>{description}</p>}
      {action}
    </div>
  );
}

export function PageHeader({
  eyebrow,
  title,
  subtitle,
  actions,
}: {
  eyebrow?: string;
  title: string;
  subtitle?: string;
  actions?: ReactNode;
}) {
  return (
    <header className="page-header">
      <div>
        {eyebrow && <span className="eyebrow">{eyebrow}</span>}
        <h1>{title}</h1>
        {subtitle && <p>{subtitle}</p>}
      </div>
      {actions && <div className="page-header__actions">{actions}</div>}
    </header>
  );
}

export function StatCard({
  label,
  value,
  detail,
  icon,
  tone = 'primary',
}: {
  label: string;
  value: ReactNode;
  detail?: string;
  icon?: ReactNode;
  tone?: 'primary' | 'success' | 'warning' | 'violet' | 'danger';
}) {
  return (
    <article className={cn('glass stat-card', `stat-card--${tone}`)}>
      <div className="stat-card__top">
        <span>{label}</span>
        {icon && <span className="stat-card__icon" aria-hidden="true">{icon}</span>}
      </div>
      <strong className="stat-card__value">{value}</strong>
      {detail && <span className="stat-card__detail">{detail}</span>}
    </article>
  );
}
