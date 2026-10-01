# Desain Frontend: Next.js (App Router)

**Constraint:** Konsumsi RESTful API Laravel, kompatibel dengan legacy S3 system.
**Update:** Tambah error boundary, loading states, optimistic updates, ticket comments & timeline, PM interface.
**Update 2:** Many-to-many ticket-unit relationship, Preventive Maintenance interface.
**Update 3:** Tambah WebSocket client, Zod schemas, i18n, dark mode, mobile responsive, missing components.

---

## 1. Struktur Folder Next.js (App Router)

```
frontend/
├── src/
│   ├── app/
│   │   ├── layout.tsx                    # Root layout (providers, fonts, metadata)
│   │   ├── page.tsx                      # Landing page
│   │   ├── globals.css                   # Tailwind + custom styles
│   │   ├── error.tsx                     # Global error boundary
│   │   ├── not-found.tsx                 # 404 page
│   │   │
│   │   ├── (auth)/
│   │   │   ├── login/
│   │   │   │   ├── page.tsx
│   │   │   │   └── loading.tsx
│   │   │   ├── forgot-password/
│   │   │   │   └── page.tsx
│   │   │   ├── layout.tsx
│   │   │   └── error.tsx
│   │   │
│   │   ├── (dashboard)/
│   │   │   ├── layout.tsx
│   │   │   ├── loading.tsx
│   │   │   ├── error.tsx
│   │   │   ├── dashboard/
│   │   │   │   ├── page.tsx
│   │   │   │   └── loading.tsx
│   │   │   │
│   │   │   ├── tickets/
│   │   │   │   ├── page.tsx              # Ticket list
│   │   │   │   ├── loading.tsx
│   │   │   │   ├── [ticketId]/
│   │   │   │   │   ├── page.tsx          # Ticket detail (dengan comments & timeline)
│   │   │   │   │   └── loading.tsx
│   │   │   │   └── new/
│   │   │   │       ├── page.tsx          # Create ticket (dengan multiple units)
│   │   │   │       └── loading.tsx
│   │   │   │
│   │   │   ├── units/
│   │   │   │   ├── page.tsx
│   │   │   │   ├── loading.tsx
│   │   │   │   └── [unitId]/
│   │   │   │       ├── page.tsx
│   │   │   │       └── loading.tsx
│   │   │   │
│   │   │   ├── preventive-maintenances/
│   │   │   │   ├── page.tsx              # PM list
│   │   │   │   ├── loading.tsx
│   │   │   │   ├── [pmId]/
│   │   │   │   │   ├── page.tsx          # PM detail (dengan progress & tickets)
│   │   │   │   │   └── loading.tsx
│   │   │   │   └── new/
│   │   │   │       ├── page.tsx          # Create PM (dengan multiple SNs)
│   │   │   │       └── loading.tsx
│   │   │   │
│   │   │   ├── loans/
│   │   │   │   ├── page.tsx
│   │   │   │   ├── loading.tsx
│   │   │   │   └── [loanId]/
│   │   │   │       ├── page.tsx
│   │   │   │       └── loading.tsx
│   │   │   │
│   │   │   ├── sales-orders/
│   │   │   │   ├── page.tsx
│   │   │   │   ├── loading.tsx
│   │   │   │   └── [soNo]/
│   │   │   │       ├── page.tsx
│   │   │   │       └── loading.tsx
│   │   │   │
│   │   │   ├── quotations/
│   │   │   │   ├── page.tsx
│   │   │   │   ├── loading.tsx
│   │   │   │   └── [quoteNo]/
│   │   │   │       ├── page.tsx
│   │   │   │       └── loading.tsx
│   │   │   │
│   │   │   ├── sprf/
│   │   │   │   ├── page.tsx
│   │   │   │   ├── loading.tsx
│   │   │   │   └── [sprfNo]/
│   │   │   │       ├── page.tsx
│   │   │   │       └── loading.tsx
│   │   │   │
│   │   │   ├── customers/
│   │   │   │   ├── page.tsx
│   │   │   │   ├── loading.tsx
│   │   │   │   └── [customerId]/
│   │   │   │       ├── page.tsx
│   │   │   │       └── loading.tsx
│   │   │   │
│   │   │   ├── stock/
│   │   │   │   ├── page.tsx
│   │   │   │   └── loading.tsx
│   │   │   │
│   │   │   ├── engineers/
│   │   │   │   ├── page.tsx
│   │   │   │   └── loading.tsx
│   │   │   │
│   │   │   ├── reports/
│   │   │   │   ├── page.tsx
│   │   │   │   ├── loading.tsx
│   │   │   │   ├── sla/
│   │   │   │   │   ├── page.tsx
│   │   │   │   │   └── loading.tsx
│   │   │   │   └── loan-status/
│   │   │   │       ├── page.tsx
│   │   │   │       └── loading.tsx
│   │   │   │
│   │   │   └── settings/
│   │   │       ├── page.tsx
│   │   │       └── loading.tsx
│   │   │
│   │   └── api/
│   │       └── auth/
│   │           └── logout/
│   │               └── route.ts
│   │
│   ├── components/
│   │   ├── ui/                           # Reusable UI components
│   │   │   ├── button.tsx
│   │   │   ├── input.tsx
│   │   │   ├── select.tsx
│   │   │   ├── modal.tsx
│   │   │   ├── table.tsx
│   │   │   ├── pagination.tsx
│   │   │   ├── badge.tsx
│   │   │   ├── card.tsx
│   │   │   ├── dropdown.tsx
│   │   │   ├── date-picker.tsx
│   │   │   ├── search-input.tsx
│   │   │   ├── loading-spinner.tsx
│   │   │   ├── error-boundary.tsx
│   │   │   ├── empty-state.tsx
│   │   │   ├── confirm-dialog.tsx
│   │   │   └── toast.tsx
│   │   │
│   │   ├── layout/
│   │   │   ├── sidebar.tsx
│   │   │   ├── header.tsx
│   │   │   ├── breadcrumb.tsx
│   │   │   └── mobile-nav.tsx
│   │   │
│   │   ├── tickets/
│   │   │   ├── ticket-table.tsx
│   │   │   ├── ticket-form.tsx           # Form dengan multiple units
│   │   │   ├── ticket-detail.tsx
│   │   │   ├── ticket-status-badge.tsx
│   │   │   ├── ticket-timeline.tsx       # Timeline component
│   │   │   ├── ticket-comments.tsx       # Comments component
│   │   │   ├── comment-form.tsx          # Add comment form
│   │   │   ├── timeline-item.tsx         # Individual timeline item
│   │   │   ├── ticket-units.tsx          # Units list (many-to-many)
│   │   │   ├── unit-selector.tsx         # Multi-unit selector
│   │   │   └── ticket-filters.tsx
│   │   │
│   │   ├── units/
│   │   │   ├── unit-table.tsx
│   │   │   ├── unit-form.tsx
│   │   │   ├── unit-detail.tsx
│   │   │   └── warranty-badge.tsx
│   │   │
│   │   ├── preventive-maintenances/
│   │   │   ├── pm-table.tsx
│   │   │   ├── pm-form.tsx               # Form dengan multiple SNs
│   │   │   ├── pm-detail.tsx
│   │   │   ├── pm-progress.tsx           # Progress bar
│   │   │   ├── pm-tickets.tsx            # Linked tickets list
│   │   │   └── sn-selector.tsx           # Serial number selector
│   │   │
│   │   ├── loans/
│   │   │   ├── loan-table.tsx
│   │   │   ├── loan-form.tsx
│   │   │   ├── loan-detail.tsx
│   │   │   └── approval-actions.tsx
│   │   │
│   │   ├── dashboard/
│   │   │   ├── stats-card.tsx
│   │   │   ├── ticket-chart.tsx
│   │   │   ├── recent-tickets.tsx
│   │   │   └── notification-list.tsx
│   │   │
│   │   ├── notifications/
│   │   │   ├── notification-bell.tsx     # Notification indicator
│   │   │   └── notification-dropdown.tsx  # Notification dropdown
│   │   │
│   │   └── auth/
│   │       ├── login-form.tsx
│   │       ├── protected-route.tsx
│   │       ├── user-menu.tsx
│   │       └── user-avatar.tsx           # User profile avatar
│   │
│   ├── hooks/
│   │   ├── use-auth.ts
│   │   ├── use-tickets.ts
│   │   ├── use-ticket-comments.ts
│   │   ├── use-ticket-timeline.ts
│   │   ├── use-units.ts
│   │   ├── use-preventive-maintenances.ts
│   │   ├── use-loans.ts
│   │   ├── use-debounce.ts
│   │   ├── use-pagination.ts
│   │   ├── use-media-query.ts
│   │   └── use-websocket.ts              # WebSocket hook
│   │
│   ├── lib/
│   │   ├── api.ts
│   │   ├── auth.ts
│   │   ├── query-client.ts
│   │   ├── utils.ts
│   │   ├── constants.ts
│   │   ├── validators.ts                 # Zod schemas
│   │   └── websocket.ts                  # WebSocket client
│   │
│   ├── stores/
│   │   ├── auth-store.ts
│   │   ├── ui-store.ts
│   │   └── filter-store.ts
│   │
│   ├── types/
│   │   ├── api.ts
│   │   ├── models.ts
│   │   ├── auth.ts
│   │   └── index.ts
│   │
│   ├── i18n/                             # Internationalization
│   │   ├── en.json
│   │   ├── id.json
│   │   └── config.ts
│   │
│   └── middleware.ts
│
├── public/
├── .env.local
├── .env.example
├── next.config.js
├── tailwind.config.ts
├── tsconfig.json
├── package.json
└── README.md
```

---

## 2. State Management (Zustand)

### 2.1 Auth Store

```typescript
// src/stores/auth-store.ts

import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { User } from '@/types/auth';

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;

  setUser: (user: User | null) => void;
  setToken: (token: string | null) => void;
  login: (user: User, token: string) => void;
  logout: () => void;
  setLoading: (loading: boolean) => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: true,

      setUser: (user) => set({ user }),
      setToken: (token) => set({ token }),

      login: (user, token) =>
        set({ user, token, isAuthenticated: true, isLoading: false }),

      logout: () =>
        set({ user: null, token: null, isAuthenticated: false, isLoading: false }),

      setLoading: (isLoading) => set({ isLoading }),
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
);
```

### 2.2 UI Store

```typescript
// src/stores/ui-store.ts

import { create } from 'zustand';

interface UIState {
  sidebarOpen: boolean;
  theme: 'light' | 'dark';
  notifications: Notification[];
  locale: 'en' | 'id';

  toggleSidebar: () => void;
  setTheme: (theme: 'light' | 'dark') => void;
  addNotification: (notification: Notification) => void;
  removeNotification: (id: string) => void;
  clearNotifications: () => void;
  setLocale: (locale: 'en' | 'id') => void;
}

export const useUIStore = create<UIState>((set) => ({
  sidebarOpen: true,
  theme: 'light',
  notifications: [],
  locale: 'id',

  toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
  setTheme: (theme) => set({ theme }),

  addNotification: (notification) =>
    set((state) => ({ notifications: [...state.notifications, notification] })),

  removeNotification: (id) =>
    set((state) => ({ notifications: state.notifications.filter((n) => n.id !== id) })),

  clearNotifications: () => set({ notifications: [] }),
  setLocale: (locale) => set({ locale }),
}));
```

### 2.3 Filter Store

```typescript
// src/stores/filter-store.ts

import { create } from 'zustand';

interface FilterState {
  ticketFilters: {
    status: string;
    engineerId: string;
    dateFrom: string;
    dateTo: string;
    search: string;
  };
  unitFilters: {
    status: string;
    unitType: string;
    search: string;
  };
  loanFilters: {
    status: string;
    customerId: string;
  };

  setTicketFilters: (filters: Partial<FilterState['ticketFilters']>) => void;
  setUnitFilters: (filters: Partial<FilterState['unitFilters']>) => void;
  setLoanFilters: (filters: Partial<FilterState['loanFilters']>) => void;
  resetTicketFilters: () => void;
  resetUnitFilters: () => void;
  resetLoanFilters: () => void;
}

export const useFilterStore = create<FilterState>((set) => ({
  ticketFilters: { status: '', engineerId: '', dateFrom: '', dateTo: '', search: '' },
  unitFilters: { status: '', unitType: '', search: '' },
  loanFilters: { status: '', customerId: '' },

  setTicketFilters: (filters) =>
    set((state) => ({ ticketFilters: { ...state.ticketFilters, ...filters } })),

  setUnitFilters: (filters) =>
    set((state) => ({ unitFilters: { ...state.unitFilters, ...filters } })),

  setLoanFilters: (filters) =>
    set((state) => ({ loanFilters: { ...state.loanFilters, ...filters } })),

  resetTicketFilters: () =>
    set({ ticketFilters: { status: '', engineerId: '', dateFrom: '', dateTo: '', search: '' } }),

  resetUnitFilters: () =>
    set({ unitFilters: { status: '', unitType: '', search: '' } }),

  resetLoanFilters: () =>
    set({ loanFilters: { status: '', customerId: '' } }),
}));
```

---

## 3. API Integration Layer

### 3.1 Axios Instance

```typescript
// src/lib/api.ts

import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';
import { useAuthStore } from '@/stores/auth-store';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

export const apiClient = axios.create({
  baseURL: API_URL,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  withCredentials: true,
});

apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = useAuthStore.getState().token;
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
  },
  (error) => Promise.reject(error)
);

apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };

    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      useAuthStore.getState().logout();
      if (typeof window !== 'undefined') window.location.href = '/login';
      return Promise.reject(error);
    }
    return Promise.reject(error);
  }
);
```

### 3.2 TanStack Query Client

```typescript
// src/lib/query-client.ts

import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5,
      gcTime: 1000 * 60 * 30,
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});
```

### 3.3 WebSocket Client

```typescript
// src/lib/websocket.ts

import { useEffect, useRef } from 'react';
import { useAuthStore } from '@/stores/auth-store';

interface WebSocketMessage {
  event: string;
  data: any;
}

export function useWebSocket(channel: string, onMessage: (message: WebSocketMessage) => void) {
  const wsRef = useRef<WebSocket | null>(null);
  const token = useAuthStore((state) => state.token);

  useEffect(() => {
    if (!token) return;

    const wsUrl = `${process.env.NEXT_PUBLIC_WS_URL}/ws?token=${token}`;
    const ws = new WebSocket(wsUrl);

    ws.onopen = () => {
      console.log('WebSocket connected');
      ws.send(JSON.stringify({ type: 'subscribe', channel }));
    };

    ws.onmessage = (event) => {
      const message = JSON.parse(event.data);
      onMessage(message);
    };

    ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };

    ws.onclose = () => {
      console.log('WebSocket disconnected');
    };

    wsRef.current = ws;

    return () => {
      ws.close();
    };
  }, [token, channel, onMessage]);

  return wsRef;
}
```

### 3.4 API Hooks

#### useTickets

```typescript
// src/hooks/use-tickets.ts

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/lib/api';
import { Ticket, TicketListResponse, TicketCreateInput } from '@/types/models';

const TICKETS_KEY = 'tickets';

export function useTickets(params?: Record<string, string>) {
  return useQuery({
    queryKey: [TICKETS_KEY, params],
    queryFn: async () => {
      const { data } = await apiClient.get<TicketListResponse>('/tickets', { params });
      return data;
    },
  });
}

export function useTicket(ticketId: string) {
  return useQuery({
    queryKey: [TICKETS_KEY, ticketId],
    queryFn: async () => {
      const { data } = await apiClient.get<{ data: Ticket }>(`/tickets/${ticketId}`);
      return data.data;
    },
    enabled: !!ticketId,
  });
}

export function useCreateTicket() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (input: TicketCreateInput) => {
      const { data } = await apiClient.post('/tickets', input);
      return data;
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: [TICKETS_KEY] }),
  });
}

export function useUpdateTicketStatus() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ ticketId, status, remark }: { ticketId: string; status: string; remark?: string }) => {
      const { data } = await apiClient.post(`/tickets/${ticketId}/status`, { status, remark });
      return data;
    },
    onMutate: async ({ ticketId, status }) => {
      await queryClient.cancelQueries({ queryKey: [TICKETS_KEY, ticketId] });
      const previousTicket = queryClient.getQueryData([TICKETS_KEY, ticketId]);
      queryClient.setQueryData([TICKETS_KEY, ticketId], (old: any) => ({ ...old, ticket_status: status }));
      return { previousTicket };
    },
    onError: (err, variables, context) => {
      if (context?.previousTicket) {
        queryClient.setQueryData([TICKETS_KEY, variables.ticketId], context.previousTicket);
      }
    },
    onSettled: () => queryClient.invalidateQueries({ queryKey: [TICKETS_KEY] }),
  });
}

export function useAssignEngineer() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ ticketId, engineerId }: { ticketId: string; engineerId: string }) => {
      const { data } = await apiClient.post(`/tickets/${ticketId}/assign`, { engineer_id: engineerId });
      return data;
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: [TICKETS_KEY] }),
  });
}
```

#### useTicketComments

```typescript
// src/hooks/use-ticket-comments.ts

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/lib/api';

const COMMENTS_KEY = 'ticket-comments';

export function useTicketComments(ticketId: string) {
  return useQuery({
    queryKey: [COMMENTS_KEY, ticketId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/tickets/${ticketId}/comments`);
      return data.data;
    },
    enabled: !!ticketId,
  });
}

export function useAddTicketComment() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ ticketId, content, type, isInternal }: {
      ticketId: string; content: string; type?: string; isInternal?: boolean;
    }) => {
      const { data } = await apiClient.post(`/tickets/${ticketId}/comments`, {
        content, type, is_internal: isInternal,
      });
      return data;
    },
    onSuccess: (_, { ticketId }) => {
      queryClient.invalidateQueries({ queryKey: [COMMENTS_KEY, ticketId] });
      queryClient.invalidateQueries({ queryKey: ['ticket-timeline', ticketId] });
    },
  });
}
```

#### useTicketTimeline

```typescript
// src/hooks/use-ticket-timeline.ts

import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@/lib/api';

const TIMELINE_KEY = 'ticket-timeline';

export function useTicketTimeline(ticketId: string) {
  return useQuery({
    queryKey: [TIMELINE_KEY, ticketId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/tickets/${ticketId}/timeline`);
      return data.data;
    },
    enabled: !!ticketId,
  });
}
```

#### usePreventiveMaintenances

```typescript
// src/hooks/use-preventive-maintenances.ts

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@/lib/api';

const PM_KEY = 'preventive-maintenances';

export function usePreventiveMaintenances(params?: Record<string, string>) {
  return useQuery({
    queryKey: [PM_KEY, params],
    queryFn: async () => {
      const { data } = await apiClient.get('/preventive-maintenances', { params });
      return data;
    },
  });
}

export function usePreventiveMaintenance(pmId: string) {
  return useQuery({
    queryKey: [PM_KEY, pmId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/preventive-maintenances/${pmId}`);
      return data.data;
    },
    enabled: !!pmId,
  });
}

export function useCreatePreventiveMaintenance() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (input: any) => {
      const { data } = await apiClient.post('/preventive-maintenances', input);
      return data;
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: [PM_KEY] }),
  });
}

export function usePMTickets(pmId: string) {
  return useQuery({
    queryKey: [PM_KEY, pmId, 'tickets'],
    queryFn: async () => {
      const { data } = await apiClient.get(`/preventive-maintenances/${pmId}/tickets`);
      return data.data;
    },
    enabled: !!pmId,
  });
}

export function usePMProgress(pmId: string) {
  return useQuery({
    queryKey: [PM_KEY, pmId, 'progress'],
    queryFn: async () => {
      const { data } = await apiClient.get(`/preventive-maintenances/${pmId}/progress`);
      return data.data;
    },
    enabled: !!pmId,
  });
}
```

---

## 4. Form Validation (Zod Schemas)

```typescript
// src/lib/validators.ts

import { z } from 'zod';

export const ticketSchema = z.object({
  ticket_no: z.string().max(50).optional(),
  ticket_date: z.string().optional(),
  customer_id: z.string().min(1, 'Customer is required'),
  engineer_id: z.string().optional(),
  ticket_status: z.enum(['1', '2', '10', '12', '13', '14']).optional(),
  sla: z.enum(['1', '2', '3']).optional(),
  problem_desc: z.string().max(5000).optional(),
  unit_ids: z.array(z.string()).optional(),
});

export const unitSchema = z.object({
  unit_id: z.string().min(1, 'Unit ID is required'),
  serial_no: z.string().min(1, 'Serial number is required'),
  unit_type: z.string().min(1, 'Unit type is required'),
  model_type: z.string().optional(),
  customer_id: z.string().min(1, 'Customer is required'),
  warranty_start: z.string().optional(),
  warranty_end: z.string().optional(),
  install_date: z.string().optional(),
  status: z.enum(['ACTIVE', 'INACTIVE', 'RETIRED']).optional(),
});

export const pmSchema = z.object({
  pm_type: z.enum(['monthly', 'quarterly', 'annual']),
  customer_id: z.string().min(1, 'Customer is required'),
  engineer_id: z.string().optional(),
  scheduled_date: z.string().min(1, 'Scheduled date is required'),
  description: z.string().max(5000).optional(),
  checklist: z.array(z.string()).optional(),
  serial_numbers: z.array(z.string()).min(1, 'At least one serial number is required'),
});

export const commentSchema = z.object({
  content: z.string().min(1, 'Content is required').max(5000),
  type: z.enum(['comment', 'note', 'status_change']).optional(),
  is_internal: z.boolean().optional(),
});

export const loginSchema = z.object({
  username: z.string().min(1, 'Username is required'),
  password: z.string().min(1, 'Password is required'),
});

export type TicketInput = z.infer<typeof ticketSchema>;
export type UnitInput = z.infer<typeof unitSchema>;
export type PMInput = z.infer<typeof pmSchema>;
export type CommentInput = z.infer<typeof commentSchema>;
export type LoginInput = z.infer<typeof loginSchema>;
```

---

## 5. Internationalization (i18n)

```typescript
// src/i18n/config.ts

export const i18n = {
  defaultLocale: 'id',
  locales: ['en', 'id'],
} as const;

export type Locale = (typeof i18n)['locales'][number];
```

```json
// src/i18n/id.json
{
  "common": {
    "loading": "Memuat...",
    "error": "Terjadi kesalahan",
    "save": "Simpan",
    "cancel": "Batal",
    "delete": "Hapus",
    "edit": "Edit",
    "create": "Buat",
    "search": "Cari",
    "filter": "Filter",
    "export": "Ekspor",
    "import": "Impor",
    "yes": "Ya",
    "no": "Tidak",
    "confirm": "Konfirmasi",
    "back": "Kembali",
    "next": "Lanjut",
    "previous": "Sebelumnya",
    "submit": "Kirim",
    "reset": "Reset",
    "close": "Tutup",
    "open": "Buka",
    "view": "Lihat",
    "actions": "Aksi",
    "status": "Status",
    "date": "Tanggal",
    "name": "Nama",
    "description": "Deskripsi",
    "type": "Tipe",
    "total": "Total",
    "actions": "Aksi"
  },
  "auth": {
    "login": "Masuk",
    "logout": "Keluar",
    "username": "Username",
    "password": "Kata Sandi",
    "forgot_password": "Lupa Kata Sandi",
    "login_failed": "Login gagal",
    "invalid_credentials": "Username atau kata sandi salah"
  },
  "tickets": {
    "title": "Tiket",
    "create": "Buat Tiket",
    "edit": "Edit Tiket",
    "detail": "Detail Tiket",
    "list": "Daftar Tiket",
    "ticket_no": "No Tiket",
    "ticket_date": "Tanggal Tiket",
    "customer": "Pelanggan",
    "engineer": "Teknisi",
    "status": "Status",
    "sla": "SLA",
    "problem_desc": "Deskripsi Masalah",
    "units": "Unit",
    "comments": "Komentar",
    "timeline": "Linimasa",
    "add_comment": "Tambah Komentar",
    "add_unit": "Tambah Unit",
    "remove_unit": "Hapus Unit",
    "status_new": "Baru",
    "status_analyzing": "Menganalisis",
    "status_pending_part": "Menunggu Part",
    "status_solved": "Selesai",
    "status_closed": "Ditutup",
    "status_cancelled": "Dibatalkan"
  },
  "pm": {
    "title": "Pemeliharaan Preventif",
    "create": "Buat PM",
    "edit": "Edit PM",
    "detail": "Detail PM",
    "list": "Daftar PM",
    "pm_no": "No PM",
    "pm_type": "Tipe PM",
    "scheduled_date": "Tanggal Dijadwalkan",
    "status": "Status",
    "progress": "Progres",
    "tickets": "Tiket",
    "generate_tickets": "Buat Tiket",
    "serial_numbers": "Nomor Seri"
  },
  "units": {
    "title": "Unit",
    "create": "Buat Unit",
    "edit": "Edit Unit",
    "detail": "Detail Unit",
    "list": "Daftar Unit",
    "unit_id": "ID Unit",
    "serial_no": "No Seri",
    "unit_type": "Tipe Unit",
    "model_type": "Model",
    "warranty": "Garansi",
    "warranty_start": "Mulai Garansi",
    "warranty_end": "Akhir Garansi",
    "status": "Status"
  },
  "dashboard": {
    "title": "Dasbor",
    "open_tickets": "Tiket Terbuka",
    "active_loans": "Pinjaman Aktif",
    "active_units": "Unit Aktif",
    "unread_notifications": "Notifikasi Belum Dibaca",
    "recent_tickets": "Tiket Terbaru",
    "ticket_trends": "Tren Tiket"
  }
}
```

---

## 6. Auth State Management

### 6.1 Auth Hook

```typescript
// src/hooks/use-auth.ts

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/stores/auth-store';
import { apiClient } from '@/lib/api';

export function useAuth() {
  const router = useRouter();
  const { user, token, isAuthenticated, isLoading, login, logout, setLoading } = useAuthStore();

  useEffect(() => {
    const initAuth = async () => {
      const storedToken = useAuthStore.getState().token;
      if (!storedToken) { setLoading(false); return; }
      try {
        const { data } = await apiClient.get('/auth/me');
        useAuthStore.getState().setUser(data);
        setLoading(false);
      } catch { logout(); setLoading(false); }
    };
    initAuth();
  }, []);

  const handleLogin = async (username: string, password: string) => {
    const { data } = await apiClient.post('/auth/login', { username, password });
    login(data.user, data.token);
    router.push('/dashboard');
  };

  const handleLogout = async () => {
    try { await apiClient.post('/auth/logout'); } catch {}
    logout();
    router.push('/login');
  };

  return { user, token, isAuthenticated, isLoading, login: handleLogin, logout: handleLogout };
}
```

### 6.2 Protected Route Component

```typescript
// src/components/auth/protected-route.tsx

'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/hooks/use-auth';
import { LoadingSpinner } from '@/components/ui/loading-spinner';

interface ProtectedRouteProps {
  children: React.ReactNode;
  requiredRole?: string;
}

export function ProtectedRoute({ children, requiredRole }: ProtectedRouteProps) {
  const router = useRouter();
  const { isAuthenticated, isLoading, user } = useAuth();

  useEffect(() => {
    if (!isLoading && !isAuthenticated) router.push('/login');
  }, [isLoading, isAuthenticated, router]);

  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  if (!isAuthenticated) return null;

  if (requiredRole && user?.role !== requiredRole) {
    return (
      <div className="flex h-screen items-center justify-center">
        <p className="text-red-500">Access denied. Insufficient permissions.</p>
      </div>
    );
  }

  return <>{children}</>;
}
```

### 6.3 Next.js Middleware

```typescript
// src/middleware.ts

import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

const PUBLIC_PATHS = ['/login', '/forgot-password'];
const AUTH_PATHS = ['/login'];

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const token = request.cookies.get('auth-storage')?.value;

  if (PUBLIC_PATHS.some((path) => pathname.startsWith(path))) {
    if (token && AUTH_PATHS.some((path) => pathname.startsWith(path))) {
      return NextResponse.redirect(new URL('/dashboard', request.url));
    }
    return NextResponse.next();
  }

  if (!token) {
    const loginUrl = new URL('/login', request.url);
    loginUrl.searchParams.set('redirect', pathname);
    return NextResponse.redirect(loginUrl);
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/((?!_static|_next/image|favicon.ico|public).*)'],
};
```

---

## 7. Error Handling & Loading States

### 7.1 Global Error Boundary

```typescript
// src/app/error.tsx

'use client';

import { useEffect } from 'react';
import { Button } from '@/components/ui/button';

export default function Error({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  useEffect(() => { console.error(error); }, [error]);

  return (
    <div className="flex h-screen flex-col items-center justify-center">
      <h2 className="text-2xl font-bold text-red-600">Something went wrong!</h2>
      <p className="mt-2 text-gray-600">{error.message}</p>
      <Button onClick={reset} className="mt-4">Try again</Button>
    </div>
  );
}
```

### 7.2 Loading State

```typescript
// src/app/(dashboard)/loading.tsx

import { LoadingSpinner } from '@/components/ui/loading-spinner';

export default function Loading() {
  return (
    <div className="flex h-screen items-center justify-center">
      <LoadingSpinner size="lg" />
    </div>
  );
}
```

### 7.3 Empty State Component

```typescript
// src/components/ui/empty-state.tsx

interface EmptyStateProps {
  title: string;
  description?: string;
  action?: React.ReactNode;
}

export function EmptyState({ title, description, action }: EmptyStateProps) {
  return (
    <div className="flex flex-col items-center justify-center py-12">
      <p className="text-lg font-medium text-gray-900">{title}</p>
      {description && <p className="mt-1 text-sm text-gray-500">{description}</p>}
      {action && <div className="mt-4">{action}</div>}
    </div>
  );
}
```

---

## 8. TypeScript Types

### 8.1 API Types

```typescript
// src/types/api.ts

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface ApiError {
  success: boolean;
  message: string;
  errors?: Record<string, string[]>;
}
```

### 8.2 Model Types

```typescript
// src/types/models.ts

export interface Ticket {
  id: number;
  ticket_no: string;
  ticket_date: string;
  customer: { id: string; name: string };
  units: Array<{ id: number; unit_id: string; serial_no: string; unit_type: string }>;
  engineer: { id: string; name: string } | null;
  problem_desc: string;
  ticket_status: string;
  sla: string;
  created_at: string;
  updated_at: string;
  closed_at: string | null;
  close_remark: string | null;
}

export interface TicketComment {
  id: number;
  ticket_id: number;
  user_id: number;
  content: string;
  type: 'comment' | 'note' | 'status_change';
  is_internal: boolean;
  user: { id: number; name: string };
  created_at: string;
}

export interface TicketTimelineEvent {
  id: number;
  ticket_id: number;
  event_type: 'created' | 'status_changed' | 'comment_added' | 'assigned' | 'unit_added' | 'unit_removed';
  title: string;
  description: string | null;
  metadata: Record<string, any> | null;
  user: { id: number; name: string } | null;
  occurred_at: string;
}

export interface Unit {
  id: number;
  unit_id: string;
  serial_no: string;
  unit_type: string;
  model_type: string;
  customer: { id: string; name: string };
  warranty: { warranty_type: string; warranty_start: string; warranty_end: string; warranty_status: string } | null;
  status: string;
  install_date: string;
  created_at: string;
}

export interface PreventiveMaintenance {
  id: number;
  pm_no: string;
  pm_type: 'monthly' | 'quarterly' | 'annual';
  customer: { id: string; name: string };
  engineer: { id: string; name: string } | null;
  scheduled_date: string;
  completed_date: string | null;
  status: 'PLANNED' | 'IN_PROGRESS' | 'COMPLETED' | 'CANCELLED';
  description: string | null;
  checklist: string[] | null;
  tickets_count: number;
  created_at: string;
}

export interface PMProgress {
  total: number;
  completed: number;
  pending: number;
  cancelled: number;
  progress_percentage: number;
}

export interface Loan {
  id: number;
  loan_no: string;
  ticket_id: number;
  unit: { id: number; serial_no: string } | null;
  customer: { id: number; name: string };
  loan_date: string;
  return_date: string | null;
  loan_status: string;
  remark: string | null;
  approvals: Array<{ id: number; approval_status: string; approved_by: string; approval_date: string; remark: string | null }>;
  created_at: string;
}

export interface Customer {
  id: string;
  customer_id: string;
  customer_name: string;
  customer_type: string;
  address: string;
  city: string;
  province: string;
  phone: string;
  email: string;
  contact_person: string;
}

export interface TicketListResponse {
  data: Ticket[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface UnitListResponse {
  data: Unit[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface LoanListResponse {
  data: Loan[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface TicketCreateInput {
  ticket_no?: string;
  ticket_date?: string;
  customer_id?: string;
  engineer_id?: string;
  ticket_status?: string;
  sla?: string;
  problem_desc?: string;
  unit_ids?: string[];
}
```

### 8.3 Auth Types

```typescript
// src/types/auth.ts

export interface User {
  user_id: string;
  username: string;
  name: string;
  role: string;
  office_id: string | null;
  office_name?: string;
  engineer_name?: string;
}

export interface LoginResponse {
  token: string;
  user: User;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
}
```

---

## 9. Root Layout with Providers

```typescript
// src/app/layout.tsx

import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import { Providers } from './providers';
import './globals.css';

const inter = Inter({ subsets: ['latin'] });

export const metadata: Metadata = {
  title: 'S3 System - Fujitsu Support & Service',
  description: 'Fujitsu Indonesia Support & Service Management System',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="id">
      <body className={inter.className}>
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
```

```typescript
// src/app/providers.tsx

'use client';

import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/lib/query-client';

export function Providers({ children }: { children: React.ReactNode }) {
  return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
}
```

---

## 10. Environment Variables

```env
# .env.local
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_APP_NAME=S3 System
NEXT_PUBLIC_WS_URL=ws://localhost:8080
```

---

## 11. Package Dependencies

```json
{
  "dependencies": {
    "next": "^14.2.0",
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "axios": "^1.6.0",
    "@tanstack/react-query": "^5.0.0",
    "zustand": "^4.5.0",
    "tailwindcss": "^3.4.0",
    "clsx": "^2.1.0",
    "tailwind-merge": "^2.2.0",
    "lucide-react": "^0.300.0",
    "date-fns": "^3.0.0",
    "react-hook-form": "^7.49.0",
    "@hookform/resolvers": "^3.3.0",
    "zod": "^3.22.0",
    "next-intl": "^3.0.0"
  },
  "devDependencies": {
    "typescript": "^5.3.0",
    "@types/node": "^20.11.0",
    "@types/react": "^18.2.0",
    "@types/react-dom": "^18.2.0",
    "eslint": "^8.56.0",
    "eslint-config-next": "^14.2.0",
    "prettier": "^3.2.0"
  }
}
```

---

## 12. State Machine Visualization (dari System Architecture Flow)

### 12.1 Ticket Status Flow (15 States)

```
New → Analyzing → InProgress → Dispatching → WaitingPart → Solved → Closed
                  ↓           ↓              ↓
                  Cancelled   SLA Paused     Escalation3rdParty
                              (5 states)
```

### 12.2 Status Categories

| Category | States | Color |
|---|---|---|
| **Active** | New, Analyzing, InProgress, Dispatching, WaitingPart, Escalation3rdParty | Blue |
| **SLA Paused** | WaitingCustomerFeedback, WaitingCustomerSchedule, InternalQuotation, WaitingCustomerPO, Pending | Orange |
| **Closed** | Solved, Closed, Cancelled, PickupAfterNotResolve | Green/Gray |

### 12.3 SLA Tier Display

| Tier | Response Time | Color |
|---|---|---|
| Gold | ≤ 4 hours | Red |
| Standard | Next Business Day | Orange |
| Carry-in | 7 business days | Blue |
| Non-Warranty | 30 business days | Gray |
| Other Contracts | 14 business days | Purple |

### 12.4 Validation Gate UI

| Target Status | UI Validation |
|---|---|
| Solved (Incident) | Resolution ≥ 11 chars, ≥ 1 Category, all parts final, Resolve flag |
| Solved (Request) | Resolution ≥ 11 chars, all parts final |
| Solved (PM) | Resolution filled |
| Solved (Inquiry) | No prerequisites |
| Closed | Activity Report + CSR/RRF/Repair Tag uploaded |
| Cancelled | Only from Analyzing + structural input error |

## 13. RBAC-Based UI Rendering

### 13.1 Role-Based Component Visibility

| Component | Front Desk | Leader | Engineer | ASP | Customer |
|---|---|---|---|---|---|
| Create Ticket | ✅ | ❌ | ❌ | ⚠️ Carry-in | ❌ |
| Admit/Decline | ✅ | ❌ | ❌ | ❌ | ❌ |
| Reassign | ✅ | ✅ | ❌ | ❌ | ❌ |
| Request Spare Part | ✅ | ✅ | ✅ | ✅ | ❌ |
| Solve Ticket | ❌ | ❌ | ✅ | ✅ | ❌ |
| Close/Cancel | ✅ | ❌ | ❌ | ❌ | ❌ |
| Task Scheduling | ❌ | ✅ | ✅ | ❌ | ❌ |

### 13.2 Permission Hook

```typescript
// src/hooks/use-permission.ts

import { useAuth } from './use-auth';

type Permission = 'ticket.create' | 'ticket.edit' | 'ticket.solve' | 'ticket.close' | 'loan.approve';

export function usePermission(permission: Permission): boolean {
  const { user } = useAuth();
  if (!user) return false;

  const rolePermissions: Record<string, Permission[]> = {
    admin: ['ticket.create', 'ticket.edit', 'ticket.solve', 'ticket.close', 'loan.approve'],
    manager: ['ticket.create', 'ticket.edit', 'ticket.solve', 'ticket.close', 'loan.approve'],
    engineer: ['ticket.edit', 'ticket.solve'],
    staff: ['ticket.create', 'ticket.edit'],
    external: ['ticket.view'],
  };

  return rolePermissions[user.role]?.includes(permission) ?? false;
}
```

## 14. Business Flow Integration

### 14.1 Ticket Ingest Flow
- Customer Portal → Submit Request → Front Desk Admit/Decline
- Call/Email/Walk-in → Front Desk Create Ticket → Auto-populate from Serial Number

### 14.2 Assignment Flow
- Front Desk → Analyzing → Dispatching → Assign to Engineer
- Multi-Engineer: Create Reference Ticket (Sub-ticket)

### 14.3 Spare Part Lifecycle
- Request Part → WaitingPart → Logistic Allocate → Engineer Receive → Return Status Update

### 14.4 Quotation Flow (Non-Warranty)
- InternalQuotation → WaitingCustomerPO → Customer PO → InProgress
- Customer Reject → Solved (Resolve=No) → PickupAfterNotResolve

### 14.5 Closure Flow
- Solved → Upload CSR/RRF → Activity Report → Closed

## 15. Notes

- **SQL injection fix di legacy di-skip** — aplikasi sedang dijadikan staging untuk production errors
- **Optimistic updates** — TanStack Query mutations pakai optimistic update untuk UX yang lebih baik
- **Error boundary** — `error.tsx` dan `loading.tsx` untuk setiap route group
- **Form validation** — Zod schema validation di client-side
- **Many-to-many** — ticket-unit relationship via `ticket_units` junction table
- **PM interface** — create PM dengan multiple SNs, track progress
- **Comments & Timeline** — setiap ticket punya conversation thread dan audit trail
- **WebSocket** — real-time updates via Laravel Echo + Pusher/Soketi
- **i18n** — multi-language support via next-intl
- **Dark mode** — theme switching via Zustand
- **Mobile responsive** — Tailwind CSS responsive design
- **State Machine** — 15 status dengan visual flow
- **Validation Gates** — UI validation sebelum status transition
- **RBAC UI** — component visibility berdasarkan role

---

*Spesifikasi ini dapat dilanjutkan dengan deployment strategy dan testing plan.*
