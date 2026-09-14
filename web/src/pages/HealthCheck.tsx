import { useEffect, useState } from "react";
import type { Session } from "@supabase/supabase-js";
import { apiGet } from "../lib/api";
import { supabase } from "../lib/supabaseClient";
import "./HealthCheck.css";

type HealthResponse = {
  status: string;
  service: string;
};

type MeResponse = {
  supabase_user_id: string;
  supabase_user_email: string | null;
};

// A small page that proves the web app, Supabase login, and the
// Laravel API all work together. It's a starting point for the real
// dashboard, not the final product.
export function HealthCheck() {
  const [health, setHealth] = useState<HealthResponse | null>(null);
  const [healthError, setHealthError] = useState<string | null>(null);

  const [session, setSession] = useState<Session | null>(null);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [authError, setAuthError] = useState<string | null>(null);

  const [me, setMe] = useState<MeResponse | null>(null);
  const [meError, setMeError] = useState<string | null>(null);

  useEffect(() => {
    apiGet<HealthResponse>("/health")
      .then(setHealth)
      .catch((err: Error) => setHealthError(err.message));
  }, []);

  useEffect(() => {
    supabase.auth.getSession().then(({ data }) => setSession(data.session));
    const { data: listener } = supabase.auth.onAuthStateChange((_event, newSession) => {
      setSession(newSession);
    });
    return () => listener.subscription.unsubscribe();
  }, []);

  useEffect(() => {
    if (!session) {
      setMe(null);
      return;
    }
    apiGet<MeResponse>("/me")
      .then(setMe)
      .catch((err: Error) => setMeError(err.message));
  }, [session]);

  async function handleSignIn(e: React.FormEvent) {
    e.preventDefault();
    setAuthError(null);
    const { error } = await supabase.auth.signInWithPassword({ email, password });
    if (error) setAuthError(error.message);
  }

  async function handleSignOut() {
    await supabase.auth.signOut();
  }

  const apiStatus = healthError ? "error" : health ? "ok" : "pending";

  return (
    <main className="page">
      <div className="brand">SchoolRuns</div>

      <div className="card">
        <div className="card-title">API connection</div>
        <div className="status">
          <span className={`status-dot ${apiStatus}`} />
          {healthError && <span>Can&apos;t reach the API</span>}
          {!healthError && !health && <span>Checking...</span>}
          {health && (
            <span>
              {health.status} ({health.service})
            </span>
          )}
        </div>
      </div>

      <div className="card">
        <div className="card-title">Sign in</div>
        {!session && (
          <form onSubmit={handleSignIn}>
            <div className="field">
              <input
                type="email"
                placeholder="Email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>
            <div className="field">
              <input
                type="password"
                placeholder="Password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>
            <button type="submit" className="btn">
              Sign in
            </button>
            {authError && <p className="error-text">{authError}</p>}
          </form>
        )}
        {session && (
          <div className="signed-in-row">
            <span className="signed-in-email">{session.user.email}</span>
            <button type="button" className="btn btn-secondary" onClick={handleSignOut}>
              Sign out
            </button>
          </div>
        )}
      </div>

      {session && (
        <div className="card">
          <div className="card-title">API sees you as</div>
          <div className="status">
            <span className={`status-dot ${meError ? "error" : me ? "ok" : "pending"}`} />
            {meError && <span>Can&apos;t verify with the API</span>}
            {!meError && !me && <span>Checking...</span>}
            {me && <span>{me.supabase_user_email}</span>}
          </div>
        </div>
      )}
    </main>
  );
}
