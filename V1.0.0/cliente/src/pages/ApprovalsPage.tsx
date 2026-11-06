import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { CheckCircle2, ClipboardCheck, Clock3, XCircle } from 'lucide-react';
import { api, ApiError } from '../api/client';
import type { Raffle } from '../api/types';
import { useToast } from '../components/ui/Toast';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Card } from '../components/ui/Card';
import { EmptyState, PageHeader, PageLoader } from '../components/ui/Feedback';
import { Textarea } from '../components/ui/Field';
import { Modal } from '../components/ui/Modal';
import { currency, formatDate } from '../lib/format';

export function ApprovalsPage() {
  const { toast } = useToast();
  const [raffles, setRaffles] = useState<Raffle[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState<string | null>(null);
  const [rejectTarget, setRejectTarget] = useState<Raffle | null>(null);
  const [reason, setReason] = useState('');

  const load = () => {
    setLoading(true);
    api.raffles({ status: 'pending_approval', limit: 100 })
      .then((payload) => setRaffles(payload.raffles))
      .catch((error) => toast('error', 'No se pudieron cargar las aprobaciones', error instanceof ApiError ? error.message : undefined))
      .finally(() => setLoading(false));
  };

  useEffect(load, []);

  const approve = async (raffle: Raffle) => {
    setBusy(raffle.id);
    try {
      await api.raffleAction(raffle.id, 'approve', {});
      toast('success', 'Rifa aprobada', raffle.name);
      load();
    } catch (error) {
      toast('error', 'No se pudo aprobar', error instanceof ApiError ? error.message : undefined);
    } finally {
      setBusy(null);
    }
  };

  const reject = async () => {
    if (!rejectTarget) return;
    setBusy(rejectTarget.id);
    try {
      await api.raffleAction(rejectTarget.id, 'reject', { reason });
      toast('success', 'Rifa rechazada', rejectTarget.name);
      setRejectTarget(null);
      setReason('');
      load();
    } catch (error) {
      toast('error', 'No se pudo rechazar', error instanceof ApiError ? error.message : undefined);
    } finally {
      setBusy(null);
    }
  };

  return (
    <div className="page-stack">
      <PageHeader
        eyebrow="Gobierno"
        title="Aprobaciones"
        subtitle="Revisa las rifas enviadas por los líderes antes de que puedan operar."
        actions={<Badge tone="warning">{raffles.length} pendientes</Badge>}
      />

      {loading ? <PageLoader label="Cargando solicitudes…" /> : raffles.length === 0 ? (
        <EmptyState icon={<ClipboardCheck size={26} />} title="No hay rifas pendientes" description="Cuando un líder envíe una rifa a aprobación aparecerá aquí." />
      ) : (
        <div className="approval-grid">
          {raffles.map((raffle) => (
            <Card key={raffle.id} className="approval-card">
              <div className="approval-card__top">
                <Badge tone="warning"><Clock3 size={14} /> Pendiente</Badge>
                <span>{raffle.code}</span>
              </div>
              <h3>{raffle.name}</h3>
              <p className="muted-text">{raffle.description || 'Sin descripción.'}</p>
              <div className="info-grid">
                <div><span>Premio</span><strong>{raffle.prize}</strong></div>
                <div><span>Boleta</span><strong>{currency(raffle.ticket_price)}</strong></div>
                <div><span>Total</span><strong>{raffle.total_tickets}</strong></div>
                <div><span>Cierre</span><strong>{formatDate(raffle.end_at)}</strong></div>
              </div>
              <div className="panel-actions">
                <Link to={`/app/raffles/${raffle.id}`} className="btn btn--ghost btn--sm">Ver detalle</Link>
                <Button variant="danger" size="sm" icon={<XCircle size={15} />} onClick={() => setRejectTarget(raffle)}>Rechazar</Button>
                <Button variant="success" size="sm" icon={<CheckCircle2 size={15} />} loading={busy === raffle.id} onClick={() => approve(raffle)}>Aprobar</Button>
              </div>
            </Card>
          ))}
        </div>
      )}

      <Modal
        open={Boolean(rejectTarget)}
        onClose={() => setRejectTarget(null)}
        title="Rechazar rifa"
        subtitle={rejectTarget?.name}
        footer={
          <>
            <Button variant="ghost" onClick={() => setRejectTarget(null)}>Cancelar</Button>
            <Button variant="danger" loading={busy === rejectTarget?.id} onClick={reject} disabled={reason.trim().length < 3}>Rechazar</Button>
          </>
        }
      >
        <Textarea label="Motivo del rechazo" value={reason} onChange={(event) => setReason(event.target.value)} rows={4} required />
      </Modal>
    </div>
  );
}
