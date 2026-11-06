export type Role = 'super_admin' | 'leader' | 'seller' | 'buyer';
export type UserStatus = 'active' | 'inactive' | 'locked';
export type RaffleStatus =
  | 'draft'
  | 'pending_approval'
  | 'approved'
  | 'active'
  | 'closed'
  | 'finished'
  | 'cancelled'
  | 'rejected';
export type TicketStatus = 'available' | 'reserved' | 'paid' | 'cancelled' | 'winner';

export interface User {
  id: string;
  email: string;
  full_name: string;
  role: Role;
  status: UserStatus;
  phone: string | null;
  document_type: string | null;
  document_number: string | null;
  must_change_password: boolean;
  last_login_at: string | null;
  created_at: string;
  updated_at: string;
}

export type ThemeId =
  | 'aurora'
  | 'neon'
  | 'sunset'
  | 'ocean'
  | 'forest'
  | 'candy'
  | 'cyber'
  | 'royal'
  | 'terracotta'
  | 'mono'
  | 'tropical'
  | 'galaxy';
export type ThemeMode = 'light' | 'dark' | 'contrast';
export type Geometry = 'soft' | 'sharp' | 'pill' | 'organic';
export type BackgroundId = 'aurora' | 'mesh' | 'waves' | 'orbs' | 'grid' | 'static';

export interface Preferences {
  user_id: string;
  theme: ThemeId;
  mode: ThemeMode;
  geometry: Geometry;
  background: BackgroundId;
  sidebar_collapsed: number;
}

export interface RaffleStats {
  total: number;
  available: number;
  reserved: number;
  paid: number;
  cancelled: number;
  revenue: number;
}

export interface Raffle {
  id: string;
  code: string;
  name: string;
  description: string | null;
  prize: string;
  ticket_price: number;
  total_tickets: number;
  status: RaffleStatus;
  created_by: string;
  approved_by: string | null;
  approved_at: string | null;
  rejection_reason: string | null;
  start_at: string;
  end_at: string;
  draw_seed_hash: string | null;
  winner_ticket_id: string | null;
  created_at: string;
  updated_at: string;
  stats: RaffleStats;
}

export interface RaffleSeller {
  id: string;
  full_name: string;
  email: string;
  phone: string | null;
  status: string;
  assigned_at: string;
}

export interface RaffleApproval {
  id: string;
  raffle_id: string;
  actor_id: string;
  actor_name: string;
  action: string;
  reason: string | null;
  created_at: string;
}

export interface Ticket {
  id: string;
  raffle_id: string;
  number: number;
  seller_id: string | null;
  buyer_id: string | null;
  status: TicketStatus;
  price: number;
  qr_token: string;
  reserved_until: string | null;
  reserved_at: string | null;
  paid_at: string | null;
  created_at: string;
  updated_at: string;
  buyer_name: string | null;
  seller_name: string | null;
  raffle_name: string | null;
}

export interface Buyer {
  id: string;
  full_name: string;
  email: string | null;
  phone: string;
  document_type: string | null;
  document_number: string | null;
  user_id: string | null;
  created_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface Dashboard {
  role: Role;
  metrics: Record<string, number | Record<string, number>>;
  recent_raffles?: Raffle[];
  recent_tickets?: Ticket[];
}

export interface ApiKey {
  id: string;
  name: string;
  prefix: string;
  scopes: string[];
  created_by: string;
  last_used_at: string | null;
  expires_at: string | null;
  revoked_at: string | null;
  created_at: string;
}

export interface ApiKeyCreated {
  id: string;
  name: string;
  prefix: string;
  scopes: string[];
  expires_at: string | null;
  key: string;
}

export interface AuditEntry {
  id: string;
  actor_id: string | null;
  actor_type: string;
  action: string;
  entity_type: string;
  entity_id: string | null;
  before_json: string | null;
  after_json: string | null;
  ip: string | null;
  user_agent: string | null;
  created_at: string;
}

export interface SessionPayload {
  access_token: string;
  token_type: 'Bearer';
  expires_in: number;
  csrf_token: string;
  user: User;
  permissions: string[];
}

export interface MePayload {
  user: User;
  permissions: string[];
  preferences: Preferences;
}

export interface RaffleDetail {
  raffle: Raffle;
  sellers: RaffleSeller[];
  ticket_stats: Record<string, number>;
  approvals: RaffleApproval[];
}

export interface DashboardPayload {
  dashboard: Dashboard;
}

export interface SettingsPayload {
  settings: Record<string, unknown>;
}

export interface AuditPayload {
  audit: AuditEntry[];
}

export interface ApiKeysPayload {
  api_keys: ApiKey[];
}

export interface UsersPayload {
  users: User[];
}

export interface BuyersPayload {
  buyers: Buyer[];
}

export interface TicketsPayload {
  tickets: Ticket[];
}

export interface RafflesPayload {
  raffles: Raffle[];
}

export interface Envelope<T> {
  data: T;
  meta?: Record<string, unknown>;
}

export interface ApiErrorBody {
  error: {
    code: string;
    message: string;
    details?: Record<string, unknown>;
  };
}

export interface CreateRaffleInput {
  name: string;
  description?: string;
  prize: string;
  ticket_price: number;
  total_tickets: number;
  start_at: string;
  end_at: string;
  code?: string;
}

export interface CreateTicketBuyerInput {
  buyer_id?: string;
  full_name?: string;
  email?: string;
  phone?: string;
  document_type?: string;
  document_number?: string;
  reservation_hours?: number;
}
