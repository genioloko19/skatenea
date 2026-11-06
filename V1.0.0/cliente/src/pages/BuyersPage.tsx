import { useEffect, useState, type FormEvent } from 'react';
import { Plus, Search, Users } from 'lucide-react';
import { api, ApiError } from '../api/client';
import type { Buyer } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/ui/Toast';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Card, CardBody, CardHeader } from '../components/ui/Card';
import { EmptyState, PageHeader, PageLoader } from '../components/ui/Feedback';
import { Input, Select } from '../components/ui/Field';
import { Modal } from '../components/ui/Modal';

export function BuyersPage() {
  const { hasPermission } = useAuth();
  const { toast } = useToast();
  const [term, setTerm] = useState('');
  const [buyers, setBuyers] = useState<Buyer[]>([]);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [createOpen, setCreateOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({ full_name: '', email: '', phone: '', document_type: 'CC', document_number: '' });

  const search = async (event?: FormEvent) => {
    event?.preventDefault();
    if (term.trim().length < 2) {
      setBuyers([]);
      setSearched(false);
      return;
    }
    setLoading(true);
    try {
      const payload = await api.searchBuyers(term.trim());
      setBuyers(payload.buyers);
      setSearched(true);
    } catch (error) {
      toast('error', 'No se pudo buscar', error instanceof ApiError ? error.message : undefined);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (term.trim().length >= 2) {
      const timeout = window.setTimeout(() => void search(), 350);
      return () => window.clearTimeout(timeout);
    }
    setBuyers([]);
    setSearched(false);
    return undefined;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [term]);

  const create = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    try {
      await api.createBuyer({
        full_name: form.full_name,
        phone: form.phone,
        email: form.email || undefined,
        document_type: form.document_type,
        document_number: form.document_number || undefined,
      });
      toast('success', 'Comprador registrado');
      setCreateOpen(false);
      setForm({ full_name: '', email: '', phone: '', document_type: 'CC', document_number: '' });
      if (term.trim().length >= 2) await search();
    } catch (error) {
      toast('error', 'No se pudo registrar', error instanceof ApiError ? error.message : undefined);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="page-stack">
      <PageHeader
        eyebrow="Relaciones"
        title="Compradores"
        subtitle="Busca por nombre, teléfono, correo o documento. Solo ves los compradores que te corresponden."
        actions={hasPermission('buyers.create') ? <Button icon={<Plus size={17} />} onClick={() => setCreateOpen(true)}>Nuevo comprador</Button> : undefined}
      />

      <Card>
        <CardBody>
          <form className="search-form" onSubmit={search} role="search">
            <span className="search-form__icon"><Search size={18} /></span>
            <input
              value={term}
              onChange={(event) => setTerm(event.target.value)}
              placeholder="Buscar comprador…"
              aria-label="Buscar comprador"
              autoFocus
            />
            <Button type="submit" variant="secondary">Buscar</Button>
          </form>
        </CardBody>
      </Card>

      {loading ? <PageLoader label="Buscando compradores…" /> : !searched ? (
        <EmptyState icon={<Users size={26} />} title="Busca un comprador" description="Escribe al menos 2 caracteres para comenzar." />
      ) : buyers.length === 0 ? (
        <EmptyState
          icon={<Users size={26} />}
          title="No encontramos coincidencias"
          description="Prueba con otro nombre, teléfono o documento."
          action={hasPermission('buyers.create') ? <Button icon={<Plus size={17} />} onClick={() => setCreateOpen(true)}>Registrar comprador</Button> : undefined}
        />
      ) : (
        <Card>
          <CardHeader icon={<Users size={20} />} title="Resultados" subtitle={`${buyers.length} compradores`} actions={<Badge tone="neutral">{buyers.length}</Badge>} />
          <CardBody>
            <div className="table-wrap">
              <table className="data-table">
                <thead><tr><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Documento</th><th>Registrado</th></tr></thead>
                <tbody>
                  {buyers.map((buyer) => (
                    <tr key={buyer.id}>
                      <td><strong>{buyer.full_name}</strong></td>
                      <td>{buyer.phone}</td>
                      <td>{buyer.email ?? '—'}</td>
                      <td>{buyer.document_type ?? '—'} {buyer.document_number ?? ''}</td>
                      <td>{new Date(buyer.created_at.replace(' ', 'T') + 'Z').toLocaleDateString('es-CO')}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardBody>
        </Card>
      )}

      <Modal
        open={createOpen}
        onClose={() => setCreateOpen(false)}
        title="Nuevo comprador"
        subtitle="Los datos se usan para asociar boletas y facilitar la trazabilidad."
        footer={
          <>
            <Button variant="ghost" onClick={() => setCreateOpen(false)}>Cancelar</Button>
            <Button type="submit" form="buyer-form" loading={saving}>Registrar comprador</Button>
          </>
        }
      >
        <form id="buyer-form" className="form-grid" onSubmit={create}>
          <Input label="Nombre completo" value={form.full_name} onChange={(event) => setForm({ ...form, full_name: event.target.value })} required />
          <Input label="Teléfono" value={form.phone} onChange={(event) => setForm({ ...form, phone: event.target.value })} required />
          <Input label="Correo" type="email" value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} />
          <Select label="Tipo de documento" value={form.document_type} onChange={(event) => setForm({ ...form, document_type: event.target.value })}>
            <option value="CC">Cédula de ciudadanía</option>
            <option value="CE">Cédula de extranjería</option>
            <option value="PA">Pasaporte</option>
            <option value="NIT">NIT</option>
          </Select>
          <Input label="Número de documento" value={form.document_number} onChange={(event) => setForm({ ...form, document_number: event.target.value })} />
        </form>
      </Modal>
    </div>
  );
}
