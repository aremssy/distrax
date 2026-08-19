<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Print</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; margin: 24px; color: #1e293b; }
        .head { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #5352ed; padding-bottom: 10px; margin-bottom: 16px; }
        .head h1 { margin: 0; font-size: 20px; color: #0f172a; }
        .head p { margin: 4px 0 0; font-size: 12px; color: #64748b; }
        .actions { margin-bottom: 16px; }
        button { background: #5352ed; color: #fff; border: 0; border-radius: 8px; padding: 8px 16px; font-size: 14px; font-weight: 600; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th { background: #f1f5f9; text-align: left; padding: 8px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #475569; border-bottom: 1px solid #cbd5e1; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .empty { padding: 28px; text-align: center; color: #94a3b8; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="head">
        <div>
            <h1>{{ $title }}</h1>
            <p>Generated {{ $generatedAt->format('d M Y, H:i') }} &middot; {{ count($rows) }} record(s)</p>
        </div>
    </div>

    <div class="actions">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ max(count($headings), 1) }}">No records to export.</td></tr>
            @endforelse
        </tbody>
    </table>

    <script>
        // Auto-open the print dialog once the table has rendered.
        window.addEventListener('load', () => setTimeout(() => window.print(), 300));
    </script>
</body>
</html>
