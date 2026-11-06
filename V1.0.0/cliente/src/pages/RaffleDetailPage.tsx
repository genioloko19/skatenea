import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { Link, useParams } from 'react-router-dom';
import {
  ArrowLeft,
  CalendarDays,
  CheckCircle2,
  CircleDollarSign,
  Clock3,
  Download,
  Gift,
  Hash,
  Play,
  ShieldCheck,
  Ticket,
  Trophy,
  Users,
  XCircle,
} from 'lucide-react';
import { api, ApiError } from '../api/client';
import type { RaffleDetail, RaffleSeller, RaffleStatus, User } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/ui/Toast';
import { Badge, RaffleStatusBadge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Card, CardBody, CardHeader } from '../components/ui/Card';
import { EmptyState, PageLoader, StatCard } from '../components/ui/Feedback';
import { Input, Select, Textarea } from '../components/ui/Field';
import { Modal } from '../components/ui/Modal';
import { currency, formatDate, formatDateTime, percent } from '../lib/format';

const MANAGEABLE: RaffleStatus[] = ['draft', 'rejected'];

export function RaffleDetailPage() {
  const { id = '' } = useParams();
  const { user, hasPermission } = useAuth();
  const { toast } = useToast();
  const [detail, setDetail] = useState<RaffleDetail | null>(null);
  const [allSellers, setAllSellers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState<string | null>(null);
  const [rejectOpen, setRejectOpen] = useState(false);
  const [rejectReason, setRejectReason] = useState('');
  const [selectedSellers, setSelectedSellers] = useState<string[]>([]);
  const [range, setRange] = useState({ seller_id: '', from: 1, to: 10 });
  const [drawResult, setDrawResult] = useState<{ winner: { number: number } | null; seed: string; seed_hash: string } | null>(null);

  const load = () => {
    setLoading(true);
    api.raffle(id)
      .then(setDetail)
      .catch((error) => toast('error', 'No se pudo cargar la rifa', error instanceof ApiError ? error.message : undefined))
      .finally(() => setLoading(false));
  };

  useEffect(load, [id]);

  useEffect(() => {
    if (hasPermission('tickets.assign')) {
      api.sellers().then((payload) => setAllSellers(payload.users)).catch(() => undefined);
    }
  }, [hasPermission]);

  const canManage = useMemo(() => {
    if (!detail || !user) return false;
    return user.role === 'super_admin' || (user.role === 'leader' && detail.raffle.created_by === user.id);
  }, [detail, user]);

  if (loading) return <PageLoader label="Cargando rifa…" />;
  if (!detail) return <EmptyState icon={<Ticket size={26} />} title="Rifa no encontrada" action={<Link to="/app/raffles" className="btn btn--secondary btn--md">Volver</Link>} />;

  const { raffle, sellers, approvals } = detail;
  const availableSellers = allSellers.filter((seller) => !sellers.some((assigned) => assigned.id === seller.id));

  const run = async (label: string, action: () => Promise<unknown>) => {
    setBusy(label);
    try {
      await action();
      toast('success', 'Operación completada');
      load();
    } catch (error) {
      toast('error', 'No se pudo completar', error instanceof ApiError ? error.message : undefined);
    } finally {
      setBusy(null);
    }
  };

  const assignSellers = async (event: FormEvent) => {
    event.preventDefault();
    if (selectedSellers.length === 0) return;
    await run('sellers', async () => {
      await api.assignSellers(raffle.id, selectedSellers);
      setSelectedSellers([]);
    });
  };

  const assignRange = async (event: FormEvent) => {
    event.preventDefault();
    await run('range', async () => {
      const result = await api.assignTickets(raffle.id, range.seller_id, range.from, range.to);
      toast('success', 'Rango asignado', `${result.updated} boletas actualizadas.`);
    });
  };

  const exportCsv = async () => {
    setBusy('export');
    try {
      const blob = await api.download(`/raffles/${raffle.id}/export.csv`);
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `rifa-${raffle.code}.csv`;
      anchor.click();
      URL.revokeObjectURL(url);
    } catch (error) {
      toast('error', 'No se pudo exportar', error instanceof ApiError ? error.message : undefined);
    } finally {
      setBusy(null);
    }
  };

  const draw = async () => {
    setBusy('draw');
    try {
      const result = await api.draw(raffle.id);
      setDrawResult(result);
      toast('success', 'Sorteo realizado', 'La rifa quedó finalizada.');
      load();
    } catch (error) {
      toast('error', 'No se pudo sortear', error instanceof ApiError ? error.message : undefined);
    } finally {
      setBusy(null);
    }
  };

  return (
    <div className="page-stack">
      <div className="detail-head">
        <Link to="/app/raffles" className="text-link"><ArrowLeft size={16} /> Volver a rifas</Link>
        <div className="detail-head__main">
          <div>
            <span className="eyebrow">{raffle.code}</span>
            <h1>{raffle.name}</h1>
            <p className="muted-text">{raffle.description || 'Sin descripción adicional.'}</p>
          </div>
          <div className="detail-head__badges">
            <RaffleStatusBadge status={raffle.status} />
            {raffle.approved_at && <Badge tone="success"><ShieldCheck size={14} /> Aprobada</Badge>}
          </div>
        </div>
      </div>

      <section className="stats-grid">
        <StatCard label="Total boletas" value={raffle.total_tickets} icon={<Ticket size={19} />} detail={`${raffle.stats.available} disponibles`} />
        <StatCard label="Pagadas" value={raffle.stats.paid} icon={<CheckCircle2 size={19} />} detail={`${percent(raffle.stats.paid, raffle.total_tickets)}% del total`} tone="success" />
        <StatCard label="Separadas" value={raffle.stats.reserved} icon={<Clock3 size={19} />} detail="Esperando pago" tone="warning" />
        <StatCard label="Recaudo" value={currency(raffle.stats.revenue)} icon={<CircleDollarSign size={19} />} detail="Pagos confirmados" tone="violet" />
      </section>

      <div className="detail-grid">
        <div className="detail-grid__main">
          <Card>
            <CardHeader icon={<Gift size={20} />} title="Información de la rifa" subtitle="Datos base y condiciones." />
            <CardBody>
              <div className="info-grid">
                <div><span>Premio</span><strong>{raffle.prize}</strong></div>
                <div><span>Valor de boleta</span><strong>{currency(raffle.ticket_price)}</strong></div>
                <div><span>Inicio</span><strong>{formatDate(raffle.start_at)}</strong></div>
                <div><span>Cierre</span><strong>{formatDate(raffle.end_at)}</strong></div>
                <div><span>Creada</span><strong>{formatDateTime(raffle.created_at)}</strong></div>
                <div><span>Código</span><strong>{raffle.code}</strong></div>
              </div>
              <div className="progress progress--lg"><span style={{ width: `${percent(raffle.stats.paid, raffle.total_tickets)}%` }} /></div>
              <div className="detail-progress-meta"><span>{raffle.stats.paid} pagadas</span><span>{raffle.stats.reserved} separadas</span><span>{raffle.stats.available} disponibles</span></div>
            </CardBody>
          </Card>

          <Card>
            <CardHeader
              icon={<Users size={20} />}
              title="Vendedores asignados"
              subtitle="Solo los vendedores asignados pueden operar sus números."
              actions={<Badge tone="neutral">{sellers.length}</Badge>}
            />
            <CardBody>
              {sellers.length === 0 ? <p className="muted-text">Aún no hay vendedores asignados.</p> : (
                <div className="list-stack">
                  {sellers.map((seller: RaffleSeller) => (
                    <div key={seller.id} className="list-row">
                      <div><strong>{seller.full_name}</strong><small>{seller.email} · {seller.phone ?? 'Sin teléfono'}</small></div>
                      <Badge tone="success">Asignado</Badge>
                    </div>
                  ))}
                </div>
              )}
              {canManage && hasPermission('tickets.assign') && availableSellers.length > 0 && (
                <form className="inline-form" onSubmit={assignSellers}>
                  <div className="check-grid">
                    {availableSellers.map((seller) => (
                      <label key={seller.id} className="check-row">
                        <input
                          type="checkbox"
                          checked={selectedSellers.includes(seller.id)}
                          onChange={(event) => setSelectedSellers((current) => event.target.checked ? [...current, seller.id] : current.filter((item) => item !== seller.id))}
                        />
                        <span>{seller.full_name}</span>
                      </label>
                    ))}
                  </div>
                  <Button type="submit" size="sm" loading={busy === 'sellers'} disabled={selectedSellers.length === 0}>Asignar vendedores</Button>
                </form>
              )}
            </CardBody>
          </Card>

          {canManage && hasPermission('tickets.assign') && (
            <Card>
              <CardHeader icon={<Hash size={20} />} title="Asignar rango de boletas" subtitle="Solo se actualizan boletas disponibles." />
              <CardBody>
                <form className="form-grid" onSubmit={assignRange}>
                  <Select label="Vendedor" value={range.seller_id} onChange={(event) => setRange({ ...range, seller_id: event.target.value })} required>
                    <option value="">Selecciona…</option>
                    {sellers.map((seller) => <option key={seller.id} value={seller.id}>{seller.full_name}</option>)}
                  </Select>
                  <Input label="Desde" type="number" min={1} value={range.from} onChange={(event) => setRange({ ...range, from: Number(event.target.value) })} required />
                  <Input label="Hasta" type="number" min={1} value={range.to} onChange={(event) => setRange({ ...range, to: Number(event.target.value) })} required />
                  <div className="form-grid__full panel-actions">
                    <Button type="submit" variant="secondary" loading={busy === 'range'} disabled={!range.seller_id}>Asignar rango</Button>
                  </div>
                </form>
              </CardBody>
            </Card>
          )}

          <Card>
            <CardHeader icon={<ShieldCheck size={20} />} title="Historial de aprobación" subtitle="Cada transición queda auditada." />
            <CardBody>
              {approvals.length === 0 ? <p className="muted-text">Sin eventos de aprobación.</p> : (
                <ol className="timeline">
                  {approvals.map((approval) => (
                    <li key={approval.id}>
                      <span className="timeline__dot" />
                      <div>
                        <strong>{approval.action}</strong>
                        <small>{approval.actor_name} · {formatDateTime(approval.created_at)}</small>
                        {approval.reason && <p>{approval.reason}</p>}
                      </div>
                    </li>
                  ))}
                </ol>
              )}
            </CardBody>
          </Card>
        </div>

        <aside className="detail-grid__side">
          <Card>
            <CardHeader icon={<Play size={20} />} title="Acciones" subtitle="Disponibles según el estado y tu rol." />
            <CardBody>
              <div className="action-stack">
                {canManage && MANAGEABLE.includes(raffle.status) && (
                  <Button loading={busy === 'submit'} onClick={() => run('submit', () => api.raffleAction(raffle.id, 'submit'))}><ShieldCheck size={17} /> Enviar a aprobación</Button>
                )}
                {hasPermission('raffles.approve') && raffle.status === 'pending_approval' && (
                  <>
                    <Button loading={busy === 'approve'} onClick={() => run('approve', () => api.raffleAction(raffle.id, 'approve', {}))}><CheckCircle2 size={17} /> Aprobar</Button>
                    <Button variant="danger" onClick={() => setRejectOpen(true)}><XCircle size={17} /> Rechazar</Button>
                  </>
                )}
                {canManage && raffle.status === 'approved' && (
                  <Button loading={busy === 'activate'} onClick={() => run('activate', () => api.raffleAction(raffle.id, 'activate'))}><Play size={17} /> Activar rifa</Button>
                )}
                {canManage && hasPermission('tickets.assign') && ['approved', 'active'].includes(raffle.status) && raffle.stats.total === 0 && (
                  <Button variant="secondary" loading={busy === 'generate'} onClick={() => run('generate', () => api.generateTickets(raffle.id))}><Ticket size={17} /> Generar boletas</Button>
                )}
                {canManage && raffle.status === 'active' && (
                  <Button variant="ghost" loading={busy === 'close'} onClick={() => run('close', () => api.raffleAction(raffle.id, 'close'))}><Clock3 size={17} /> Cerrar rifa</Button>
                )}
                {hasPermission('raffles.approve') && ['active', 'closed'].includes(raffle.status) && (
                  <Button variant="success" loading={busy === 'draw'} onClick={draw}><Trophy size={17} /> Realizar sorteo</Button>
                )}
                {canManage && (
                  <Button variant="secondary" icon={<Download size={17} />} loading={busy === 'export'} onClick={exportCsv}>Exportar CSV</Button>
                )}
                {raffle.status === 'finished' && <div className="alert alert--success">Rifa finalizada. La boleta ganadora y la semilla quedaron registradas.</div>}
              </div>
            </CardBody>
          </Card>

          {drawResult && (
            <Card>
              <CardHeader icon={<Trophy size={20} />} title="Resultado del sorteo" subtitle="Semilla y hash de verificación." />
              <CardBody>
                <div className="info-grid">
                  <div><span>Ganadora</span><strong>#{drawResult.winner?.number ?? '—'}</strong></div>
                  <div><span>Boletas participantes</span><strong>{raffle.stats.paid}</strong></div>
                </div>
                <div className="code-block"><span>Seed</span><code>{drawResult.seed}</code></div>
                <div className="code-block"><span>Hash</span><code>{drawResult.seed_hash}</code></div>
              </CardBody>
            </Card>
          )}

          <Card>
            <CardHeader icon={<CalendarDays size={20} />} title="Resumen rápido" />
            <CardBody>
              <div className="info-list">
                <div><span>Estado</span><RaffleStatusBadge status={raffle.status} /></div>
                <div><span>Boletas</span><strong>{raffle.stats.total}/{raffle.total_tickets}</strong></div>
                <div><span>Recaudo</span><strong>{currency(raffle.stats.revenue)}</strong></div>
                <div><span>Vendedores</span><strong>{sellers.length}</strong></div>
              </div>
            </CardBody>
          </Card>
        </aside>
      </div>

      <Modal
        open={rejectOpen}
        onClose={() => setRejectOpen(false)}
        title="Rechazar rifa"
        subtitle="Explica el motivo para que el líder pueda corregirla."
        footer={
          <>
            <Button variant="ghost" onClick={() => setRejectOpen(false)}>Cancelar</Button>
            <Button variant="danger" loading={busy === 'reject'} onClick={() => { void run('reject', async () => { await api.raffleAction(raffle.id, 'reject', { reason: rejectReason }); setRejectOpen(false); setRejectReason(''); }); }}>Rechazar</Button>
          </>
        }
      >
        <Textarea label="Motivo" value={rejectReason} onChange={(event) => setRejectReason(event.target.value)} rows={4} required />
      </Modal>
    </div>
  );
}
