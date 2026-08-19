<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1e293b; font-size: 10px; }
        .head { border-bottom: 2px solid #5352ed; padding-bottom: 8px; margin-bottom: 12px; }
        .head h1 { margin: 0; font-size: 16px; color: #0f172a; }
        .head p { margin: 2px 0 0; font-size: 9px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #f1f5f9; text-align: left; padding: 6px 8px;
            font-size: 9px; text-transform: uppercase; letter-spacing: .04em; color: #475569;
            border-bottom: 1px solid #cbd5e1;
        }
        tbody td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .empty { padding: 20px; text-align: center; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="head">
        <h1>{{ $title }}</h1>
        <p>Generated {{ $generatedAt->format('d M Y, H:i') }} &middot; {{ count($rows) }} record(s)</p>
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
</body>
</html>
