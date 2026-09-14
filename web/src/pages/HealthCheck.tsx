import { useEffect, useState } from "react";
import { apiGet } from "../lib/api";

type HealthResponse = {
  status: string;
  service: string;
};

// A small page that proves the web app and the Laravel API can talk to
// each other. It's a starting point for the real dashboard, not the
// final product.
export function HealthCheck() {
  const [health, setHealth] = useState<HealthResponse | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    apiGet<HealthResponse>("/health")
      .then(setHealth)
      .catch((err: Error) => setError(err.message));
  }, []);

  return (
    <main style={{ fontFamily: "sans-serif", padding: "2rem" }}>
      <h1>SchoolRuns</h1>
      <p>Web app + Laravel API connectivity check.</p>
      {error && <p style={{ color: "crimson" }}>API error: {error}</p>}
      {!error && !health && <p>Checking API connection...</p>}
      {health && (
        <p style={{ color: "green" }}>
          API says: {health.status} ({health.service})
        </p>
      )}
    </main>
  );
}
