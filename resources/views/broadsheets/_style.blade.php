<style>
    @page { margin: 18px 18px 24px 18px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: {{ $font }}px; color: #111827; }
    h1 { font-size: 13px; margin: 0; color: {{ $color }}; }
    .sub { color: #4b5563; margin: 2px 0 8px; }
    table.bs { width: 100%; border-collapse: collapse; }
    table.bs th, table.bs td { border: 0.5px solid #9ca3af; padding: 2px 3px; text-align: center; }
    table.bs th { background: {{ $color }}; color: #fff; font-weight: bold; }
    table.bs th.part { background: #f3f4f6; color: #111827; font-weight: normal; }
    table.bs td.name { text-align: left; white-space: nowrap; }
    table.bs td.total { font-weight: bold; }
    table.bs tr.alt td { background: #f9fafb; }
    table.bs tr.foot td { background: #eef2ff; font-weight: bold; }
    table.bs td.fail { color: #b91c1c; }
    .preview { color: #b45309; font-weight: bold; }
    .note { color: #6b7280; margin-top: 6px; }
</style>
