<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Rental;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class RentalController extends Controller
{
    public function downloadInvoice(Rental $rental)
    {
        Gate::authorize('view', $rental);

        $pdf = Pdf::loadView('emails.order-pdf', ['rental' => $rental]);

        return $pdf->download('Invoice_Order_'.$rental->id.'.pdf');
    }
}
