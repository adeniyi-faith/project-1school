// Hand-drawn line icons, defined once and reused everywhere via
// <svg class="icon"><use href="#i-name" /></svg>. No icon library
// needed — these are just small SVG shapes.
export function IconSprite() {
  return (
    <svg style={{ display: "none" }}>
      <symbol id="i-check-circle" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="9" />
        <polyline points="7.5,12.5 10.5,15.5 16.5,8.5" />
      </symbol>
      <symbol id="i-logout" viewBox="0 0 24 24">
        <path d="M9 21H5.5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2H9" />
        <polyline points="16,17 21,12 16,7" />
        <line x1="21" y1="12" x2="9" y2="12" />
      </symbol>
      <symbol id="i-mail" viewBox="0 0 24 24">
        <path d="M4 6h16v12H4z" />
        <path d="M4 7l8 6 8-6" />
      </symbol>
      <symbol id="i-lock" viewBox="0 0 24 24">
        <rect x="5" y="10" width="14" height="10" rx="2" />
        <path d="M8 10V7a4 4 0 018 0v3" />
      </symbol>
      <symbol id="i-graduation" viewBox="0 0 24 24">
        <path d="M12 3 2 8l10 5 10-5-10-5z" />
        <path d="M6 10.5v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5" />
        <line x1="22" y1="8" x2="22" y2="15" />
      </symbol>
    </svg>
  );
}
