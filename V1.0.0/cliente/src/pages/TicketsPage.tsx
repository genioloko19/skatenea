import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { CircleDollarSign, Clock3, Filter, Ticket as TicketIcon, Wallet } from 'lucide-react';
import { api, ApiError } from '../api/client';
import type { Raffle, Ticket, TicketStatus } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/ui/Toast';
import { Badge, TicketStatusBadge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Card, CardBody, CardHeader } from '../components/ui/Card';
import { EmptyState, PageHeader, PageLoader, StatCard } from '../components/ui/Feedback';
import { Input, Select } from '../components/ui/Field';
import { Modal } from '../components/ui/Modal';
import { currency, formatDateTime } from '../lib/format';

const STATUS_FILTERS: { value: TicketStatus | 'all'; label: string }[] = [
  { value: 'all', label: 'Todas' },
  { value: 'available', label: 'Disponibles' },
  { value: 'reserved', label: 'Separadas' },
  { value: 'paid', label: 'Pagadas' },
  { value: 'cancelled', label: 'Anuladas' },
];

export function TicketsPage() {
  const { user } = useAuth();
  const { toast } = useToast();
  const isSeller = user?.role === 'seller';
  const isBuyer = user?.role === 'buyer';
  const isManager = user?.role === 'super_admin' || user?.role === 'leader';

  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [raffles, setRaffles] = useState<Raffle[]>([]);
  const [selectedRaffle, setSelectedRaffle] = useState('');
  const [status, setStatus] = useState<TicketStatus | 'all'>('all');
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState<string | null>(null);
  const [reserveTarget, setReserveTarget] = useState<Ticket | null>(null);
  const [payTarget, setPayTarget] = useState<Ticket | null>(null);

  const [buyerForm, setBuyerForm] = useState({
    full_name: '',
    phone: '',
    email: '',
    document_type: 'CC',
    document_number: '',
    reservation_hours: 48,
  });
  const [paymentForm, setPaymentForm] = useState({ method: 'cash', reference: '' });

  const loadMine = () => {
    setLoading(true);
    api.myTickets()
      .then((payload) => setTickets(payload.tickets))
      .catch((error) => toast('error', 'No se pudieron cargar las boletas', error instanceof ApiError ? error.message : undefined))
      .finally(() => setLoading(false));
  };

  const loadManager = (raffleId: string) => {
    if (!raffleId) {
      setTickets([]);
      setLoading(false);
      return;
    }
    setLoading(true);
    api.ticketsByRaffle(raffleId, { limit: 5000 })
      .then((payload) => setTickets(payload.tickets))
      .catch((error) => toast('error', 'No se pudieron cargar las boletas', error instanceof ApiError ? error.message : undefined))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    if (isManager) {
      api.raffles({ limit: 100 }).then((payload) => {
        setRaffles(payload.raffles);
        if (payload.raffles[0]) {
          setSelectedRaffle(payload.raffles[0].id);
          loadManager(payload.raffles[0].id);
        } else {
          setLoading(false);
        }
      }).catch(() => setLoading(false));
    } else {
      loadMine();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isManager]);

  const filtered = useMemo(
    () => tickets.filter((ticket) => status === 'all' || ticket.status === status),
    [tickets, status],
  );

  const counts = useMemo(() => ({
    total: tickets.length,
    available: tickets.filter((ticket) => ticket.status === 'available').length,
    reserved: tickets.filter((ticket) => ticket.status === 'reserved').length,
    paid: tickets.filter((ticket) => ticket.status === 'paid').length,
  }), [tickets]);

  const refresh = () => {
    if (isManager) loadManager(selectedRaffle);
    else loadMine();
  };

  const reserve = async (event: FormEvent) => {
    event.preventDefault();
    if (!reserveTarget) return;
    setBusy('reserve');
    try {
      await api.reserveTicket(reserveTarget.id, {
        full_name: buyerForm.full_name,
        phone: buyerForm.phone,
        email: buyerForm.email || undefined,
        document_type: buyerForm.document_type,
        document_number: buyerForm.document_number || undefined,
        reservation_hours: Number(buyerForm.reservation_hours),
      });
      toast('success', `Boleta #${reserveTarget.number} separada`);
      setReserveTarget(null);
      setBuyerForm({ full_name: '', phone: '', email: '', document_type: 'CC', document_number: '', reservation_hours: 48 });
      refresh();
    } catch (error) {
      toast('error', 'No se pudo separar', error instanceof ApiError ? error.message : undefined);
    } finally {
      setBusy(null);
    }
  };

  const pay = async (event: FormEvent) => {
    event.preventDefault();
    if (!payTarget) return;
    setBusy('pay');
    try {
      await api.payTicket(payTarget.id, paymentForm.method, paymentForm.reference || undefined);
      toast('success', `Boleta #${payTarget.number} pagada`);
      setPayTarget(null);
      setPaymentForm({ method: 'cash', reference: '' });
      refresh();
    } catch (error) {
      toast('error', 'No se pudo registrar el pago', error instanceof ApiError ? error.message : undefined);
    } finally {
      setBusy(null);
    }
  };

  const cancel = async (ticket: Ticket) => {
    if (!window.confirm(`¿Anular la boleta #${ticket.number}?`)) return;
    setBusy(`cancel-${ticket.id}`);
    try {
      await api.cancelTicket(ticket.id, 'Anulación manual');
      toast('success', `Boleta #${ticket.number} anulada`);
      refresh();
    } catch (error) {
      toast('error', 'No se pudo anular', error instanceof ApiError ? error.message : undefined);
    } finally {
      setBusy(null);
    }
  };

  return (
    <div className="page-stack">
      <PageHeader
        eyebrow="Operación"
        title={isBuyer ? 'Mis boletas' : isSeller ? 'Mis números' : 'Boletas'}
        subtitle={isBuyer ? 'Consulta el estado de las boletas que has comprado.' : isSeller ? 'Gestiona tus boletas disponibles, separadas y pagadas.' : 'Supervisa el inventario de boletas por rifa.'}
        actions={isSeller && <Link to="/app/raffles" className="btn btn--secondary btn--md"><TicketIcon size={17} /> Ver rifas</Link>}
      />

      {!isBuyer && (
        <section className="stats-grid">
          <StatCard label="Totales" value={counts.total} icon={<TicketIcon size={19} />} detail="En la vista actual" />
          <StatCard label="Disponibles" value={counts.available} icon={<Wallet size={19} />} tone="success" detail="Listas para vender" />
          <StatCard label="Separadas" value={counts.reserved} icon={<Clock3 size={19} />} tone="warning" detail="Esperando pago" />
          <StatCard label="Pagadas" value={counts.paid} icon={<CircleDollarSign size={19} />} tone="violet" detail="Confirmadas" />
        </section>
      )}

      <Card>
        <CardBody>
          <div className="toolbar">
            {isManager && (
              <Select
                label="Rifa"
                value={selectedRaffle}
                onChange={(event) => {
                  setSelectedRaffle(event.target.value);
                  loadManager(event.target.value);
                }}
              >
                {raffles.map((raffle) => <option key={raffle.id} value={raffle.id}>{raffle.name} · {raffle.code}</option>)}
              </Select>
            )}
            <div className="chip-row" role="group" aria-label="Filtrar boletas">
              <Filter size={16} className="muted-icon" />
              {STATUS_FILTERS.map((filter) => (
                <button key={filter.value} type="button" className={filter.value === status ? 'chip is-active' : 'chip'} onClick={() => setStatus(filter.value)}>
                  {filter.label}
                </button>
              ))}
            </div>
          </div>
        </CardBody>
      </Card>

      {loading ? <PageLoader label="Cargando boletas…" /> : filtered.length === 0 ? (
        <EmptyState
          icon={<TicketIcon size={26} />}
          title="No hay boletas en esta vista"
          description={isSeller ? 'Cuando te asignen números aparecerán aquí.' : isBuyer ? 'Cuando compres una boleta podrás verla aquí.' : 'Selecciona otra rifa o cambia el filtro.'}
        />
      ) : (
        <Card>
          <CardHeader icon={<TicketIcon size={20} />} title="Detalle de boletas" subtitle={`${filtered.length} registros`} actions={<Badge tone="neutral">{status === 'all' ? 'Todas' : status}</Badge>} />
          <CardBody>
            <div className="table-wrap">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Rifa</th>
                    {!isBuyer && <th>Comprador</th>}
                    {isManager && <th>Vendedor</th>}
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Reserva / pago</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {filtered.map((ticket) => (
                    <tr key={ticket.id}>
                      <td><strong>#{ticket.number}</strong></td>
                      <td>{ticket.raffle_name ?? '—'}</td>
                      {!isBuyer && <td>{ticket.buyer_name ?? '—'}</td>}
                      {isManager && <td>{ticket.seller_name ?? '—'}</td>}
                      <td>{currency(ticket.price)}</td>
                      <td><TicketStatusBadge status={ticket.status} /></td>
                      <td>{ticket.paid_at ? formatDateTime(ticket.paid_at) : ticket.reserved_until ? `Hasta ${formatDateTime(ticket.reserved_until)}` : '—'}</td>
                      <td>
                        <div className="row-actions">
                          {isSeller && ticket.status === 'available' && <Button size="sm" onClick={() => setReserveTarget(ticket)}>Separar</Button>}
                          {isSeller && ticket.status === 'reserved' && <Button size="sm" variant="success" onClick={() => setPayTarget(ticket)}>Pagar</Button>}
                          {(isSeller || isManager) && ['available', 'reserved', 'paid'].includes(ticket.status) && (
                            <Button size="sm" variant="ghost" loading={busy === `cancel-${ticket.id}`} onClick={() => cancel(ticket)}>Anular</Button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardBody>
        </Card>
      )}

      <Modal
        open={Boolean(reserveTarget)}
        onClose={() => setReserveTarget(null)}
        title={reserveTarget ? `Separar boleta #${reserveTarget.number}` : 'Separar boleta'}
        subtitle="Registra los datos del comprador. La reserva expira según las horas indicadas."
        size="lg"
        footer={
          <>
            <Button variant="ghost" onClick={() => setReserveTarget(null)}>Cancelar</Button>
            <Button type="submit" form="reserve-form" loading={busy === 'reserve'}>Separar boleta</Button>
          </>
        }
      >
        <form id="reserve-form" className="form-grid" onSubmit={reserve}>
          <Input label="Nombre completo" value={buyerForm.full_name} onChange={(event) => setBuyerForm({ ...buyerForm, full_name: event.target.value })} required />
          <Input label="Teléfono" value={buyerForm.phone} onChange={(event) => setBuyerForm({ ...buyerForm, phone: event.target.value })} required />
          <Input label="Correo" type="email" value={buyerForm.email} onChange={(event) => setBuyerForm({ ...buyerForm, email: event.target.value })} />
          <Select label="Tipo de documento" value={buyerForm.document_type} onChange={(event) => setBuyerForm({ ...buyerForm, document_type: event.target.value })}>
            <option value="CC">Cédula de ciudadanía</option>
            <option value="CE">Cédula de extranjería</option>
            <option value="PA">Pasaporte</option>
            <option value="NIT">NIT</option>
          </Select>
          <Input label="Número de documento" value={buyerForm.document_number} onChange={(event) => setBuyerForm({ ...buyerForm, document_number: event.target.value })} />
          <Input label="Horas de reserva" type="number" min={1} max={168} value={buyerForm.reservation_hours} onChange={(event) => setBuyerForm({ ...buyerForm, reservation_hours: Number(event.target.value) })} />
        </form>
      </Modal>

      <Modal
        open={Boolean(payTarget)}
        onClose={() => setPayTarget(null)}
        title={payTarget ? `Registrar pago #${payTarget.number}` : 'Registrar pago'}
        subtitle={`Monto a registrar: ${payTarget ? currency(payTarget.price) : '—'}. El monto se toma del valor de la boleta.`}
        footer={
          <>
            <Button variant="ghost" onClick={() => setPayTarget(null)}>Cancelar</Button>
            <Button type="submit" form="pay-form" variant="success" loading={busy === 'pay'}>Confirmar pago</Button>
          </>
        }
      >
        <form id="pay-form" className="form-grid" onSubmit={pay}>
          <Select label="Método de pago" value={paymentForm.method} onChange={(event) => setPaymentForm({ ...paymentForm, method: event.target.value })}>
            <option value="cash">Efectivo</option>
            <option value="transfer">Transferencia</option>
            <option value="card">Tarjeta</option>
            <option value="nequi">Nequi</option>
            <option value="daviplata">Daviplata</option>
            <option value="other">Otro</option>
          </Select>
          <Input label="Referencia (opcional)" value={paymentForm.reference} onChange={(event) => setPaymentForm({ ...paymentForm, reference: event.target.value })} />
        </form>
      </Modal>
    </div>
  );
}
