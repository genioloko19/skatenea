import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, CalendarDays, Plus, Search, Ticket, Trophy } from 'lucide-react';
import { api, ApiError } from '../api/client';
import type { Raffle, RaffleStatus } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/ui/Toast';
import { Badge, RaffleStatusBadge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Card, CardBody } from '../components/ui/Card';
import { EmptyState, PageHeader, PageLoader } from '../components/ui/Feedback';
import { Input, Textarea } from '../components/ui/Field';
import { Modal } from '../components/ui/Modal';
import { currency, formatDate, percent } from '../lib/format';

const FILTERS: { value: RaffleStatus | 'all'; label: string }[] = [
  { value: 'all', label: 'Todas' },
  { value: 'active', label: 'Activas' },
  { value: 'pending_approval', label: 'Pendientes' },
  { value: 'draft', label: 'Borradores' },
  { value: 'finished', label: 'Finalizadas' },
];

function toSqlDate(value: string): string {
  return `${value.replace('T', ' ')}:00`;
}

function defaultStart(): string {
  const date = new Date();
  date.setDate(date.getDate() + 1);
  return date.toISOString().slice(0, 16);
}

function defaultEnd(): string {
  const date = new Date();
  date.setDate(date.getDate() + 31);
  return date.toISOString().slice(0, 16);
}

export function RafflesPage() {
  const { hasPermission } = useAuth();
  const { toast } = useToast();
  const [raffles, setRaffles] = useState<Raffle[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState<RaffleStatus | 'all'>('all');
  const [createOpen, setCreateOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({
    name: '',
    description: '',
    prize: '',
    ticket_price: 10000,
    total_tickets: 100,
    start_at: defaultStart(),
    end_at: defaultEnd(),
    code: '',
  });

  const load = () => {
    setLoading(true);
    api.raffles({ status: status === 'all' ? undefined : status, search: search || undefined, limit: 100 })
      .then((payload) => setRaffles(payload.raffles))
      .catch((error) => toast('error', 'No se pudieron cargar las rifas', error instanceof ApiError ? error.message : undefined))
      .finally(() => setLoading(false));
  };

  useEffect(load, [status]);

  const filtered = useMemo(
    () => raffles.filter((raffle) => raffle.name.toLowerCase().includes(search.toLowerCase()) || raffle.code.toLowerCase().includes(search.toLowerCase())),
    [raffles, search],
  );

  const create = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    try {
      await api.createRaffle({
        name: form.name,
        description: form.description || undefined,
        prize: form.prize,
        ticket_price: Number(form.ticket_price),
        total_tickets: Number(form.total_tickets),
        start_at: toSqlDate(form.start_at),
        end_at: toSqlDate(form.end_at),
        code: form.code || undefined,
      });
      toast('success', 'Rifa creada', 'Puedes enviarla a aprobación desde el detalle.');
      setCreateOpen(false);
      setForm({ name: '', description: '', prize: '', ticket_price: 10000, total_tickets: 100, start_at: defaultStart(), end_at: defaultEnd(), code: '' });
      load();
    } catch (error) {
      toast('error', 'No se pudo crear la rifa', error instanceof ApiError ? error.message : undefined);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="page-stack">
      <PageHeader
        eyebrow="Inventario"
        title="Rifas"
        subtitle="Crea, aprueba y opera rifas con un flujo claro y auditable."
        actions={hasPermission('raffles.create') ? <Button icon={<Plus size={17} />} onClick={() => setCreateOpen(true)}>Nueva rifa</Button> : undefined}
      />

      <Card>
        <CardBody>
          <div className="toolbar">
            <div className="toolbar__search">
              <Search size={17} />
              <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Buscar por nombre o código…" aria-label="Buscar rifas" />
            </div>
            <div className="chip-row" role="group" aria-label="Filtrar por estado">
              {FILTERS.map((filter) => (
                <button
                  key={filter.value}
                  type="button"
                  className={filter.value === status ? 'chip is-active' : 'chip'}
                  onClick={() => setStatus(filter.value)}
                >
                  {filter.label}
                </button>
              ))}
            </div>
          </div>
        </CardBody>
      </Card>

      {loading ? <PageLoader label="Cargando rifas…" /> : filtered.length === 0 ? (
        <EmptyState
          icon={<Ticket size={26} />}
          title="No hay rifas en esta vista"
          description={hasPermission('raffles.create') ? 'Crea una nueva rifa para empezar.' : 'Cuando haya rifas disponibles aparecerán aquí.'}
          action={hasPermission('raffles.create') ? <Button icon={<Plus size={17} />} onClick={() => setCreateOpen(true)}>Crear rifa</Button> : undefined}
        />
      ) : (
        <div className="raffle-grid">
          {filtered.map((raffle) => (
            <Card key={raffle.id} className="raffle-card">
              <div className="raffle-card__top">
                <Badge tone="neutral">{raffle.code}</Badge>
                <RaffleStatusBadge status={raffle.status} />
              </div>
              <h3>{raffle.name}</h3>
              <p className="muted-text">{raffle.description || 'Sin descripción adicional.'}</p>
              <div className="raffle-card__prize"><Trophy size={17} /> {raffle.prize}</div>
              <div className="progress"><span style={{ width: `${percent(raffle.stats.paid, raffle.total_tickets)}%` }} /></div>
              <div className="raffle-card__stats">
                <div><strong>{raffle.stats.paid}</strong><span>pagadas</span></div>
                <div><strong>{raffle.stats.reserved}</strong><span>separadas</span></div>
                <div><strong>{raffle.stats.available}</strong><span>disponibles</span></div>
              </div>
              <div className="raffle-card__footer">
                <div><strong>{currency(raffle.ticket_price)}</strong><small>por boleta</small></div>
                <div className="raffle-card__date"><CalendarDays size={15} /><small>Cierra {formatDate(raffle.end_at)}</small></div>
                <Link to={`/app/raffles/${raffle.id}`} className="btn btn--secondary btn--sm">Abrir <ArrowRight size={15} /></Link>
              </div>
            </Card>
          ))}
        </div>
      )}

      <Modal
        open={createOpen}
        onClose={() => setCreateOpen(false)}
        title="Nueva rifa"
        subtitle="La rifa nace en borrador y requiere aprobación del super administrador."
        size="lg"
        footer={
          <>
            <Button variant="ghost" onClick={() => setCreateOpen(false)}>Cancelar</Button>
            <Button type="submit" form="create-raffle-form" loading={saving}>Crear rifa</Button>
          </>
        }
      >
        <form id="create-raffle-form" className="form-grid" onSubmit={create}>
          <Input label="Nombre" value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required />
          <Input label="Código (opcional)" value={form.code} onChange={(event) => setForm({ ...form, code: event.target.value })} placeholder="SK-ABC123" />
          <Input label="Premio" value={form.prize} onChange={(event) => setForm({ ...form, prize: event.target.value })} required />
          <Input label="Valor de la boleta" type="number" min={0} value={form.ticket_price} onChange={(event) => setForm({ ...form, ticket_price: Number(event.target.value) })} required />
          <Input label="Total de boletas" type="number" min={1} max={1000000} value={form.total_tickets} onChange={(event) => setForm({ ...form, total_tickets: Number(event.target.value) })} required />
          <Input label="Inicio" type="datetime-local" value={form.start_at} onChange={(event) => setForm({ ...form, start_at: event.target.value })} required />
          <Input label="Fin" type="datetime-local" value={form.end_at} onChange={(event) => setForm({ ...form, end_at: event.target.value })} required />
          <div className="form-grid__full">
            <Textarea label="Descripción" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} rows={3} />
          </div>
        </form>
      </Modal>
    </div>
  );
}
