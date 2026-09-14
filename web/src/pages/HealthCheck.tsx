import { useEffect, useState } from "react";
import type { Session } from "@supabase/supabase-js";
import { apiGet } from "../lib/api";
import { supabase } from "../lib/supabaseClient";

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

  return (
    <main style={{ fontFamily: "sans-serif", padding: "2rem", maxWidth: 480 }}>
      <h1>SchoolRuns</h1>

      <section>
        <h2>1. Web app ↔ Laravel API</h2>
        {healthError && <p style={{ color: "crimson" }}>API error: {healthError}</p>}
        {!healthError && !health && <p>Checking API connection...</p>}
        {health && (
          <p style={{ color: "green" }}>
            API says: {health.status} ({health.service})
          </p>
        )}
      </section>

      <section>
        <h2>2. Supabase login</h2>
        {!session && (
          <form onSubmit={handleSignIn}>
            <div>
              <input
                type="email"
                placeholder="Email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>
            <div>
              <input
                type="password"
                placeholder="Password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>
            <button type="submit">Sign in</button>
            {authError && <p style={{ color: "crimson" }}>{authError}</p>}
          </form>
        )}
        {session && (
          <div>
            <p style={{ color: "green" }}>Signed in as {session.user.email}</p>
            <button type="button" onClick={handleSignOut}>
              Sign out
            </button>
          </div>
        )}
      </section>

      {session && (
        <section>
          <h2>3. Laravel checking the Supabase login</h2>
          {meError && <p style={{ color: "crimson" }}>API error: {meError}</p>}
          {!meError && !me && <p>Checking with the API...</p>}
          {me && (
            <p style={{ color: "green" }}>
              Laravel sees you as: {me.supabase_user_email} ({me.supabase_user_id})
            </p>
          )}
        </section>
      )}
    </main>
  );
}
