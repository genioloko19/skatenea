import type {
  ApiKeysPayload,
  ApiKeyCreated,
  AuditPayload,
  BuyersPayload,
  CreateRaffleInput,
  CreateTicketBuyerInput,
  Dashboard,
  DashboardPayload,
  MePayload,
  Preferences,
  Raffle,
  RaffleDetail,
  RafflesPayload,
  SessionPayload,
  SettingsPayload,
  Ticket,
  TicketsPayload,
  UsersPayload,
} from './types';

const API_BASE = import.meta.env.VITE_API_URL ?? '/api/v1';

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly code = 'api_error',
    public readonly details?: Record<string, unknown>,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

type Query = Record<string, string | number | boolean | null | undefined>;

function readCookie(name: string): string | null {
  if (typeof document === 'undefined') return null;
  const match = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}=([^;]*)`));
  return match ? decodeURIComponent(match[1]) : null;
}

function withQuery(path: string, query?: Query): string {
  if (!query) return path;
  const params = new URLSearchParams();
  Object.entries(query).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params.set(key, String(value));
    }
  });
  const serialized = params.toString();
  return serialized ? `${path}?${serialized}` : path;
}

export class ApiClient {
  private accessToken: string | null = null;
  private csrfToken: string | null = null;
  private refreshPromise: Promise<boolean> | null = null;

  setSession(accessToken: string, csrfToken: string): void {
    this.accessToken = accessToken;
    this.csrfToken = csrfToken;
  }

  clearSession(): void {
    this.accessToken = null;
    this.csrfToken = null;
  }

  hasAccessToken(): boolean {
    return this.accessToken !== null;
  }

  hasRefreshHint(): boolean {
    return readCookie('skatenea_csrf') !== null;
  }

  async request<T>(path: string, options: RequestInit = {}, retry = true): Promise<T> {
    const headers = new Headers(options.headers);
    headers.set('Accept', 'application/json');
    if (options.body && !headers.has('Content-Type')) {
      headers.set('Content-Type', 'application/json');
    }
    if (this.accessToken) {
      headers.set('Authorization', `Bearer ${this.accessToken}`);
    }
    const csrf = this.csrfToken ?? readCookie('skatenea_csrf');
    if (csrf) {
      headers.set('X-CSRF-Token', csrf);
    }

    const isAuthFlow = path.startsWith('/auth/login')
      || path.startsWith('/auth/register')
      || path.startsWith('/auth/refresh');
    if (!this.accessToken && retry && !isAuthFlow && this.hasRefreshHint()) {
      await this.refresh();
      const refreshedToken = this.accessToken;
      if (refreshedToken) {
        headers.set('Authorization', `Bearer ${refreshedToken}`);
      }
    }

    const response = await fetch(`${API_BASE}${path}`, {
      ...options,
      headers,
      credentials: 'include',
    });

    if (response.status === 401 && retry && !path.startsWith('/auth/login') && !path.startsWith('/auth/refresh')) {
      const refreshed = await this.refresh();
      if (refreshed) {
        return this.request<T>(path, options, false);
      }
    }

    const text = await response.text();
    interface ApiPayload<T> {
      data?: T;
      error?: { code?: string; message?: string; details?: Record<string, unknown> };
    }
    let payload: ApiPayload<T> | null = null;
    if (text) {
      try {
        payload = JSON.parse(text) as ApiPayload<T>;
      } catch {
        payload = null;
      }
    }

    if (!response.ok) {
      throw new ApiError(
        payload?.error?.message ?? `Error HTTP ${response.status}`,
        response.status,
        payload?.error?.code ?? 'http_error',
        payload?.error?.details,
      );
    }

    return (payload?.data ?? (payload as T)) as T;
  }

  async refresh(): Promise<boolean> {
    if (this.refreshPromise) return this.refreshPromise;

    const promise = this.performRefresh().finally(() => {
      this.refreshPromise = null;
    });
    this.refreshPromise = promise;
    return promise;
  }

  private async performRefresh(): Promise<boolean> {
    const csrf = this.csrfToken ?? readCookie('skatenea_csrf');
    if (!csrf) {
      this.clearSession();
      return false;
    }

    const tokenBefore = this.accessToken;
    const response = await fetch(`${API_BASE}/auth/refresh`, {
      method: 'POST',
      credentials: 'include',
      headers: { Accept: 'application/json', 'X-CSRF-Token': csrf },
    });

    if (!response.ok) {
      // Otra petición pudo haber rotado el token correctamente mientras esta fallaba.
      if (this.accessToken !== tokenBefore) return true;
      this.clearSession();
      return false;
    }

    const payload = (await response.json()) as { data: SessionPayload };
    this.setSession(payload.data.access_token, payload.data.csrf_token);
    return true;
  }

  async login(email: string, password: string): Promise<SessionPayload> {
    const payload = await this.request<SessionPayload>(
      '/auth/login',
      { method: 'POST', body: JSON.stringify({ email, password }) },
      false,
    );
    this.setSession(payload.access_token, payload.csrf_token);
    return payload;
  }

  async logout(): Promise<void> {
    try {
      await this.request<void>('/auth/logout', { method: 'POST' }, false);
    } finally {
      this.clearSession();
    }
  }

  me(): Promise<MePayload> {
    return this.request<MePayload>('/auth/me');
  }

  register(input: {
    full_name: string;
    email: string;
    password: string;
    phone: string;
    document_type?: string;
    document_number?: string;
  }): Promise<{ user: unknown }> {
    return this.request<{ user: unknown }>('/auth/register', {
      method: 'POST',
      body: JSON.stringify(input),
    });
  }

  changePassword(currentPassword: string, newPassword: string): Promise<void> {
    return this.request<void>('/auth/password', {
      method: 'PATCH',
      body: JSON.stringify({ current_password: currentPassword, new_password: newPassword }),
    });
  }

  dashboard(): Promise<Dashboard> {
    return this.request<DashboardPayload>('/dashboard').then((payload) => payload.dashboard);
  }

  raffles(query?: Query): Promise<RafflesPayload> {
    return this.request<RafflesPayload>(withQuery('/raffles', query));
  }

  raffle(id: string): Promise<RaffleDetail> {
    return this.request<RaffleDetail>(`/raffles/${id}`);
  }

  createRaffle(input: CreateRaffleInput): Promise<{ raffle: Raffle }> {
    return this.request<{ raffle: Raffle }>('/raffles', {
      method: 'POST',
      body: JSON.stringify(input),
    });
  }

  updateRaffle(id: string, input: Partial<CreateRaffleInput>): Promise<{ raffle: Raffle }> {
    return this.request<{ raffle: Raffle }>(`/raffles/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(input),
    });
  }

  raffleAction(id: string, action: string, body?: Record<string, unknown>): Promise<{ raffle: Raffle }> {
    return this.request<{ raffle: Raffle }>(`/raffles/${id}/${action}`, {
      method: 'POST',
      body: body ? JSON.stringify(body) : undefined,
    });
  }

  assignSellers(id: string, sellerIds: string[]): Promise<{ sellers: unknown[] }> {
    return this.request<{ sellers: unknown[] }>(`/raffles/${id}/sellers`, {
      method: 'POST',
      body: JSON.stringify({ seller_ids: sellerIds }),
    });
  }

  generateTickets(id: string): Promise<{ ticket_stats: Record<string, number> }> {
    return this.request<{ ticket_stats: Record<string, number> }>(`/raffles/${id}/tickets/generate`, {
      method: 'POST',
    });
  }

  assignTickets(id: string, sellerId: string, from: number, to: number): Promise<{ updated: number }> {
    return this.request<{ updated: number }>(`/raffles/${id}/tickets/assign`, {
      method: 'POST',
      body: JSON.stringify({ seller_id: sellerId, from, to }),
    });
  }

  draw(id: string): Promise<{ winner: Ticket | null; seed: string; seed_hash: string; paid_tickets: number }> {
    return this.request<{ winner: Ticket | null; seed: string; seed_hash: string; paid_tickets: number }>(
      `/raffles/${id}/draw`,
      { method: 'POST' },
    );
  }

  ticketsByRaffle(id: string, query?: Query): Promise<TicketsPayload> {
    return this.request<TicketsPayload>(withQuery(`/raffles/${id}/tickets`, query));
  }

  myTickets(): Promise<TicketsPayload> {
    return this.request<TicketsPayload>('/tickets/mine');
  }

  reserveTicket(id: string, input: CreateTicketBuyerInput): Promise<{ ticket: Ticket }> {
    return this.request<{ ticket: Ticket }>(`/tickets/${id}/reserve`, {
      method: 'POST',
      body: JSON.stringify(input),
    });
  }

  payTicket(id: string, method: string, reference?: string): Promise<{ ticket: Ticket }> {
    return this.request<{ ticket: Ticket }>(`/tickets/${id}/pay`, {
      method: 'POST',
      body: JSON.stringify({ method, reference }),
    });
  }

  cancelTicket(id: string, reason?: string): Promise<{ ticket: Ticket }> {
    return this.request<{ ticket: Ticket }>(`/tickets/${id}/cancel`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    });
  }

  searchBuyers(term: string): Promise<BuyersPayload> {
    return this.request<BuyersPayload>(withQuery('/buyers', { q: term }));
  }

  createBuyer(input: {
    full_name: string;
    email?: string;
    phone: string;
    document_type?: string;
    document_number?: string;
  }): Promise<{ buyer: unknown }> {
    return this.request<{ buyer: unknown }>('/buyers', {
      method: 'POST',
      body: JSON.stringify(input),
    });
  }

  users(query?: Query): Promise<UsersPayload> {
    return this.request<UsersPayload>(withQuery('/users', query));
  }

  sellers(): Promise<UsersPayload> {
    return this.request<UsersPayload>('/sellers');
  }

  createUser(input: {
    full_name: string;
    email: string;
    password: string;
    role: string;
    phone?: string;
  }): Promise<{ user: unknown }> {
    return this.request<{ user: unknown }>('/users', {
      method: 'POST',
      body: JSON.stringify(input),
    });
  }

  deactivateUser(id: string): Promise<{ user: unknown }> {
    return this.request<{ user: unknown }>(`/users/${id}`, { method: 'DELETE' });
  }

  updateProfile(input: {
    full_name?: string;
    phone?: string;
    document_type?: string;
    document_number?: string;
  }): Promise<{ user: unknown }> {
    return this.request<{ user: unknown }>('/profile', {
      method: 'PATCH',
      body: JSON.stringify(input),
    });
  }

  preferences(): Promise<Preferences> {
    return this.request<{ preferences: Preferences }>('/preferences').then((payload) => payload.preferences);
  }

  updatePreferences(input: Partial<Preferences>): Promise<Preferences> {
    return this.request<{ preferences: Preferences }>('/preferences', {
      method: 'PATCH',
      body: JSON.stringify(input),
    }).then((payload) => payload.preferences);
  }

  apiKeys(): Promise<ApiKeysPayload> {
    return this.request<ApiKeysPayload>('/api-keys');
  }

  createApiKey(name: string, scopes: string[], expiresAt?: string | null): Promise<{ api_key: ApiKeyCreated }> {
    return this.request<{ api_key: ApiKeyCreated }>('/api-keys', {
      method: 'POST',
      body: JSON.stringify({ name, scopes, expires_at: expiresAt ?? null }),
    });
  }

  revokeApiKey(id: string): Promise<void> {
    return this.request<void>(`/api-keys/${id}`, { method: 'DELETE' });
  }

  audit(query?: Query): Promise<AuditPayload> {
    return this.request<AuditPayload>(withQuery('/audit', query));
  }

  settings(): Promise<SettingsPayload> {
    return this.request<SettingsPayload>('/settings');
  }

  updateSettings(values: Record<string, unknown>): Promise<SettingsPayload> {
    return this.request<SettingsPayload>('/settings', {
      method: 'PATCH',
      body: JSON.stringify(values),
    });
  }
}

export const api = new ApiClient();
