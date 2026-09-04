<?php

namespace App\Mail;

use App\Models\PagoSuscripcion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Le llega a todos los usuarios con rol admin cuando un cliente reporta un
 * pago (comprobante subido) desde /cliente/suscripcion -ver
 * App\Http\Controllers\Cliente\SuscripcionController::store(). No se
 * dispara cuando el admin activa manualmente
 * (Admin\EmpresaController::activar()), porque ahí el admin ya sabe que
 * lo hizo él mismo.
 */
class PagoReportado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PagoSuscripcion $pago)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo pago reportado — '.$this->pago->empresa->nombre_negocio,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pago-reportado',
            with: [
                'pago' => $this->pago,
                'empresa' => $this->pago->empresa,
                'planLabel' => PagoSuscripcion::PLANES[$this->pago->plan]['label'] ?? $this->pago->plan,
            ],
        );
    }
}
