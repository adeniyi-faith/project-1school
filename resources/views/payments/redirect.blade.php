<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Going to the payment page…</title>
<style>body{font-family:system-ui,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;background:#f8fafc;color:#0f172a}p{margin:.5rem 0}button{margin-top:1rem;padding:.6rem 1.2rem;border:0;border-radius:.5rem;background:#059669;color:#fff;font-size:1rem;cursor:pointer}</style>
</head><body>
<form id="pay" method="post" action="{{ $form['action'] }}" style="text-align:center">
    @foreach ($form['fields'] as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <p><strong>Taking you to the secure payment page…</strong></p>
    <p>If nothing happens, press the button.</p>
    <button type="submit">Continue to payment</button>
</form>
<script>document.getElementById('pay').submit();</script>
</body></html>
