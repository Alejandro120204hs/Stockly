<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Correo de "recuperar contraseña" con la paleta de Stockly, en vez
        // de la plantilla genérica azul que trae Laravel por defecto.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Recupera tu contraseña — Stockly')
                ->view('emails.password-reset', [
                    'url' => $url,
                    'nombre' => $notifiable->nombreCompleto(),
                    'count' => config('auth.passwords.users.expire'),
                ]);
        });
    }
}
