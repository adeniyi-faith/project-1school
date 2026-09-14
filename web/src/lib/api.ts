import { supabase } from "./supabaseClient";

// Central place that knows how to talk to the Laravel backend.
// The base URL comes from an environment variable so it can change
// per environment (local dev, staging, production) without code changes.
const API_URL = import.meta.env.VITE_API_URL as string;

// Attaches the current Supabase login token (if any) so Laravel can
// verify who's asking. A logged-out visitor can still call public
// endpoints like /health without one.
async function authHeaders(): Promise<HeadersInit> {
  const { data } = await supabase.auth.getSession();
  const token = data.session?.access_token;
  return token ? { Authorization: `Bearer ${token}` } : {};
}

// Laravel's validation errors (422) come back as { message, errors: { field: [msgs] } }.
export class ApiError extends Error {
  status: number;
  errors?: Record<string, string[]>;

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

async function handleResponse<T>(response: Response): Promise<T> {
  const body = await response.json().catch(() => null);

  if (!response.ok) {
    throw new ApiError(
      body?.message ?? `API request failed: ${response.status}`,
      response.status,
      body?.errors,
    );
  }

  return body as T;
}

export async function apiGet<T>(path: string): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    headers: { Accept: "application/json", ...(await authHeaders()) },
  });
  return handleResponse<T>(response);
}

export async function apiPost<T>(path: string, data: unknown): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(await authHeaders()),
    },
    body: JSON.stringify(data),
  });
  return handleResponse<T>(response);
}
