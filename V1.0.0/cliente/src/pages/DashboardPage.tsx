import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  ArrowRight,
  BarChart3,
  CircleDollarSign,
  ClipboardCheck,
  Clock3,
  Plus,
  Ticket,
  Trophy,
  Users,
  Wallet,
} from 'lucide-react';
import { api, ApiError } from '../api/client';
import type { Dashboard, Raffle, Ticket as TicketType } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { Badge, RaffleStatusBadge, TicketStatusBadge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Card, CardBody, CardHeader } from '../components/ui/Card';
import { EmptyState, PageHeader, PageLoader, StatCard } from '../components/ui/Feedback';
import { currency, formatDate, percent, roleLabel } from '../lib/format';

function numberMetric(metrics: Dashboard['metrics'], key: string): number {
  const value = metrics[key];
  return typeof value === 'number' ? value : 0;
}

export function DashboardPage() {
  const { user, hasPermission } = useAuth();
  const [dashboard, setDashboard] = useState<Dashboard | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api.dashboard()
      .then(setDashboard)
      .catch((caught) => setError(caught instanceof ApiError ? caught.message : 'No se pudo cargar el panel.'))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <PageLoader label="Preparando tu panel…" />;
  if (!dashboard || !user) {
    return <EmptyState icon={<BarChart3 size={26} />} title="No pudimos cargar el panel" description={error ?? 'Intenta nuevamente.'} action={<Button onClick={() => window.location.reload()}>Reintentar</Button>} />;
  }

  const metrics = dashboard.metrics;
  const recentRaffles = dashboard.recent_raffles ?? [];
  const recentTickets = dashboard.recent_tickets ?? [];

  return (
    <div className="page-stack">
      <PageHeader
        eyebrow={`${roleLabel(user.role)} · ${new Date().toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' })}`}
        title={`Hola, ${user.full_name.split(' ')[0]}.`}
        subtitle="Este es el pulso de tu operación en SKATENEA."
        actions={
          <>
            {hasPermission('raffles.create') && <Link to="/app/raffles" className="btn btn--primary btn--md"><Plus size={17} /> Nueva rifa</Link>}
            {hasPermission('raffles.approve') && <Link to="/app/approvals" className="btn btn--secondary btn--md"><ClipboardCheck size={17} /> Aprobaciones</Link>}
          </>
        }
      />

      <section className="stats-grid" aria-label="Indicadores principales">
        {dashboard.role === 'super_admin' && (
          <>
            <StatCard label="Usuarios" value={numberMetric(metrics, 'users')} icon={<Users size={19} />} detail="En todos los roles" />
            <StatCard label="Rifas" value={numberMetric(metrics, 'raffles')} icon={<Ticket size={19} />} detail="Activas y cerradas" tone="violet" />
            <StatCard label="Boletas pagadas" value={numberMetric(metrics, 'paid_tickets')} icon={<Wallet size={19} />} detail="Recaudo confirmado" tone="success" />
            <StatCard label="Recaudo" value={currency(numberMetric(metrics, 'revenue'))} icon={<CircleDollarSign size={19} />} detail="Histórico" tone="warning" />
          </>
        )}
        {dashboard.role === 'leader' && (
          <>
            <StatCard label="Mis rifas" value={numberMetric(metrics, 'raffles')} icon={<Ticket size={19} />} detail="Creadas por ti" />
            <StatCard label="Pendientes" value={numberMetric(metrics, 'pending_approval')} icon={<Clock3 size={19} />} detail="Esperando aprobación" tone="warning" />
            <StatCard label="Boletas pagadas" value={numberMetric(metrics, 'paid_tickets')} icon={<Wallet size={19} />} detail="En tus rifas" tone="success" />
            <StatCard label="Recaudo" value={currency(numberMetric(metrics, 'revenue'))} icon={<CircleDollarSign size={19} />} detail="Tus rifas" tone="violet" />
          </>
        )}
        {dashboard.role === 'seller' && (
          <>
            <StatCard label="Mis boletas" value={numberMetric(metrics, 'total')} icon={<Ticket size={19} />} detail="Asignadas" />
            <StatCard label="Disponibles" value={numberMetric(metrics, 'available')} icon={<Wallet size={19} />} detail="Listas para vender" tone="success" />
            <StatCard label="Separadas" value={numberMetric(metrics, 'reserved')} icon={<Clock3 size={19} />} detail="Esperando pago" tone="warning" />
            <StatCard label="Pagadas" value={numberMetric(metrics, 'paid')} icon={<CircleDollarSign size={19} />} detail={currency(numberMetric(metrics, 'revenue'))} tone="violet" />
          </>
        )}
        {dashboard.role === 'buyer' && (
          <>
            <StatCard label="Mis boletas" value={numberMetric(metrics, 'tickets')} icon={<Ticket size={19} />} detail="En todas las rifas" />
            <StatCard label="Pagadas" value={numberMetric(metrics, 'paid')} icon={<Trophy size={19} />} detail="Participación confirmada" tone="success" />
          </>
        )}
      </section>

      <div className="dashboard-grid">
        <Card>
          <CardHeader
            icon={<Ticket size={20} />}
            title={dashboard.role === 'buyer' ? 'Rifas para participar' : 'Rifas recientes'}
            subtitle={dashboard.role === 'buyer' ? 'Explora las rifas activas.' : 'Últimos movimientos de tu inventario.'}
            actions={<Link to="/app/raffles" className="text-link">Ver todas <ArrowRight size={15} /></Link>}
          />
          <CardBody>
            {recentRaffles.length === 0 ? (
              <EmptyState icon={<Ticket size={24} />} title="Aún no hay rifas" description={hasPermission('raffles.create') ? 'Crea la primera rifa para comenzar.' : 'Cuando existan rifas activas aparecerán aquí.'} />
            ) : (
              <div className="list-stack">
                {recentRaffles.map((raffle: Raffle) => (
                  <Link key={raffle.id} to={`/app/raffles/${raffle.id}`} className="list-row list-row--link">
                    <div className="list-row__main">
                      <span className="list-row__icon"><Ticket size={18} /></span>
                      <div>
                        <strong>{raffle.name}</strong>
                        <small>{raffle.code} · {raffle.stats.paid}/{raffle.total_tickets} pagadas · cierra {formatDate(raffle.end_at)}</small>
                      </div>
                    </div>
                    <div className="list-row__end">
                      <div className="mini-progress"><span style={{ width: `${percent(raffle.stats.paid, raffle.total_tickets)}%` }} /></div>
                      <RaffleStatusBadge status={raffle.status} />
                    </div>
                  </Link>
                ))}
              </div>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader
            icon={<Wallet size={20} />}
            title={dashboard.role === 'buyer' ? 'Mis boletas' : 'Actividad de boletas'}
            subtitle={dashboard.role === 'buyer' ? 'Tu participación más reciente.' : 'Estado de las últimas boletas.'}
            actions={<Link to="/app/tickets" className="text-link">Abrir <ArrowRight size={15} /></Link>}
          />
          <CardBody>
            {recentTickets.length === 0 ? (
              <EmptyState icon={<Wallet size={24} />} title="Sin boletas todavía" description={dashboard.role === 'seller' ? 'Cuando te asignen números aparecerán aquí.' : 'Cuando compres una boleta podrás verla aquí.'} />
            ) : (
              <div className="list-stack">
                {recentTickets.map((ticket: TicketType) => (
                  <div key={ticket.id} className="list-row">
                    <div className="list-row__main">
                      <span className="list-row__icon"><Ticket size={18} /></span>
                      <div>
                        <strong>Boleta #{ticket.number}</strong>
                        <small>{ticket.raffle_name ?? 'Rifa'} · {currency(ticket.price)}</small>
                      </div>
                    </div>
                    <TicketStatusBadge status={ticket.status} />
                  </div>
                ))}
              </div>
            )}
          </CardBody>
        </Card>
      </div>

      <Card>
        <CardHeader icon={<BarChart3 size={20} />} title="Siguientes pasos" subtitle="Acciones recomendadas para tu rol." />
        <CardBody>
          <div className="quick-actions">
            {hasPermission('raffles.create') && <Link to="/app/raffles" className="quick-action"><Plus size={20} /><span><strong>Crear una rifa</strong><small>Define premio, precio y boletas.</small></span><ArrowRight size={17} /></Link>}
            {hasPermission('tickets.assign') && <Link to="/app/raffles" className="quick-action"><Users size={20} /><span><strong>Asignar vendedores</strong><small>Distribuye números y activa el equipo.</small></span><ArrowRight size={17} /></Link>}
            {hasPermission('tickets.sell') && <Link to="/app/tickets" className="quick-action"><Wallet size={20} /><span><strong>Vender o separar</strong><small>Registra compradores y pagos.</small></span><ArrowRight size={17} /></Link>}
            {hasPermission('raffles.approve') && <Link to="/app/approvals" className="quick-action"><ClipboardCheck size={20} /><span><strong>Revisar aprobaciones</strong><small>Autoriza las rifas pendientes.</small></span><ArrowRight size={17} /></Link>}
            {dashboard.role === 'buyer' && <Link to="/app/tickets" className="quick-action"><Ticket size={20} /><span><strong>Ver mis boletas</strong><small>Consulta estados y pagos.</small></span><ArrowRight size={17} /></Link>}
          </div>
          <div className="dashboard-note">
            <Badge tone="info">Consejo</Badge>
            <span>Puedes cambiar el tema, el modo y la geometría desde el panel de control del menú de usuario.</span>
          </div>
        </CardBody>
      </Card>
    </div>
  );
}
