<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Your data') }} — {{ config('app.name') }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1e293b; font-size: 11px; }
        .head { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 14px; }
        .head h1 { margin: 0; font-size: 17px; color: #0f172a; }
        .head p { margin: 3px 0 0; font-size: 9px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #eef2ff; text-align: left; padding: 6px 8px; font-size: 9px;
            text-transform: uppercase; letter-spacing: .04em; color: #4338ca; border-bottom: 1px solid #c7d2fe;
        }
        tbody td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tbody td.section { font-weight: bold; color: #334155; }
        tbody tr:nth-child(even) { background: #f8fafc; }
    </style>
</head>
<body>
    <div class="head">
        <h1>{{ __('Your personal data') }}</h1>
        <p>{{ $user->name }} &middot; {{ $user->email }} &middot; {{ __('Generated :date', ['date' => now()->format('d M Y, H:i')]) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:22%">{{ __('Section') }}</th>
                <th style="width:33%">{{ __('Field') }}</th>
                <th>{{ __('Value') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="section">{{ $row[0] }}</td>
                    <td>{{ $row[1] }}</td>
                    <td>{{ $row[2] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
