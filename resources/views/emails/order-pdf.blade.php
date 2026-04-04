<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; color: #18181b; line-height: 1.4; }
        .container { padding: 30px; border: 4px solid #18181b; }
        .header { border-bottom: 4px solid #18181b; padding-bottom: 20px; margin-bottom: 20px; }
        .title { font-weight: 900; text-transform: uppercase; font-size: 28px; margin: 0; font-style: italic; }
        .badge { background-color: #facc15; color: #18181b; padding: 5px 10px; font-weight: bold; text-transform: uppercase; font-size: 12px; display: inline-block; margin-bottom: 10px; border: 2px solid #18181b; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 2px solid #18181b; padding: 12px; text-align: left; }
        th { background-color: #f4f4f5; text-transform: uppercase; font-weight: 900; font-size: 12px; letter-spacing: 1px; }
        .totals { margin-top: 30px; text-align: right; }
        .totals-row { margin-bottom: 8px; font-weight: bold; font-size: 14px; text-transform: uppercase; }
        .grand-total { font-size: 24px; font-weight: 900; color: #18181b; margin-top: 15px; border-top: 4px solid #18181b; padding-top: 15px; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge">Order #{{ $rental->id }}</span>
            <h1 class="title">Reservation Confirmation</h1>
            <p><strong>Customer:</strong> {{ $rental->user?->name ?? $rental->first_name }}</p>
            <p><strong>Email:</strong> {{ $rental->user?->email ?? $rental->email }}</p>
            <p><strong>Rental Period:</strong> {{ $rental->start_at->format('M d, Y') }} to {{ $rental->end_at->format('M d, Y') }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Equipment</th>
                    <th>Qty</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rental->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->tool?->name ?? 'Unknown Item' }}</strong><br>
                        <small style="color: #52525b;">Pricing Tier: {{ $item->pricing_type }}</small>
                    </td>
                    <td>{{ $item->quantity }}x</td>
                    <td>€{{ number_format(($item->unit_price_cents * $item->quantity) / 100, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">Items Subtotal: €{{ number_format($rental->subtotal_cents / 100, 2) }}</div>
            <div class="totals-row" style="color: #16a34a;">Deposit Paid: - €{{ number_format($rental->paid_cents / 100, 2) }}</div>
            <div class="grand-total">Balance Due at Pickup: €{{ number_format(($rental->total_cents - $rental->paid_cents) / 100, 2) }}</div>
        </div>
    </div>
</body>
</html>