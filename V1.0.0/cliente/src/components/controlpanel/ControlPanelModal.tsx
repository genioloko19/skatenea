import { useEffect, useMemo, useState } from 'react';
import {
  Bell,
  Check,
  Copy,
  KeyRound,
  Monitor,
  Palette,
  Plug,
  RefreshCw,
  ScrollText,
  ShieldCheck,
  SlidersHorizontal,
  Ticket,
  UserRound,
  Users,
  Wallet,
} from 'lucide-react';
import { api, ApiError } from '../../api/client';
import type {
  ApiKey,
  AuditEntry,
  NotificationEntry,
  Preferences,
  Raffle,
  SettingsPayload,
  Ticket as TicketType,
  User,
  Webhook,
} from '../../api/types';
import { useAuth } from '../../auth/AuthContext';
import { useTheme } from '../../theme/ThemeProvider';
import { BACKGROUNDS, GEOMETRIES, MODES, THEMES } from '../../theme/themes';
import { cn } from '../../lib/cn';
import { currency, formatDateTime, roleLabel } from '../../lib/format';
import { Badge, RaffleStatusBadge, TicketStatusBadge } from '../ui/Badge';
import { Button } from '../ui/Button';
import { Card, CardBody, CardHeader } from '../ui/Card';
import { Input, Select } from '../ui/Field';
import { Modal } from '../ui/Modal';
import { Segmented } from '../ui/Segmented';
import { Tabs, type TabItem } from '../ui/Tabs';
import { useToast } from '../ui/Toast';

interface ControlPanelModalProps {
  open: boolean;
  onClose: () => void;
}

const SCOPES = [
  'raffles.read',
  'tickets.read',
  'buyers.read',
  'dashboard.read',
  'audit.read',
  'settings.read',
];

export function ControlPanelModal({ open, onClose }: ControlPanelModalProps) {
  const { user, permissions, hasPermission, refresh } = useAuth();
  const { toast } = useToast();
  const [active, setActive] = useState('appearance');

  const tabs = useMemo<TabItem[]>(
    () =>
      [
        { id: 'appearance', label: 'Apariencia', icon: <Palette size={17} /> },
        { id: 'profile', label: 'Perfil', icon: <UserRound size={17} />, permission: 'profile.update' },
        { id: 'general', label: 'General', icon: <SlidersHorizontal size={17} />, permission: 'settings.read' },
        { id: 'users', label: 'Usuarios', icon: <Users size={17} />, permission: 'users.read' },
        { id: 'raffles', label: 'Rifas', icon: <Ticket size={17} />, permission: 'raffles.read' },
        { id: 'tickets', label: 'Boletas', icon: <Wallet size={17} />, permission: 'tickets.read' },
        { id: 'security', label: 'Seguridad', icon: <ShieldCheck size={17} /> },
        { id: 'api', label: 'API', icon: <KeyRound size={17} />, permission: 'api_keys.read' },
        { id: 'audit', label: 'Auditoría', icon: <ScrollText size={17} />, permission: 'audit.read' },
        { id: 'integrations', label: 'Integraciones', icon: <Plug size={17} />, permission: 'webhooks.read' },
        { id: 'notifications', label: 'Notificaciones', icon: <Bell size={17} /> },
        { id: 'system', label: 'Sistema', icon: <Monitor size={17} /> },
      ].filter((tab) => !('permission' in tab) || !tab.permission || hasPermission(tab.permission as string)),
    [hasPermission],
  );

  useEffect(() => {
    if (!tabs.some((tab) => tab.id === active)) {
      setActive(tabs[0]?.id ?? 'appearance');
    }
  }, [tabs, active]);

  if (!user) return null;

  return (
    <Modal
      open={open}
      onClose={onClose}
      size="xl"
      title="Panel de control"
      subtitle="Apariencia, seguridad, operación y administración en un solo lugar."
      className="control-panel"
    >
      <div className="control-panel__layout">
        <Tabs
          tabs={tabs}
          active={active}
          onChange={setActive}
          orientation="vertical"
          ariaLabel="Secciones del panel de control"
          panelId="control-panel"
        />
        <div
          className="control-panel__content"
          role="tabpanel"
          id={`control-panel-${active}`}
          aria-labelledby={`tab-${active}`}
        >
          {active === 'appearance' && <AppearancePanel onSaved={() => toast('success', 'Apariencia guardada')} />}
          {active === 'profile' && <ProfilePanel onSaved={refresh} />}
          {active === 'general' && (
            <GeneralPanel canEdit={hasPermission('settings.update')} onSaved={() => toast('success', 'Configuración guardada')} />
          )}
          {active === 'users' && <UsersPanel canEdit={hasPermission('users.update')} />}
          {active === 'raffles' && <RafflesPanel />}
          {active === 'tickets' && <TicketsPanel />}
          {active === 'security' && <SecurityPanel />}
          {active === 'api' && <ApiPanel />}
          {active === 'audit' && <AuditPanel />}
          {active === 'integrations' && <IntegrationsPanel />}
          {active === 'notifications' && <NotificationsPanel />}
          {active === 'system' && <SystemPanel />}
        </div>
      </div>
      <div className="control-panel__footnote">
        <Check size={15} />
        <span>
          {permissions.length} permisos activos · {roleLabel(user.role)} · Los cambios de apariencia se guardan por usuario.
        </span>
      </div>
    </Modal>
  );
}

function AppearancePanel({ onSaved }: { onSaved: () => void }) {
  const theme = useTheme();
  const { savePreferences } = useAuth();
  const [saving, setSaving] = useState(false);
  const [draft, setDraft] = useState<Preferences>({
    user_id: '',
    theme: theme.theme,
    mode: theme.mode,
    geometry: theme.geometry,
    background: theme.background,
    sidebar_collapsed: 0,
  });

  useEffect(() => {
    setDraft((current) => ({
      ...current,
      theme: theme.theme,
      mode: theme.mode,
      geometry: theme.geometry,
      background: theme.background,
    }));
  }, [theme.theme, theme.mode, theme.geometry, theme.background]);

  const save = async () => {
    setSaving(true);
    try {
      await savePreferences({
        theme: draft.theme,
        mode: draft.mode,
        geometry: draft.geometry,
        background: draft.background,
      });
      theme.applyPreferences(draft);
      onSaved();
    } catch (error) {
      // El toast global no está disponible aquí; la promesa se propaga al manejador.
      console.error(error);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="panel-stack">
      <Card>
        <CardHeader
          icon={<Palette size={20} />}
          title="Temas"
          subtitle="12 identidades visuales. Cada tema conserva la misma estructura y accesibilidad."
        />
        <CardBody>
          <div className="theme-grid" role="radiogroup" aria-label="Seleccionar tema">
            {THEMES.map((item) => (
              <button
                key={item.id}
                type="button"
                role="radio"
                aria-checked={draft.theme === item.id}
                className={cn('theme-card', draft.theme === item.id && 'is-selected')}
                onClick={() => {
                  setDraft((current) => ({ ...current, theme: item.id }));
                  theme.setTheme(item.id);
                }}
              >
                <span className="theme-card__swatches" aria-hidden="true">
                  {item.swatches.map((color) => (
                    <span key={color} style={{ background: color }} />
                  ))}
                </span>
                <span className="theme-card__meta">
                  <strong>{item.name}</strong>
                  <small>{item.description}</small>
                </span>
                {draft.theme === item.id && <Check size={17} className="theme-card__check" />}
              </button>
            ))}
          </div>
        </CardBody>
      </Card>

      <div className="panel-grid">
        <Card>
          <CardHeader icon={<SlidersHorizontal size={20} />} title="Modo" subtitle="Claro, oscuro y alto contraste." />
          <CardBody>
            <Segmented
              options={MODES.map((mode) => ({ value: mode.id, label: mode.name }))}
              value={draft.mode}
              onChange={(mode) => {
                setDraft((current) => ({ ...current, mode }));
                theme.setMode(mode);
              }}
              label="Modo de color"
            />
            <p className="muted-text">
              {MODES.find((mode) => mode.id === draft.mode)?.description}
            </p>
          </CardBody>
        </Card>

        <Card>
          <CardHeader icon={<Palette size={20} />} title="Geometría" subtitle="Define la personalidad de las formas." />
          <CardBody>
            <Segmented
              options={GEOMETRIES.map((geometry) => ({ value: geometry.id, label: geometry.name }))}
              value={draft.geometry}
              onChange={(geometry) => {
                setDraft((current) => ({ ...current, geometry }));
                theme.setGeometry(geometry);
              }}
              label="Geometría de la interfaz"
            />
            <p className="muted-text">
              {GEOMETRIES.find((geometry) => geometry.id === draft.geometry)?.description}
            </p>
          </CardBody>
        </Card>
      </div>

      <Card>
        <CardHeader icon={<Monitor size={20} />} title="Fondo y movimiento" subtitle="Fondos dinámicos que no distraen." />
        <CardBody>
          <Segmented
            options={BACKGROUNDS.map((background) => ({ value: background.id, label: background.name }))}
            value={draft.background}
            onChange={(background) => {
              setDraft((current) => ({ ...current, background }));
              theme.setBackground(background);
            }}
            label="Fondo dinámico"
          />
          <div className="switch-row">
            <div>
              <strong>Animaciones</strong>
              <p className="muted-text">Desactívalas si prefieres una interfaz completamente estática.</p>
            </div>
            <button
              type="button"
              role="switch"
              aria-checked={theme.motion}
              className={cn('switch', theme.motion && 'is-on')}
              onClick={() => theme.setMotion(!theme.motion)}
            >
              <span />
            </button>
          </div>
        </CardBody>
      </Card>

      <div className="panel-actions">
        <Button variant="secondary" onClick={() => theme.reset()}>Restablecer</Button>
        <Button onClick={save} loading={saving}>Guardar apariencia</Button>
      </div>
    </div>
  );
}

function ProfilePanel({ onSaved }: { onSaved: () => void }) {
  const { user } = useAuth();
  const { toast } = useToast();
  const [fullName, setFullName] = useState(user?.full_name ?? '');
  const [phone, setPhone] = useState(user?.phone ?? '');
  const [documentType, setDocumentType] = useState(user?.document_type ?? 'CC');
  const [documentNumber, setDocumentNumber] = useState(user?.document_number ?? '');
  const [saving, setSaving] = useState(false);
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [passwordSaving, setPasswordSaving] = useState(false);

  const save = async (event: React.FormEvent) => {
    event.preventDefault();
    setSaving(true);
    try {
      await api.updateProfile({
        full_name: fullName,
        phone,
        document_type: documentType,
        document_number: documentNumber,
      });
      toast('success', 'Perfil actualizado');
      onSaved();
    } catch (error) {
      toast('error', 'No se pudo actualizar', error instanceof ApiError ? error.message : undefined);
    } finally {
      setSaving(false);
    }
  };

  const changePassword = async (event: React.FormEvent) => {
    event.preventDefault();
    setPasswordSaving(true);
    try {
      await api.changePassword(currentPassword, newPassword);
      setCurrentPassword('');
      setNewPassword('');
      toast('success', 'Contraseña actualizada', 'Las demás sesiones fueron revocadas.');
    } catch (error) {
      toast('error', 'No se pudo cambiar la contraseña', error instanceof ApiError ? error.message : undefined);
    } finally {
      setPasswordSaving(false);
    }
  };

  return (
    <div className="panel-stack">
      <Card>
        <CardHeader icon={<UserRound size={20} />} title="Datos del perfil" subtitle="Tu identidad dentro de SKATENEA." />
        <CardBody>
          <form className="form-grid" onSubmit={save}>
            <Input label="Nombre completo" value={fullName} onChange={(event) => setFullName(event.target.value)} required />
            <Input label="Teléfono" value={phone} onChange={(event) => setPhone(event.target.value)} />
            <Select label="Tipo de documento" value={documentType} onChange={(event) => setDocumentType(event.target.value)}>
              <option value="CC">Cédula de ciudadanía</option>
              <option value="CE">Cédula de extranjería</option>
              <option value="PA">Pasaporte</option>
              <option value="NIT">NIT</option>
            </Select>
            <Input label="Número de documento" value={documentNumber} onChange={(event) => setDocumentNumber(event.target.value)} />
            <div className="form-grid__full panel-actions">
              <Button type="submit" loading={saving}>Guardar perfil</Button>
            </div>
          </form>
        </CardBody>
      </Card>

      <Card>
        <CardHeader icon={<ShieldCheck size={20} />} title="Contraseña" subtitle="Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo." />
        <CardBody>
          <form className="form-grid" onSubmit={changePassword}>
            <Input
              label="Contraseña actual"
              type="password"
              value={currentPassword}
              onChange={(event) => setCurrentPassword(event.target.value)}
              required
            />
            <Input
              label="Nueva contraseña"
              type="password"
              value={newPassword}
              onChange={(event) => setNewPassword(event.target.value)}
              required
            />
            <div className="form-grid__full panel-actions">
              <Button type="submit" variant="secondary" loading={passwordSaving}>Cambiar contraseña</Button>
            </div>
          </form>
        </CardBody>
      </Card>
    </div>
  );
}

function GeneralPanel({ canEdit, onSaved }: { canEdit: boolean; onSaved: () => void }) {
  const { toast } = useToast();
  const [settings, setSettings] = useState<Record<string, unknown>>({});
  const [appName, setAppName] = useState('SKATENEA');
  const [maintenance, setMaintenance] = useState(false);
  const [registrationOpen, setRegistrationOpen] = useState(true);
  const [reservationHours, setReservationHours] = useState(48);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    api.settings().then((payload: SettingsPayload) => {
      setSettings(payload.settings);
      setAppName(String(payload.settings['app.name'] ?? 'SKATENEA'));
      setMaintenance(Boolean(payload.settings['app.maintenance'] ?? false));
      setRegistrationOpen(Boolean(payload.settings['security.registration_open'] ?? true));
      setReservationHours(Number(payload.settings['raffle.reservation_hours'] ?? 48));
    }).catch(() => undefined);
  }, []);

  const save = async () => {
    setSaving(true);
    try {
      const payload = await api.updateSettings({
        'app.name': appName,
        'app.maintenance': maintenance,
        'security.registration_open': registrationOpen,
        'raffle.reservation_hours': reservationHours,
      });
      setSettings(payload.settings);
      toast('success', 'Configuración general guardada');
      onSaved();
    } catch (error) {
      toast('error', 'No se pudo guardar', error instanceof ApiError ? error.message : undefined);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="panel-stack">
      <Card>
        <CardHeader icon={<SlidersHorizontal size={20} />} title="Configuración general" subtitle="Parámetros base de la plataforma." />
        <CardBody>
          <div className="form-grid">
            <Input label="Nombre de la aplicación" value={appName} onChange={(event) => setAppName(event.target.value)} disabled={!canEdit} />
            <Input
              label="Horas de reserva"
              type="number"
              min={1}
              max={168}
              value={reservationHours}
              onChange={(event) => setReservationHours(Number(event.target.value))}
              disabled={!canEdit}
            />
          </div>
          <div className="switch-row">
            <div>
              <strong>Modo mantenimiento</strong>
              <p className="muted-text">Muestra un aviso y bloquea nuevas operaciones.</p>
            </div>
            <button type="button" role="switch" aria-checked={maintenance} className={cn('switch', maintenance && 'is-on')} onClick={() => canEdit && setMaintenance(!maintenance)} disabled={!canEdit}>
              <span />
            </button>
          </div>
          <div className="switch-row">
            <div>
              <strong>Registro de compradores abierto</strong>
              <p className="muted-text">Permite que nuevos compradores creen su cuenta.</p>
            </div>
            <button type="button" role="switch" aria-checked={registrationOpen} className={cn('switch', registrationOpen && 'is-on')} onClick={() => canEdit && setRegistrationOpen(!registrationOpen)} disabled={!canEdit}>
              <span />
            </button>
          </div>
          {Object.keys(settings).length > 0 && (
            <details className="settings-detail">
              <summary>Ver configuración completa</summary>
              <pre>{JSON.stringify(settings, null, 2)}</pre>
            </details>
          )}
          {canEdit && (
            <div className="panel-actions">
              <Button onClick={save} loading={saving}>Guardar configuración</Button>
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  );
}

function UsersPanel({ canEdit }: { canEdit: boolean }) {
  const { toast } = useToast();
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState({ full_name: '', email: '', password: '', role: 'seller', phone: '' });
  const [creating, setCreating] = useState(false);

  const load = () => {
    setLoading(true);
    api.users({ limit: 200 })
      .then((payload) => setUsers(payload.users))
      .catch(() => toast('error', 'No se pudieron cargar los usuarios'))
      .finally(() => setLoading(false));
  };

  useEffect(load, []);

  const create = async (event: React.FormEvent) => {
    event.preventDefault();
    setCreating(true);
    try {
      await api.createUser(form);
      setForm({ full_name: '', email: '', password: '', role: 'seller', phone: '' });
      toast('success', 'Usuario creado');
      load();
    } catch (error) {
      toast('error', 'No se pudo crear', error instanceof ApiError ? error.message : undefined);
    } finally {
      setCreating(false);
    }
  };

  const deactivate = async (id: string) => {
    try {
      await api.deactivateUser(id);
      toast('success', 'Usuario desactivado');
      load();
    } catch (error) {
      toast('error', 'No se pudo desactivar', error instanceof ApiError ? error.message : undefined);
    }
  };

  return (
    <div className="panel-stack">
      <Card>
        <CardHeader icon={<Users size={20} />} title="Usuarios" subtitle="Super administradores, líderes, vendedores y compradores." actions={<Badge tone="primary">{users.length}</Badge>} />
        <CardBody>
          {loading ? <div className="muted-text">Cargando usuarios…</div> : (
            <div className="table-wrap">
              <table className="data-table">
                <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th /></tr></thead>
                <tbody>
                  {users.map((item) => (
                    <tr key={item.id}>
                      <td>{item.full_name}</td>
                      <td>{item.email}</td>
                      <td><Badge tone="neutral">{roleLabel(item.role)}</Badge></td>
                      <td><Badge tone={item.status === 'active' ? 'success' : 'danger'}>{item.status}</Badge></td>
                      <td>
                        {canEdit && item.status === 'active' && (
                          <Button size="sm" variant="ghost" onClick={() => deactivate(item.id)}>Desactivar</Button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardBody>
      </Card>

      {canEdit && (
        <Card>
          <CardHeader icon={<UserRound size={20} />} title="Crear usuario" subtitle="La contraseña debe cumplir la política de seguridad." />
          <CardBody>
            <form className="form-grid" onSubmit={create}>
              <Input label="Nombre completo" value={form.full_name} onChange={(event) => setForm({ ...form, full_name: event.target.value })} required />
              <Input label="Correo" type="email" value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} required />
              <Input label="Teléfono" value={form.phone} onChange={(event) => setForm({ ...form, phone: event.target.value })} />
              <Select label="Rol" value={form.role} onChange={(event) => setForm({ ...form, role: event.target.value })}>
                <option value="super_admin">Super administrador</option>
                <option value="leader">Líder</option>
                <option value="seller">Vendedor</option>
                <option value="buyer">Comprador</option>
              </Select>
              <Input label="Contraseña temporal" type="password" value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })} required />
              <div className="form-grid__full panel-actions">
                <Button type="submit" loading={creating}>Crear usuario</Button>
              </div>
            </form>
          </CardBody>
        </Card>
      )}
    </div>
  );
}

function RafflesPanel() {
  const [raffles, setRaffles] = useState<Raffle[]>([]);
  useEffect(() => {
    api.raffles({ limit: 20 }).then((payload) => setRaffles(payload.raffles)).catch(() => undefined);
  }, []);

  return (
    <Card>
      <CardHeader icon={<Ticket size={20} />} title="Rifas recientes" subtitle="Accede al detalle desde el módulo de rifas." />
      <CardBody>
        <div className="list-stack">
          {raffles.map((raffle) => (
            <div key={raffle.id} className="list-row">
              <div>
                <strong>{raffle.name}</strong>
                <small>{raffle.code} · {currency(raffle.ticket_price)}</small>
              </div>
              <RaffleStatusBadge status={raffle.status} />
            </div>
          ))}
          {raffles.length === 0 && <p className="muted-text">Aún no hay rifas.</p>}
        </div>
      </CardBody>
    </Card>
  );
}

function TicketsPanel() {
  const [tickets, setTickets] = useState<TicketType[]>([]);
  useEffect(() => {
    api.myTickets().then((payload) => setTickets(payload.tickets)).catch(() => undefined);
  }, []);

  const paid = tickets.filter((ticket) => ticket.status === 'paid').length;
  return (
    <Card>
      <CardHeader icon={<Wallet size={20} />} title="Mis boletas" subtitle={`${tickets.length} boletas · ${paid} pagadas`} />
      <CardBody>
        <div className="list-stack">
          {tickets.slice(0, 12).map((ticket) => (
            <div key={ticket.id} className="list-row">
              <div>
                <strong>#{ticket.number} · {ticket.raffle_name ?? 'Rifa'}</strong>
                <small>{currency(ticket.price)}</small>
              </div>
              <TicketStatusBadge status={ticket.status} />
            </div>
          ))}
          {tickets.length === 0 && <p className="muted-text">No tienes boletas asignadas.</p>}
        </div>
      </CardBody>
    </Card>
  );
}

function SecurityPanel() {
  const { user, logout } = useAuth();
  const { toast } = useToast();
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [saving, setSaving] = useState(false);

  const submit = async (event: React.FormEvent) => {
    event.preventDefault();
    setSaving(true);
    try {
      await api.changePassword(currentPassword, newPassword);
      setCurrentPassword('');
      setNewPassword('');
      toast('success', 'Contraseña actualizada');
    } catch (error) {
      toast('error', 'No se pudo cambiar', error instanceof ApiError ? error.message : undefined);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="panel-stack">
      <Card>
        <CardHeader icon={<ShieldCheck size={20} />} title="Sesión y seguridad" subtitle="Tu sesión usa JWT de corta duración y refresh rotativo." />
        <CardBody>
          <div className="info-grid">
            <div><span>Usuario</span><strong>{user?.email}</strong></div>
            <div><span>Rol</span><strong>{user ? roleLabel(user.role) : '—'}</strong></div>
            <div><span>Último ingreso</span><strong>{formatDateTime(user?.last_login_at)}</strong></div>
            <div><span>Cambio de contraseña</span><strong>{user?.must_change_password ? 'Pendiente' : 'Al día'}</strong></div>
          </div>
        </CardBody>
      </Card>
      <Card>
        <CardHeader icon={<KeyRound size={20} />} title="Cambiar contraseña" subtitle="Al cambiarla se revocan las demás sesiones." />
        <CardBody>
          <form className="form-grid" onSubmit={submit}>
            <Input label="Contraseña actual" type="password" value={currentPassword} onChange={(event) => setCurrentPassword(event.target.value)} required />
            <Input label="Nueva contraseña" type="password" value={newPassword} onChange={(event) => setNewPassword(event.target.value)} required />
            <div className="form-grid__full panel-actions">
              <Button type="submit" variant="secondary" loading={saving}>Actualizar contraseña</Button>
              <Button type="button" variant="ghost" onClick={() => void logout()}>Cerrar todas las sesiones</Button>
            </div>
          </form>
        </CardBody>
      </Card>
    </div>
  );
}

function ApiPanel() {
  const { toast } = useToast();
  const [keys, setKeys] = useState<ApiKey[]>([]);
  const [name, setName] = useState('');
  const [scopes, setScopes] = useState<string[]>(['raffles.read']);
  const [created, setCreated] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const load = () => {
    api.apiKeys().then((payload) => setKeys(payload.api_keys)).catch(() => undefined);
  };
  useEffect(load, []);

  const create = async (event: React.FormEvent) => {
    event.preventDefault();
    setLoading(true);
    try {
      const payload = await api.createApiKey(name, scopes);
      setCreated(payload.api_key.key);
      setName('');
      toast('success', 'API key creada', 'Cópiala ahora: no se volverá a mostrar.');
      load();
    } catch (error) {
      toast('error', 'No se pudo crear', error instanceof ApiError ? error.message : undefined);
    } finally {
      setLoading(false);
    }
  };

  const revoke = async (id: string) => {
    try {
      await api.revokeApiKey(id);
      toast('success', 'API key revocada');
      load();
    } catch (error) {
      toast('error', 'No se pudo revocar', error instanceof ApiError ? error.message : undefined);
    }
  };

  return (
    <div className="panel-stack">
      <Card>
        <CardHeader icon={<KeyRound size={20} />} title="API keys" subtitle="Acceso máquina-a-máquina con scopes mínimos." />
        <CardBody>
          <div className="list-stack">
            {keys.map((key) => (
              <div key={key.id} className="list-row">
                <div>
                  <strong>{key.name}</strong>
                  <small>{key.prefix} · {key.scopes.join(', ')} · {key.last_used_at ? `Usada ${formatDateTime(key.last_used_at)}` : 'Sin uso'}</small>
                </div>
                {key.revoked_at ? <Badge tone="danger">Revocada</Badge> : <Button size="sm" variant="ghost" onClick={() => revoke(key.id)}>Revocar</Button>}
              </div>
            ))}
            {keys.length === 0 && <p className="muted-text">No hay API keys creadas.</p>}
          </div>
        </CardBody>
      </Card>
      <Card>
        <CardHeader icon={<KeyRound size={20} />} title="Nueva API key" subtitle="Selecciona solo los scopes necesarios." />
        <CardBody>
          <form className="form-grid" onSubmit={create}>
            <Input label="Nombre" value={name} onChange={(event) => setName(event.target.value)} required />
            <div className="form-grid__full">
              <span className="field__label">Scopes</span>
              <div className="scope-grid">
                {SCOPES.map((scope) => (
                  <label key={scope} className="check-row">
                    <input
                      type="checkbox"
                      checked={scopes.includes(scope)}
                      onChange={(event) => setScopes((current) => event.target.checked ? [...current, scope] : current.filter((item) => item !== scope))}
                    />
                    <span>{scope}</span>
                  </label>
                ))}
              </div>
            </div>
            <div className="form-grid__full panel-actions">
              <Button type="submit" loading={loading}>Crear API key</Button>
            </div>
          </form>
          {created && (
            <div className="secret-box">
              <span>Guarda esta clave en un lugar seguro:</span>
              <code>{created}</code>
              <Button size="sm" variant="secondary" icon={<Copy size={15} />} onClick={() => void navigator.clipboard.writeText(created)}>Copiar</Button>
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  );
}

function AuditPanel() {
  const [items, setItems] = useState<AuditEntry[]>([]);
  const [loading, setLoading] = useState(true);
  useEffect(() => {
    api.audit({ limit: 50 })
      .then((payload) => setItems(payload.audit))
      .catch(() => undefined)
      .finally(() => setLoading(false));
  }, []);

  return (
    <Card>
      <CardHeader icon={<ScrollText size={20} />} title="Auditoría reciente" subtitle="Trazabilidad de operaciones críticas." actions={<Button size="sm" variant="ghost" icon={<RefreshCw size={15} />} onClick={() => api.audit({ limit: 50 }).then((payload) => setItems(payload.audit))}>Actualizar</Button>} />
      <CardBody>
        {loading ? <p className="muted-text">Cargando…</p> : (
          <div className="table-wrap">
            <table className="data-table">
              <thead><tr><th>Fecha</th><th>Acción</th><th>Entidad</th><th>IP</th></tr></thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.id}>
                    <td>{formatDateTime(item.created_at)}</td>
                    <td><code>{item.action}</code></td>
                    <td>{item.entity_type}{item.entity_id ? ` · ${item.entity_id.slice(0, 8)}` : ''}</td>
                    <td>{item.ip ?? '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </CardBody>
    </Card>
  );
}

const WEBHOOK_EVENTS = [
  'raffle.created', 'raffle.submitted', 'raffle.approved', 'raffle.rejected',
  'raffle.activated', 'raffle.cancelled', 'raffle.finished', 'ticket.reserved',
  'ticket.paid', 'ticket.cancelled', '*',
];

function IntegrationsPanel() {
  const { toast } = useToast();
  const [webhooks, setWebhooks] = useState<Webhook[]>([]);
  const [name, setName] = useState('');
  const [url, setUrl] = useState('');
  const [events, setEvents] = useState<string[]>(['ticket.paid']);
  const [secret, setSecret] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const load = () => {
    api.webhooks().then((payload) => setWebhooks(payload.webhooks)).catch(() => undefined);
  };
  useEffect(load, []);

  const create = async (event: React.FormEvent) => {
    event.preventDefault();
    setLoading(true);
    try {
      const payload = await api.createWebhook(name, url, events);
      setSecret(payload.webhook.secret ?? null);
      setName('');
      setUrl('');
      toast('success', 'Webhook creado', 'Guarda el secreto para verificar la firma HMAC.');
      load();
    } catch (error) {
      toast('error', 'No se pudo crear el webhook', error instanceof ApiError ? error.message : undefined);
    } finally {
      setLoading(false);
    }
  };

  const test = async (id: string) => {
    try {
      await api.testWebhook(id);
      toast('success', 'Prueba encolada', 'El worker de webhooks entregará el evento.');
    } catch (error) {
      toast('error', 'No se pudo probar', error instanceof ApiError ? error.message : undefined);
    }
  };

  const remove = async (id: string) => {
    if (!window.confirm('¿Eliminar este webhook?')) return;
    try {
      await api.deleteWebhook(id);
      toast('success', 'Webhook eliminado');
      load();
    } catch (error) {
      toast('error', 'No se pudo eliminar', error instanceof ApiError ? error.message : undefined);
    }
  };

  return (
    <div className="panel-stack">
      <Card>
        <CardHeader icon={<Plug size={20} />} title="Webhooks" subtitle="Eventos firmados con HMAC-SHA256 y reintentos con backoff." />
        <CardBody>
          <div className="list-stack">
            {webhooks.map((webhook) => (
              <div key={webhook.id} className="list-row">
                <div>
                  <strong>{webhook.name}</strong>
                  <small>{webhook.url} · {webhook.events.join(', ')}</small>
                </div>
                <div className="row-actions">
                  <Button size="sm" variant="ghost" onClick={() => test(webhook.id)}>Probar</Button>
                  <Button size="sm" variant="danger" onClick={() => remove(webhook.id)}>Eliminar</Button>
                </div>
              </div>
            ))}
            {webhooks.length === 0 && <p className="muted-text">No hay webhooks configurados.</p>}
          </div>
        </CardBody>
      </Card>
      <Card>
        <CardHeader icon={<Plug size={20} />} title="Nuevo webhook" subtitle="Solo se enviarán los eventos seleccionados." />
        <CardBody>
          <form className="form-grid" onSubmit={create}>
            <Input label="Nombre" value={name} onChange={(event) => setName(event.target.value)} required />
            <Input label="URL HTTPS" type="url" value={url} onChange={(event) => setUrl(event.target.value)} placeholder="https://mi-sistema.com/webhooks/skatenea" required />
            <div className="form-grid__full">
              <span className="field__label">Eventos</span>
              <div className="scope-grid">
                {WEBHOOK_EVENTS.map((item) => (
                  <label key={item} className="check-row">
                    <input
                      type="checkbox"
                      checked={events.includes(item)}
                      onChange={(event) => setEvents((current) => event.target.checked ? [...current, item] : current.filter((value) => value !== item))}
                    />
                    <span>{item}</span>
                  </label>
                ))}
              </div>
            </div>
            <div className="form-grid__full panel-actions">
              <Button type="submit" loading={loading} disabled={events.length === 0}>Crear webhook</Button>
            </div>
          </form>
          {secret && (
            <div className="secret-box">
              <span>Secreto de firma (se muestra una sola vez):</span>
              <code>{secret}</code>
              <Button size="sm" variant="secondary" icon={<Copy size={15} />} onClick={() => void navigator.clipboard.writeText(secret)}>Copiar</Button>
            </div>
          )}
        </CardBody>
      </Card>
    </div>
  );
}

function NotificationsPanel() {
  const { toast } = useToast();
  const [items, setItems] = useState<NotificationEntry[]>([]);
  const [unread, setUnread] = useState(0);
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    api.notifications()
      .then((payload) => { setItems(payload.notifications); setUnread(payload.unread); })
      .catch(() => undefined)
      .finally(() => setLoading(false));
  };
  useEffect(load, []);

  const markAll = async () => {
    try {
      await api.markAllNotificationsRead();
      toast('success', 'Notificaciones marcadas como leídas');
      load();
    } catch (error) {
      toast('error', 'No se pudo actualizar', error instanceof ApiError ? error.message : undefined);
    }
  };

  return (
    <Card>
      <CardHeader
        icon={<Bell size={20} />}
        title="Notificaciones"
        subtitle={`${unread} sin leer de ${items.length}`}
        actions={<Button size="sm" variant="secondary" onClick={markAll} disabled={unread === 0}>Marcar todas</Button>}
      />
      <CardBody>
        {loading ? <p className="muted-text">Cargando…</p> : (
          <div className="list-stack">
            {items.map((item) => (
              <div key={item.id} className="list-row">
                <div>
                  <strong>{item.title}</strong>
                  <small>{item.body} · {formatDateTime(item.created_at)}</small>
                </div>
                {!item.read_at && <Badge tone="primary">Nueva</Badge>}
              </div>
            ))}
            {items.length === 0 && <p className="muted-text">No tienes notificaciones.</p>}
          </div>
        )}
      </CardBody>
    </Card>
  );
}

function SystemPanel() {
  const theme = useTheme();
  return (
    <div className="panel-stack">
      <Card>
        <CardHeader icon={<Monitor size={20} />} title="Sistema" subtitle="SKATENEA V1.0.0 alfa" />
        <CardBody>
          <div className="info-grid">
            <div><span>API</span><strong>v1 · OpenAPI 3.1</strong></div>
            <div><span>Frontend</span><strong>React + TypeScript</strong></div>
            <div><span>Backend</span><strong>PHP 8.2 · PDO</strong></div>
            <div><span>Base de datos</span><strong>MySQL / SQLite</strong></div>
          </div>
          <div className="panel-actions">
            <Button variant="secondary" onClick={() => theme.reset()}>Restablecer apariencia</Button>
            <a className="btn btn--ghost btn--md" href="http://127.0.0.1:8000/docs/api/openapi.yaml" target="_blank" rel="noreferrer">Abrir OpenAPI</a>
          </div>
        </CardBody>
      </Card>
    </div>
  );
}
