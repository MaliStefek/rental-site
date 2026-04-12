<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; color: #18181b; line-height: 1.4; }
        .container { padding: 30px; border: 4px solid #18181b; }
        .header { border-bottom: 4px solid #18181b; padding-bottom: 20px; margin-bottom: 20px; }
        .title { font-weight: 900; text-transform: uppercase; font-size: 28px; margin: 0; font-style: italic; }
        .badge { background-color: #facc15; color: #18181b; padding: 5px 10px; font-weight: bold; text-transform: uppercase; font-size: 12px; display: inline-block; margin-bottom: 10px; border: 2px solid #18181b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge">Order #{{ $rental->id }}</span>
            <h1 class="title">Return Reminder</h1>
        </div>
        <p>Hello {{ $rental->customer_first_name ?? $rental->user?->name ?? 'Customer' }},</p>
        <p>This is a friendly reminder that your rented equipment is due for return tomorrow.</p>
        <p><strong>Return Date:</strong> {{ $rental->end_at?->format('M d, Y') ?? 'TBD' }}</p>
        <p>We appreciate your promptness!</p>
    </div>
</body>
</html>