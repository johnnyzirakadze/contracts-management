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
        /* Expiry highlighting */
        .due-60 { background:rgb(255, 219, 99); } /* ყვითელი (31-60 დღე) */
        .due-30 { background:rgb(249, 176, 67); } /* ნარინჯისფერი (≤30 დღე) */
        .expired { background:rgb(249, 75, 89); } /* წითელი (ვადაგასული) */
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
            @php
                $rowClass = '';
                try {
                    $expStr = $r['Expiry Date'] ?? null;
                    if ($expStr) {
                        $exp = \Carbon\Carbon::parse($expStr)->startOfDay();
                        $today = \Carbon\Carbon::now()->startOfDay();
                        $diff = $today->diffInDays($exp, false); // negative თუ ვადაგასულია
                        if ($diff < 0) {
                            $rowClass = 'expired';
                        } elseif ($diff <= 30) {
                            $rowClass = 'due-30';
                        } elseif ($diff <= 60) {
                            $rowClass = 'due-60';
                        }
                    }
                } catch (\Throwable $e) {
                    $rowClass = '';
                }
            @endphp
            <tr class="{{ $rowClass }}">
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


