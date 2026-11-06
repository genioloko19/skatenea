import { useEffect, type ReactNode } from 'react';
import { Navigate, Outlet, Route, Routes, useLocation } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { AppShell } from '../components/layout/AppShell';
import { PageLoader } from '../components/ui/Feedback';
import { PublicLandingPage } from '../pages/PublicLandingPage';
import { LoginPage } from '../pages/LoginPage';
import { RegisterPage } from '../pages/RegisterPage';
import { ForgotPasswordPage } from '../pages/ForgotPasswordPage';
import { ResetPasswordPage } from '../pages/ResetPasswordPage';
import { VerifyTicketPage } from '../pages/VerifyTicketPage';
import { DashboardPage } from '../pages/DashboardPage';
import { RafflesPage } from '../pages/RafflesPage';
import { RaffleDetailPage } from '../pages/RaffleDetailPage';
import { TicketsPage } from '../pages/TicketsPage';
import { BuyersPage } from '../pages/BuyersPage';
import { UsersPage } from '../pages/UsersPage';
import { ApprovalsPage } from '../pages/ApprovalsPage';
import { AuditPage } from '../pages/AuditPage';
import { ProfilePage } from '../pages/ProfilePage';
import { ForbiddenPage } from '../pages/ForbiddenPage';
import { NotFoundPage } from '../pages/NotFoundPage';

function RequireAuth() {
  const { loading, isAuthenticated } = useAuth();
  const location = useLocation();

  if (loading) return <PageLoader label="Recuperando tu sesión…" />;
  if (!isAuthenticated) return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  return <Outlet />;
}

function RequirePermission({ permission, children }: { permission: string; children: ReactNode }) {
  const { hasPermission } = useAuth();
  if (!hasPermission(permission)) return <ForbiddenPage />;
  return <>{children}</>;
}

function ScrollToTop() {
  const { pathname } = useLocation();
  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }, [pathname]);
  return null;
}

export function AppRoutes() {
  return (
    <>
      <ScrollToTop />
      <Routes>
        <Route path="/" element={<PublicLandingPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/reset-password" element={<ResetPasswordPage />} />
        <Route path="/verify/:token" element={<VerifyTicketPage />} />

        <Route element={<RequireAuth />}>
          <Route path="/app" element={<AppShell />}>
            <Route index element={<DashboardPage />} />
            <Route path="raffles" element={<RequirePermission permission="raffles.read"><RafflesPage /></RequirePermission>} />
            <Route path="raffles/:id" element={<RequirePermission permission="raffles.read"><RaffleDetailPage /></RequirePermission>} />
            <Route path="tickets" element={<RequirePermission permission="tickets.read"><TicketsPage /></RequirePermission>} />
            <Route path="buyers" element={<RequirePermission permission="buyers.read"><BuyersPage /></RequirePermission>} />
            <Route path="users" element={<RequirePermission permission="users.read"><UsersPage /></RequirePermission>} />
            <Route path="approvals" element={<RequirePermission permission="raffles.approve"><ApprovalsPage /></RequirePermission>} />
            <Route path="audit" element={<RequirePermission permission="audit.read"><AuditPage /></RequirePermission>} />
            <Route path="profile" element={<ProfilePage />} />
            <Route path="forbidden" element={<ForbiddenPage />} />
          </Route>
        </Route>

        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </>
  );
}
