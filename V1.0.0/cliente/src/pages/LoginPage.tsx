import { useState, type FormEvent } from 'react';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { ArrowLeft, Eye, EyeOff, LockKeyhole, Mail, Sparkles } from 'lucide-react';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/ui/Toast';
import { Background } from '../components/layout/Background';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Field';
import { useTheme } from '../theme/ThemeProvider';

const DEMO_ACCOUNTS = [
  { label: 'Super admin', email: 'admin@skatenea.local', password: 'Admin#2026!' },
  { label: 'Líder', email: 'lider@skatenea.local', password: 'Lider#2026!' },
  { label: 'Vendedor', email: 'vendedor@skatenea.local', password: 'Vendedor#2026!' },
];

export function LoginPage() {
  const { login, isAuthenticated, loading } = useAuth();
  const { toast } = useToast();
  const { mode, cycleMode } = useTheme();
  const navigate = useNavigate();
  const location = useLocation();
  const from = (location.state as { from?: string } | null)?.from ?? '/app';

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!loading && isAuthenticated) return <Navigate to="/app" replace />;

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      await login(email, password);
      toast('success', 'Bienvenido a SKATENEA');
      navigate(from, { replace: true });
    } catch (caught) {
      const message = caught instanceof ApiError ? caught.message : 'No se pudo iniciar sesión.';
      setError(message);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="auth-page">
      <Background />
      <Link to="/" className="auth-back"><ArrowLeft size={17} /> Volver al inicio</Link>
      <div className="auth-shell glass">
        <section className="auth-story">
          <div className="brand">
            <span className="brand__mark" aria-hidden="true"><Sparkles size={26} /></span>
            <span className="brand__text"><strong>SKATENEA</strong><small>Gestión de rifas</small></span>
          </div>
          <div>
            <span className="eyebrow">Bienvenido de vuelta</span>
            <h1>La operación continúa aquí.</h1>
            <p>Accede a tu panel, revisa tus boletas y mantén cada rifa bajo control. Seguridad de nivel productivo, experiencia sin fricción.</p>
          </div>
          <div className="auth-story__features">
            <span>✓ JWT + refresh rotativo</span>
            <span>✓ Auditoría de operaciones</span>
            <span>✓ 12 temas y 3 modos</span>
          </div>
        </section>

        <section className="auth-form-wrap">
          <div className="auth-form__top">
            <div>
              <span className="eyebrow">Acceso seguro</span>
              <h2>Iniciar sesión</h2>
            </div>
            <button type="button" className="btn btn--ghost btn--sm" onClick={cycleMode}>
              {mode === 'light' ? 'Claro' : mode === 'dark' ? 'Oscuro' : 'Contraste'}
            </button>
          </div>

          {error && <div className="alert alert--danger" role="alert">{error}</div>}

          <form className="auth-form" onSubmit={submit}>
            <Input
              label="Correo electrónico"
              type="email"
              autoComplete="email"
              placeholder="tu@correo.com"
              leading={<Mail size={17} />}
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              required
            />
            <Input
              label="Contraseña"
              type={showPassword ? 'text' : 'password'}
              autoComplete="current-password"
              placeholder="Tu contraseña"
              leading={<LockKeyhole size={17} />}
              trailing={
                <button type="button" className="input-action" onClick={() => setShowPassword((visible) => !visible)} aria-label={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}>
                  {showPassword ? <EyeOff size={17} /> : <Eye size={17} />}
                </button>
              }
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              required
            />
            <Button type="submit" block size="lg" loading={submitting}>Entrar a SKATENEA</Button>
          </form>

          <div className="demo-accounts">
            <span>Cuentas de demostración</span>
            <div>
              {DEMO_ACCOUNTS.map((account) => (
                <button key={account.email} type="button" className="demo-chip" onClick={() => { setEmail(account.email); setPassword(account.password); }}>
                  {account.label}
                </button>
              ))}
            </div>
          </div>

          <p className="auth-switch">¿No tienes cuenta? <Link to="/register">Regístrate como comprador</Link></p>
        </section>
      </div>
    </div>
  );
}
