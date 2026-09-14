import { useEffect, useState } from "react";
import type { Session } from "@supabase/supabase-js";
import { apiGet, apiPost, ApiError } from "../lib/api";
import { supabase } from "../lib/supabaseClient";
import "./AuthScreen.css";

type HealthResponse = {
  status: string;
  service: string;
};

type SchoolInfo = { code: string; name: string };

type MeResponse = {
  supabase_user_email: string | null;
  role: string | null;
  school: SchoolInfo | null;
};

// The school and admin name a person typed at sign-up, kept just long
// enough to finish registering once they've confirmed their email and
// come back to sign in.
const PENDING_REGISTRATION_KEY = "schoolruns_pending_registration";

type PendingRegistration = { schoolName: string; adminName: string };

function savePendingRegistration(data: PendingRegistration) {
  localStorage.setItem(PENDING_REGISTRATION_KEY, JSON.stringify(data));
}

function takePendingRegistration(): PendingRegistration | null {
  const raw = localStorage.getItem(PENDING_REGISTRATION_KEY);
  if (!raw) return null;
  localStorage.removeItem(PENDING_REGISTRATION_KEY);
  return JSON.parse(raw) as PendingRegistration;
}

export function AuthScreen() {
  const [health, setHealth] = useState<HealthResponse | null>(null);
  const [healthError, setHealthError] = useState<string | null>(null);

  const [session, setSession] = useState<Session | null>(null);
  const [me, setMe] = useState<MeResponse | null>(null);
  const [mode, setMode] = useState<"sign-in" | "register">("register");

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [schoolName, setSchoolName] = useState("");
  const [adminName, setAdminName] = useState("");
  const [formError, setFormError] = useState<string | null>(null);
  const [confirmEmailNotice, setConfirmEmailNotice] = useState(false);
  const [submitting, setSubmitting] = useState(false);

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

  // Whenever we're signed in, ask the API what it knows about this
  // account: a school already registered, or none yet. If a
  // registration was left pending an email confirmation, finish it now.
  useEffect(() => {
    if (!session) {
      setMe(null);
      return;
    }

    (async () => {
      const pending = takePendingRegistration();
      if (pending) {
        await registerSchool(pending.schoolName, pending.adminName);
        return;
      }

      try {
        setMe(await apiGet<MeResponse>("/me"));
      } catch (err) {
        setFormError(err instanceof ApiError ? err.message : "Couldn't reach the API.");
      }
    })();
  }, [session]);

  async function registerSchool(schoolNameValue: string, adminNameValue: string) {
    setFormError(null);
    try {
      const result = await apiPost<{ school: SchoolInfo; user: { role: string } }>("/schools", {
        school_name: schoolNameValue,
        admin_name: adminNameValue,
      });
      setMe((prev) => ({
        supabase_user_email: prev?.supabase_user_email ?? null,
        role: result.user.role,
        school: result.school,
      }));
    } catch (err) {
      setFormError(err instanceof ApiError ? err.message : "Something went wrong.");
    }
  }

  async function handleRegister(e: React.FormEvent) {
    e.preventDefault();
    setFormError(null);
    setSubmitting(true);
    try {
      const { data, error } = await supabase.auth.signUp({ email, password });
      if (error) {
        setFormError(error.message);
        return;
      }
      if (data.session) {
        await registerSchool(schoolName, adminName);
      } else {
        // Supabase requires the email to be confirmed before it hands
        // out a session. Remember what to do once they come back.
        savePendingRegistration({ schoolName, adminName });
        setConfirmEmailNotice(true);
      }
    } finally {
      setSubmitting(false);
    }
  }

  async function handleSignIn(e: React.FormEvent) {
    e.preventDefault();
    setFormError(null);
    const { error } = await supabase.auth.signInWithPassword({ email, password });
    if (error) setFormError(error.message);
  }

  async function handleSignOut() {
    setMe(null);
    await supabase.auth.signOut();
  }

  const apiStatus = healthError ? "error" : health ? "ok" : "pending";

  return (
    <main className="page">
      <div className="page-blob" />
      <div className="content">
        <div className="brand">SchoolRuns</div>
        <div className="tagline">Run your school's day-to-day, all in one place.</div>

        <div className="card">
          <div className="card-title">API CONNECTION</div>
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

        {session && me?.school && (
          <div className="card">
            <div className="card-title">YOUR SCHOOL</div>
            <p className="success-text">
              {me.school.name} — School ID <span className="school-id-badge">{me.school.code}</span>
            </p>
          </div>
        )}

        {session && !me?.school && (
          <div className="card">
            <div className="card-title">SIGNED IN</div>
            <div className="signed-in-row">
              <span className="signed-in-email">{session.user.email}</span>
              <button type="button" className="btn btn-secondary" onClick={handleSignOut}>
                Sign out
              </button>
            </div>
            {formError && <p className="error-text">{formError}</p>}
          </div>
        )}

        {!session && (
          <div className="card">
            <div className="tabs">
              <button
                type="button"
                className={mode === "register" ? "tab active" : "tab"}
                onClick={() => setMode("register")}
              >
                Register a school
              </button>
              <button
                type="button"
                className={mode === "sign-in" ? "tab active" : "tab"}
                onClick={() => setMode("sign-in")}
              >
                Sign in
              </button>
            </div>

            {confirmEmailNotice ? (
              <p className="success-text">
                Check your email for a confirmation link. Once confirmed, come back and sign in to
                finish setting up your school.
              </p>
            ) : (
              <form onSubmit={mode === "register" ? handleRegister : handleSignIn}>
                {mode === "register" && (
                  <>
                    <div className="field">
                      <input
                        type="text"
                        placeholder="School name"
                        value={schoolName}
                        onChange={(e) => setSchoolName(e.target.value)}
                        required
                      />
                    </div>
                    <div className="field">
                      <input
                        type="text"
                        placeholder="Your name"
                        value={adminName}
                        onChange={(e) => setAdminName(e.target.value)}
                        required
                      />
                    </div>
                  </>
                )}
                <div className="field">
                  <input
                    type="email"
                    placeholder="Email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                  />
                </div>
                <div className="field">
                  <input
                    type="password"
                    placeholder="Password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                  />
                </div>
                <button type="submit" className="btn" disabled={submitting}>
                  {mode === "register" ? "Register school" : "Sign in"}
                </button>
                {formError && <p className="error-text">{formError}</p>}
              </form>
            )}
          </div>
        )}
      </div>
    </main>
  );
}
