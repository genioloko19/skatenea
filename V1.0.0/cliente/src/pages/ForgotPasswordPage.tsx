import { useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft, Mail, Sparkles } from 'lucide-react';
import { api, ApiError } from '../api/client';
import { Background } from '../components/layout/Background';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Field';

export function ForgotPasswordPage() {
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const result = await api.forgotPassword(email);
      setMessage(result.message);
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'No se pudo procesar la solicitud.');
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
            <span className="eyebrow">Recuperación</span>
            <h1>Recupera tu acceso.</h1>
            <p>Te enviaremos un enlace de un solo uso al correo registrado. El enlace vence en 30 minutos.</p>
          </div>
          <div className="auth-story__features">
            <span>✓ Token de un solo uso</span>
            <span>✓ Sesiones revocadas al cambiar</span>
            <span>✓ Sin revelar si la cuenta existe</span>
          </div>
        </section>
        <section className="auth-form-wrap">
          <div className="auth-form__top">
            <div><span className="eyebrow">Cuenta</span><h2>Olvidé mi contraseña</h2></div>
          </div>
          {message && <div className="alert alert--success" role="status">{message}</div>}
          {error && <div className="alert alert--danger" role="alert">{error}</div>}
          <form className="auth-form" onSubmit={submit}>
            <Input label="Correo electrónico" type="email" leading={<Mail size={17} />} value={email} onChange={(event) => setEmail(event.target.value)} required />
            <Button type="submit" block size="lg" loading={loading}>Enviar enlace</Button>
          </form>
          <p className="auth-switch">¿Recordaste tu contraseña? <Link to="/login">Inicia sesión</Link></p>
        </section>
      </div>
    </div>
  );
}
