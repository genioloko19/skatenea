import { useEffect, useState, type FormEvent } from 'react';
import { Plus, ShieldCheck, Users } from 'lucide-react';
import { api, ApiError } from '../api/client';
import type { Role, User } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/ui/Toast';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Card, CardBody, CardHeader } from '../components/ui/Card';
import { EmptyState, PageHeader, PageLoader } from '../components/ui/Feedback';
import { Input, Select } from '../components/ui/Field';
import { Modal } from '../components/ui/Modal';
import { roleLabel } from '../lib/format';

const ROLES: Role[] = ['super_admin', 'leader', 'seller', 'buyer'];

export function UsersPage() {
  const { user } = useAuth();
  const { toast } = useToast();
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [roleFilter, setRoleFilter] = useState<Role | 'all'>('all');
  const [createOpen, setCreateOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({ full_name: '', email: '', phone: '', role: 'seller', password: '' });

  const load = () => {
    setLoading(true);
    api.users({ limit: 500 })
      .then((payload) => setUsers(payload.users))
      .catch((error) => toast('error', 'No se pudieron cargar los usuarios', error instanceof ApiError ? error.message : undefined))
      .finally(() => setLoading(false));
  };

  useEffect(load, []);

  const filtered = users.filter((item) => roleFilter === 'all' || item.role === roleFilter);

  const create = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    try {
      await api.createUser({
        full_name: form.full_name,
        email: form.email,
        phone: form.phone || undefined,
        role: form.role,
        password: form.password,
      });
      toast('success', 'Usuario creado', 'Deberá cambiar la contraseña al ingresar.');
      setCreateOpen(false);
      setForm({ full_name: '', email: '', phone: '', role: 'seller', password: '' });
      load();
    } catch (error) {
      toast('error', 'No se pudo crear el usuario', error instanceof ApiError ? error.message : undefined);
    } finally {
      setSaving(false);
    }
  };

  const deactivate = async (target: User) => {
    if (target.id === user?.id) {
      toast('warning', 'No puedes desactivar tu propia cuenta');
      return;
    }
    if (!window.confirm(`¿Desactivar a ${target.full_name}?`)) return;
    try {
      await api.deactivateUser(target.id);
      toast('success', 'Usuario desactivado');
      load();
    } catch (error) {
      toast('error', 'No se pudo desactivar', error instanceof ApiError ? error.message : undefined);
    }
  };

  return (
    <div className="page-stack">
      <PageHeader
        eyebrow="Gobierno"
        title="Usuarios"
        subtitle="Gestiona super administradores, líderes, vendedores y compradores."
        actions={<Button icon={<Plus size={17} />} onClick={() => setCreateOpen(true)}>Nuevo usuario</Button>}
      />

      <Card>
        <CardBody>
          <div className="toolbar">
            <div className="chip-row" role="group" aria-label="Filtrar por rol">
              <button type="button" className={roleFilter === 'all' ? 'chip is-active' : 'chip'} onClick={() => setRoleFilter('all')}>Todos ({users.length})</button>
              {ROLES.map((role) => (
                <button key={role} type="button" className={roleFilter === role ? 'chip is-active' : 'chip'} onClick={() => setRoleFilter(role)}>
                  {roleLabel(role)} ({users.filter((item) => item.role === role).length})
                </button>
              ))}
            </div>
          </div>
        </CardBody>
      </Card>

      {loading ? <PageLoader label="Cargando usuarios…" /> : filtered.length === 0 ? (
        <EmptyState icon={<Users size={26} />} title="No hay usuarios en este filtro" />
      ) : (
        <Card>
          <CardHeader icon={<ShieldCheck size={20} />} title="Directorio" subtitle={`${filtered.length} usuarios`} actions={<Badge tone="primary">{filtered.length}</Badge>} />
          <CardBody>
            <div className="table-wrap">
              <table className="data-table">
                <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Último ingreso</th><th /></tr></thead>
                <tbody>
                  {filtered.map((item) => (
                    <tr key={item.id}>
                      <td><strong>{item.full_name}</strong>{item.id === user?.id && <Badge tone="info">Tú</Badge>}</td>
                      <td>{item.email}</td>
                      <td><Badge tone="neutral">{roleLabel(item.role)}</Badge></td>
                      <td><Badge tone={item.status === 'active' ? 'success' : 'danger'}>{item.status}</Badge></td>
                      <td>{item.last_login_at ? new Date(item.last_login_at.replace(' ', 'T') + 'Z').toLocaleString('es-CO') : '—'}</td>
                      <td>
                        {item.status === 'active' && (
                          <Button size="sm" variant="ghost" onClick={() => deactivate(item)}>Desactivar</Button>
                        )}
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
        open={createOpen}
        onClose={() => setCreateOpen(false)}
        title="Nuevo usuario"
        subtitle="La contraseña temporal debe cumplir la política de seguridad."
        footer={
          <>
            <Button variant="ghost" onClick={() => setCreateOpen(false)}>Cancelar</Button>
            <Button type="submit" form="user-form" loading={saving}>Crear usuario</Button>
          </>
        }
      >
        <form id="user-form" className="form-grid" onSubmit={create}>
          <Input label="Nombre completo" value={form.full_name} onChange={(event) => setForm({ ...form, full_name: event.target.value })} required />
          <Input label="Correo" type="email" value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} required />
          <Input label="Teléfono" value={form.phone} onChange={(event) => setForm({ ...form, phone: event.target.value })} />
          <Select label="Rol" value={form.role} onChange={(event) => setForm({ ...form, role: event.target.value })}>
            {ROLES.map((role) => <option key={role} value={role}>{roleLabel(role)}</option>)}
          </Select>
          <div className="form-grid__full">
            <Input label="Contraseña temporal" type="password" value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })} required hint="Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo." />
          </div>
        </form>
      </Modal>
    </div>
  );
}
