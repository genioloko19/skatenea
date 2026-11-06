import { useState, type FormEvent } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { ArrowLeft, KeyRound, Sparkles } from 'lucide-react';
import { api, ApiError } from '../api/client';
import { useToast } from '../components/ui/Toast';
import { Background } from '../components/layout/Background';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Field';

export function ResetPasswordPage() {
  const [params] = useSearchParams();
  const token = params.get('token') ?? '';
  const navigate = useNavigate();
  const { toast } = useToast();
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    if (password !== confirm) {
      setError('Las contraseñas no coinciden.');
      return;
    }
    if (!token) {
      setError('El enlace no contiene un token válido.');
      return;
    }
    setLoading(true);
    try {
      await api.resetPassword(token, password);
      toast('success', 'Contraseña actualizada', 'Inicia sesión con tu nueva contraseña.');
      navigate('/login');
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'No se pudo actualizar la contraseña.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-page">
      <Background />
      <Link to="/login" className="auth-back"><ArrowLeft size={17} /> Volver al inicio de sesión</Link>
      <div className="auth-shell glass auth-shell--compact">
        <section className="auth-story">
          <div className="brand">
            <span className="brand__mark" aria-hidden="true"><Sparkles size={26} /></span>
            <span className="brand__text"><strong>SKATENEA</strong><small>Gestión de rifas</small></span>
          </div>
          <div>
            <span className="eyebrow">Nueva contraseña</span>
            <h1>Define tu nuevo acceso.</h1>
            <p>La contraseña debe tener al menos 12 caracteres, mayúscula, minúscula, número y símbolo.</p>
          </div>
          <div className="auth-story__features">
            <span>✓ Token de un solo uso</span>
            <span>✓ Revoca las demás sesiones</span>
            <span>✓ Sin contraseñas por correo</span>
          </div>
        </section>
        <section className="auth-form-wrap">
          <div className="auth-form__top">
            <div><span className="eyebrow">Seguridad</span><h2>Restablecer contraseña</h2></div>
          </div>
          {error && <div className="alert alert--danger" role="alert">{error}</div>}
          <form className="auth-form" onSubmit={submit}>
            <Input label="Nueva contraseña" type="password" leading={<KeyRound size={17} />} value={password} onChange={(event) => setPassword(event.target.value)} required hint="Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo." />
            <Input label="Confirmar contraseña" type="password" value={confirm} onChange={(event) => setConfirm(event.target.value)} required />
            <Button type="submit" block size="lg" loading={loading}>Actualizar contraseña</Button>
          </form>
          <p className="auth-switch">¿Ya la cambiaste? <Link to="/login">Inicia sesión</Link></p>
        </section>
      </div>
    </div>
  );
}
