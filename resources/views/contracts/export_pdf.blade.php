<!doctype html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f2f2f2; }
        .muted { color: #666; font-size: 11px; }
    </style>
    <title>Contracts Export</title>
    </head>
<body>
<h3>კონტრაქტების რეესტრი</h3>
<div class="muted">დაგენერირდა: {{ now()->format('Y-m-d H:i:s') }}</div>

<table>
    <thead>
        <tr>
            @foreach(array_keys($rows[0] ?? ['No Data' => '']) as $head)
                <th>{{ $head }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $r)
            <tr>
                @foreach($r as $v)
                    <td>{{ $v }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td>მონაცემი ვერ მოიძებნა</td></tr>
        @endforelse
    </tbody>
    </table>
</body>
</html>


