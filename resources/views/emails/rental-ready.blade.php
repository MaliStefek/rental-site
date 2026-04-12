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
            <h1 class="title">Your Tools Are Ready</h1>
        </div>
        <p>Hello {{ $rental->customer_first_name ?? $rental->user?->name ?? 'Customer' }},</p>
        <p>Your rental order is now prepared and ready for pickup at our warehouse.</p>
        <p><strong>Pickup Date:</strong> {{ $rental->start_at->format('M d, Y') }}</p>
        <p>Thank you for choosing us!</p>
    </div>
</body>
</html>