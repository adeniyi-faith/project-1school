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

export async function apiGet<T>(path: string): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    headers: { Accept: "application/json", ...(await authHeaders()) },
  });

  if (!response.ok) {
    throw new Error(`API request failed: ${response.status} ${response.statusText}`);
  }

  return response.json() as Promise<T>;
}
