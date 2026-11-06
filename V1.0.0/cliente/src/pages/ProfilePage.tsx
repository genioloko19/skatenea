import { useState, type FormEvent } from 'react';
import { KeyRound, ShieldCheck, UserRound } from 'lucide-react';
import { api, ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/ui/Toast';
import { Badge } from '../components/ui/Badge';
import { Button } from '../components/ui/Button';
import { Card, CardBody, CardHeader } from '../components/ui/Card';
import { PageHeader } from '../components/ui/Feedback';
import { Input, Select } from '../components/ui/Field';
import { formatDateTime, roleLabel } from '../lib/format';

export function ProfilePage() {
  const { user, refresh } = useAuth();
  const { toast } = useToast();
  const [profile, setProfile] = useState({
    full_name: user?.full_name ?? '',
    phone: user?.phone ?? '',
    document_type: user?.document_type ?? 'CC',
    document_number: user?.document_number ?? '',
  });
  const [passwords, setPasswords] = useState({ current: '', next: '', confirm: '' });
  const [saving, setSaving] = useState(false);
  const [changing, setChanging] = useState(false);

  if (!user) return null;

  const saveProfile = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    try {
      await api.updateProfile(profile);
      await refresh();
      toast('success', 'Perfil actualizado');
    } catch (error) {
      toast('error', 'No se pudo actualizar', error instanceof ApiError ? error.message : undefined);
    } finally {
      setSaving(false);
    }
  };

  const changePassword = async (event: FormEvent) => {
    event.preventDefault();
    if (passwords.next !== passwords.confirm) {
      toast('error', 'Las contraseñas no coinciden');
      return;
    }
    setChanging(true);
    try {
      await api.changePassword(passwords.current, passwords.next);
      setPasswords({ current: '', next: '', confirm: '' });
      toast('success', 'Contraseña actualizada', 'Las demás sesiones fueron revocadas.');
    } catch (error) {
      toast('error', 'No se pudo cambiar la contraseña', error instanceof ApiError ? error.message : undefined);
    } finally {
      setChanging(false);
    }
  };

  return (
    <div className="page-stack">
      <PageHeader eyebrow="Cuenta" title="Mi perfil" subtitle="Actualiza tus datos y mantén tu cuenta segura." />

      <div className="profile-grid">
        <Card>
          <CardHeader icon={<UserRound size={20} />} title="Identidad" subtitle="Datos visibles para la operación." />
          <CardBody>
            <div className="profile-summary">
              <span className="avatar avatar--xl" aria-hidden="true">{user.full_name.split(' ').slice(0, 2).map((part) => part[0]).join('').toUpperCase()}</span>
              <div>
                <h3>{user.full_name}</h3>
                <p>{user.email}</p>
                <div className="row-actions">
                  <Badge tone="primary">{roleLabel(user.role)}</Badge>
                  <Badge tone={user.status === 'active' ? 'success' : 'danger'}>{user.status}</Badge>
                </div>
              </div>
            </div>
            <div className="info-grid">
              <div><span>Último ingreso</span><strong>{formatDateTime(user.last_login_at)}</strong></div>
              <div><span>Miembro desde</span><strong>{formatDateTime(user.created_at)}</strong></div>
              <div><span>Cambio de contraseña</span><strong>{user.must_change_password ? 'Pendiente' : 'Al día'}</strong></div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardHeader icon={<ShieldCheck size={20} />} title="Editar datos" subtitle="Mantén tu información de contacto actualizada." />
          <CardBody>
            <form className="form-grid" onSubmit={saveProfile}>
              <Input label="Nombre completo" value={profile.full_name} onChange={(event) => setProfile({ ...profile, full_name: event.target.value })} required />
              <Input label="Teléfono" value={profile.phone} onChange={(event) => setProfile({ ...profile, phone: event.target.value })} />
              <Select label="Tipo de documento" value={profile.document_type} onChange={(event) => setProfile({ ...profile, document_type: event.target.value })}>
                <option value="CC">Cédula de ciudadanía</option>
                <option value="CE">Cédula de extranjería</option>
                <option value="PA">Pasaporte</option>
                <option value="NIT">NIT</option>
              </Select>
              <Input label="Número de documento" value={profile.document_number} onChange={(event) => setProfile({ ...profile, document_number: event.target.value })} />
              <div className="form-grid__full panel-actions">
                <Button type="submit" loading={saving}>Guardar cambios</Button>
              </div>
            </form>
          </CardBody>
        </Card>
      </div>

      <Card>
        <CardHeader icon={<KeyRound size={20} />} title="Seguridad" subtitle="Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo." />
        <CardBody>
          <form className="form-grid" onSubmit={changePassword}>
            <Input label="Contraseña actual" type="password" value={passwords.current} onChange={(event) => setPasswords({ ...passwords, current: event.target.value })} required />
            <Input label="Nueva contraseña" type="password" value={passwords.next} onChange={(event) => setPasswords({ ...passwords, next: event.target.value })} required />
            <Input label="Confirmar contraseña" type="password" value={passwords.confirm} onChange={(event) => setPasswords({ ...passwords, confirm: event.target.value })} required />
            <div className="form-grid__full panel-actions">
              <Button type="submit" variant="secondary" loading={changing}>Actualizar contraseña</Button>
            </div>
          </form>
        </CardBody>
      </Card>
    </div>
  );
}
