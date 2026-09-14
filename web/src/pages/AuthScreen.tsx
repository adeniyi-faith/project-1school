import { useEffect, useState } from "react";
import type { Session } from "@supabase/supabase-js";
import { apiGet, apiPost, ApiError } from "../lib/api";
import { supabase } from "../lib/supabaseClient";
import { Logo } from "../components/Logo";
import "./AuthScreen.css";

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
  const [session, setSession] = useState<Session | null>(null);
  const [me, setMe] = useState<MeResponse | null>(null);
  const [checkingMe, setCheckingMe] = useState(false);
  const [mode, setMode] = useState<"sign-in" | "register">("register");

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [schoolName, setSchoolName] = useState("");
  const [adminName, setAdminName] = useState("");
  const [formError, setFormError] = useState<string | null>(null);
  const [confirmEmailNotice, setConfirmEmailNotice] = useState(false);
  const [submitting, setSubmitting] = useState(false);

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

      setCheckingMe(true);
      try {
        setMe(await apiGet<MeResponse>("/me"));
      } catch (err) {
        setFormError(err instanceof ApiError ? err.message : "Couldn't reach the API.");
      } finally {
        setCheckingMe(false);
      }
    })();
  }, [session]);

  async function registerSchool(schoolNameValue: string, adminNameValue: string) {
    setFormError(null);
    setSubmitting(true);
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
    } finally {
      setSubmitting(false);
    }
  }

  async function handleFinishRegistering(e: React.FormEvent) {
    e.preventDefault();
    await registerSchool(schoolName, adminName);
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

  // Signed in with a school already set up.
  if (session && me?.school) {
    return (
      <main className="page">
        <div className="auth-wrap" style={{ maxWidth: 420 }}>
          <div className="auth-form-side" style={{ flex: "none", width: "100%" }}>
            <div style={{ marginBottom: 24 }}>
              <Logo />
            </div>
            <div className="signed-in-row">
              <span className="signed-in-email">{session.user.email}</span>
              <button type="button" className="btn-secondary" onClick={handleSignOut}>
                Sign out
              </button>
            </div>
            <div className="success-card">
              {me.school.name} is live — School ID <span className="school-id-badge">{me.school.code}</span>
            </div>
          </div>
        </div>
      </main>
    );
  }

  // Signed in but no school yet — either registering for the first
  // time failed partway, or this account genuinely has none.
  if (session && !me?.school && !checkingMe) {
    return (
      <main className="page">
        <div className="auth-wrap" style={{ maxWidth: 420 }}>
          <div className="auth-form-side" style={{ flex: "none", width: "100%" }}>
            <div style={{ marginBottom: 24 }}>
              <Logo />
            </div>
            <div className="signed-in-row">
              <span className="signed-in-email">{session.user.email}</span>
              <button type="button" className="btn-secondary" onClick={handleSignOut}>
                Sign out
              </button>
            </div>
            <div className="auth-title">Finish setting up your school</div>
            <form onSubmit={handleFinishRegistering}>
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
              <button type="submit" className="btn-primary" disabled={submitting}>
                Register school
              </button>
              {formError && <p className="error-text">{formError}</p>}
            </form>
          </div>
        </div>
      </main>
    );
  }

  return (
    <main className="page">
      <div className="auth-wrap">
        <div className="auth-brand">
          <Logo onDark />
          <div className="auth-headline">
            <h2>
              {mode === "register" ? (
                <>
                  Set up your school
                  <br />
                  in under 15 minutes.
                </>
              ) : (
                <>
                  Run your entire school
                  <br />
                  from one dashboard.
                </>
              )}
            </h2>
            <p>
              {mode === "register"
                ? "Add your school, invite staff, and start managing everything in one place."
                : "Records, roles, and your school's day-to-day, all kept in sync."}
            </p>
            <div className="auth-feats">
              <div>
                <svg className="icon">
                  <use href="#i-check-circle" />
                </svg>
                A school-wide dashboard for admins
              </div>
              <div>
                <svg className="icon">
                  <use href="#i-check-circle" />
                </svg>
                Separate roles for teachers, parents &amp; students
              </div>
              <div>
                <svg className="icon">
                  <use href="#i-check-circle" />
                </svg>
                Your own School ID from day one
              </div>
            </div>
          </div>
          <div />
        </div>

        <div className="auth-form-side">
          <div className="tabs">
            <button
              type="button"
              className={mode === "register" ? "tab active" : "tab"}
              onClick={() => setMode("register")}
            >
              Sign up
            </button>
            <button
              type="button"
              className={mode === "sign-in" ? "tab active" : "tab"}
              onClick={() => setMode("sign-in")}
            >
              Log in
            </button>
          </div>

          {confirmEmailNotice ? (
            <div className="success-card">
              Check your email for a confirmation link. Once confirmed, come back and log in to
              finish setting up your school.
            </div>
          ) : (
            <>
              <div className="auth-title">
                {mode === "register" ? "Create your school's account" : "Welcome back"}
              </div>
              <div className="auth-sub">
                {mode === "register"
                  ? "Get your school running on SchoolRuns."
                  : "Log in to manage your school today."}
              </div>
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
                <div className="field field-wrap">
                  <svg className="icon">
                    <use href="#i-mail" />
                  </svg>
                  <input
                    type="email"
                    placeholder="Work email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                  />
                </div>
                <div className="field field-wrap">
                  <svg className="icon">
                    <use href="#i-lock" />
                  </svg>
                  <input
                    type="password"
                    placeholder="Password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                  />
                </div>
                <button type="submit" className="btn-primary" disabled={submitting}>
                  {mode === "register" ? "Create account" : "Log in"}
                </button>
                {formError && <p className="error-text">{formError}</p>}
              </form>
            </>
          )}
        </div>
      </div>
    </main>
  );
}
