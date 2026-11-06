import { useState, type FormEvent } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import { ArrowLeft, Mail, Phone, Sparkles, UserRound } from 'lucide-react';
import { ApiError, api } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/ui/Toast';
import { Background } from '../components/layout/Background';
import { Button } from '../components/ui/Button';
import { Input, Select } from '../components/ui/Field';

export function RegisterPage() {
  const { isAuthenticated, loading } = useAuth();
  const { toast } = useToast();
  const navigate = useNavigate();
  const [form, setForm] = useState({
    full_name: '',
    email: '',
    phone: '',
    document_type: 'CC',
    document_number: '',
    password: '',
    confirm: '',
  });
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!loading && isAuthenticated) return <Navigate to="/app" replace />;

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setError(null);
    if (form.password !== form.confirm) {
      setError('Las contraseñas no coinciden.');
      return;
    }
    if (form.password.length < 12) {
      setError('La contraseña debe tener al menos 12 caracteres.');
      return;
    }
    setSubmitting(true);
    try {
      await api.register({
        full_name: form.full_name,
        email: form.email,
        phone: form.phone,
        document_type: form.document_type,
        document_number: form.document_number,
        password: form.password,
      });
      toast('success', 'Cuenta creada', 'Ya puedes iniciar sesión.');
      navigate('/login');
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.message : 'No se pudo crear la cuenta.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="auth-page">
      <Background />
      <Link to="/" className="auth-back"><ArrowLeft size={17} /> Volver al inicio</Link>
      <div className="auth-shell glass auth-shell--register">
        <section className="auth-story">
          <div className="brand">
            <span className="brand__mark" aria-hidden="true"><Sparkles size={26} /></span>
            <span className="brand__text"><strong>SKATENEA</strong><small>Gestión de rifas</small></span>
          </div>
          <div>
            <span className="eyebrow">Únete</span>
            <h1>Tu próxima oportunidad empieza aquí.</h1>
            <p>Crea tu cuenta de comprador para consultar rifas activas y revisar las boletas que has adquirido.</p>
          </div>
          <div className="auth-story__features">
            <span>✓ Consulta de boletas propias</span>
            <span>✓ Verificación QR</span>
            <span>✓ Datos protegidos</span>
          </div>
        </section>

        <section className="auth-form-wrap">
          <div className="auth-form__top">
            <div>
              <span className="eyebrow">Registro de comprador</span>
              <h2>Crear cuenta</h2>
            </div>
          </div>

          {error && <div className="alert alert--danger" role="alert">{error}</div>}

          <form className="auth-form" onSubmit={submit}>
            <Input label="Nombre completo" leading={<UserRound size={17} />} value={form.full_name} onChange={(event) => setForm({ ...form, full_name: event.target.value })} required />
            <Input label="Correo electrónico" type="email" leading={<Mail size={17} />} value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} required />
            <Input label="Teléfono" leading={<Phone size={17} />} value={form.phone} onChange={(event) => setForm({ ...form, phone: event.target.value })} required />
            <div className="form-grid">
              <Select label="Tipo de documento" value={form.document_type} onChange={(event) => setForm({ ...form, document_type: event.target.value })}>
                <option value="CC">Cédula de ciudadanía</option>
                <option value="CE">Cédula de extranjería</option>
                <option value="PA">Pasaporte</option>
                <option value="NIT">NIT</option>
              </Select>
              <Input label="Número de documento" value={form.document_number} onChange={(event) => setForm({ ...form, document_number: event.target.value })} />
            </div>
            <Input label="Contraseña" type="password" value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })} required hint="Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo." />
            <Input label="Confirmar contraseña" type="password" value={form.confirm} onChange={(event) => setForm({ ...form, confirm: event.target.value })} required />
            <Button type="submit" block size="lg" loading={submitting}>Crear mi cuenta</Button>
          </form>

          <p className="auth-switch">¿Ya tienes cuenta? <Link to="/login">Inicia sesión</Link></p>
        </section>
      </div>
    </div>
  );
}
