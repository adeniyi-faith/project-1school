@php
    $wa = preg_replace('/\D+/', '', (string) $site->option('whatsapp'));
    if (strlen($wa) === 11 && str_starts_with($wa, '0')) { $wa = '234'.substr($wa, 1); } // 0803... becomes 234803...
    $waText = trim((string) $site->option('whatsapp_text')) ?: 'Hello, I have a question about '.$school->name.'.';
@endphp
@if (strlen($wa) >= 10 && strlen($wa) <= 15)
    <a class="chat-fab" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 0 0-8.5 14.9L2 22l5.27-1.38A9.9 9.9 0 1 0 12.04 2Zm5.8 14.1c-.25.7-1.45 1.33-2 1.4-.52.07-1.17.1-1.9-.12a17 17 0 0 1-1.72-.64c-3-1.3-4.95-4.34-5.1-4.54-.15-.2-1.2-1.6-1.2-3.05 0-1.45.76-2.16 1.03-2.46.27-.3.6-.37.8-.37h.57c.18 0 .43-.07.67.5.25.6.85 2.07.92 2.22.08.15.13.32.03.52-.1.2-.15.32-.3.5-.15.17-.32.38-.45.51-.15.15-.3.3-.13.6.17.3.77 1.27 1.65 2.05 1.13 1 2.08 1.32 2.38 1.47.3.15.47.12.65-.07.17-.2.75-.87.95-1.17.2-.3.4-.25.67-.15.27.1 1.75.83 2.05.98.3.15.5.22.57.35.08.12.08.72-.17 1.42Z"/></svg>
        <span>Chat on WhatsApp</span>
    </a>
@endif
