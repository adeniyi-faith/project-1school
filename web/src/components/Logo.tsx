export function Logo({ onDark = false }: { onDark?: boolean }) {
  return (
    <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
      <svg width="30" height="30" viewBox="0 0 30 30" fill="none">
        <defs>
          <linearGradient id="logo-gradient" x1="0" y1="30" x2="30" y2="0">
            <stop offset="0%" stopColor="#1FB871" />
            <stop offset="100%" stopColor="#FF6B4A" />
          </linearGradient>
        </defs>
        <rect x="3" y="16" width="5.5" height="11" rx="1.6" fill="url(#logo-gradient)" />
        <rect
          x="12"
          y="9"
          width="5.5"
          height="18"
          rx="1.6"
          fill="url(#logo-gradient)"
          opacity="0.85"
        />
        <rect
          x="21"
          y="3"
          width="5.5"
          height="24"
          rx="1.6"
          fill="url(#logo-gradient)"
          opacity="0.7"
        />
      </svg>
      <span
        style={{
          fontFamily: "var(--heading)",
          fontWeight: 700,
          fontSize: 19,
          letterSpacing: "-0.02em",
          whiteSpace: "nowrap",
        }}
      >
        <span style={{ color: onDark ? "#fff" : "var(--ink)" }}>School</span>
        <span style={{ color: "var(--mint)" }}>Runs</span>
      </span>
    </div>
  );
}
