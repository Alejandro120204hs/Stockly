<x-admin-layout title="Mi perfil">

    {{-- Mi perfil del Super Admin — SOLO FRONTEND por ahora. Guardar
         cambios y actualizar contraseña son demos visuales (sin backend
         conectado, no persiste al recargar). La foto sí se puede
         previsualizar de verdad -queda solo en el navegador, en memoria,
         se pierde al recargar porque no hay dónde subirla todavía. --}}

    <div class="admin-page-header admin-reveal admin-reveal-1">
        <div>
            <p class="admin-page-header__eyebrow">Panel de Super Admin</p>
            <h1 class="admin-page-header__title">Mi perfil</h1>
        </div>
    </div>

    <div class="profile-grid admin-reveal admin-reveal-2">
        <div class="panel profile-photo-panel">
            <h2 class="panel__title" style="margin-bottom: 18px;">Foto de perfil</h2>

            <div class="profile-photo">
                <div class="profile-photo__avatar" id="profileAvatar">AH</div>
                <button type="button" class="profile-photo__btn" id="profilePhotoBtn">
                    Cambiar foto
                </button>
                <input type="file" id="profilePhotoInput" accept="image/*" hidden>
                <p class="profile-photo__hint">JPG o PNG, máx. 2MB. Solo se ve en tu navegador -no se guarda todavía.</p>
            </div>
        </div>

        <div class="panel">
            <h2 class="panel__title" style="margin-bottom: 18px;">Información personal</h2>

            <form id="profileInfoForm" novalidate>
                <div class="admin-form-grid">
                    <div class="admin-form-field">
                        <label for="perfilNombres" class="admin-form-label">Nombres</label>
                        <input type="text" id="perfilNombres" class="admin-form-input" value="Alejandro">
                    </div>
                    <div class="admin-form-field">
                        <label for="perfilApellidos" class="admin-form-label">Apellidos</label>
                        <input type="text" id="perfilApellidos" class="admin-form-input" value="Hernández">
                    </div>
                </div>

                <div class="admin-form-field">
                    <label for="perfilCorreo" class="admin-form-label">Correo electrónico</label>
                    <input type="email" id="perfilCorreo" class="admin-form-input" value="alejandro@devsecsolutions.co">
                </div>

                <div class="admin-form-field">
                    <label for="perfilTelefono" class="admin-form-label">Teléfono</label>
                    <input type="tel" id="perfilTelefono" class="admin-form-input" value="300 555 1234">
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="admin-btn-primary">Guardar cambios</button>
                    <span class="admin-form-success" id="profileInfoSuccess" hidden>Guardado</span>
                </div>
            </form>
        </div>
    </div>

    <div class="panel admin-reveal admin-reveal-3" style="margin-top: 20px;">
        <h2 class="panel__title" style="margin-bottom: 18px;">Cambiar contraseña</h2>

        <form id="profilePasswordForm" novalidate style="max-width: 420px;">
            <div class="admin-form-field admin-form-field--password">
                <label for="perfilClaveActual" class="admin-form-label">Contraseña actual</label>
                <input type="password" id="perfilClaveActual" class="admin-form-input">
                <button type="button" class="admin-form-toggle-password" aria-label="Mostrar contraseña" aria-pressed="false">
                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3l18 18"/>
                        <path d="M10.6 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.6 15.6 0 0 1-3.1 4.1M6.2 6.2C3.6 8 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 4.2-.9"/>
                        <path d="M9.5 9.9A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9"/>
                    </svg>
                </button>
            </div>

            <div class="admin-form-field admin-form-field--password">
                <label for="perfilClaveNueva" class="admin-form-label">Nueva contraseña</label>
                <input type="password" id="perfilClaveNueva" class="admin-form-input">
                <button type="button" class="admin-form-toggle-password" aria-label="Mostrar contraseña" aria-pressed="false">
                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3l18 18"/>
                        <path d="M10.6 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.6 15.6 0 0 1-3.1 4.1M6.2 6.2C3.6 8 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 4.2-.9"/>
                        <path d="M9.5 9.9A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9"/>
                    </svg>
                </button>
            </div>

            <div class="admin-form-field admin-form-field--password">
                <label for="perfilClaveConfirmar" class="admin-form-label">Confirmar nueva contraseña</label>
                <input type="password" id="perfilClaveConfirmar" class="admin-form-input">
                <button type="button" class="admin-form-toggle-password" aria-label="Mostrar contraseña" aria-pressed="false">
                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3l18 18"/>
                        <path d="M10.6 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.6 15.6 0 0 1-3.1 4.1M6.2 6.2C3.6 8 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 4.2-.9"/>
                        <path d="M9.5 9.9A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9"/>
                    </svg>
                </button>
                <span class="admin-form-error" id="perfilClaveError" hidden>Las contraseñas no coinciden.</span>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn-primary">Actualizar contraseña</button>
                <span class="admin-form-success" id="profilePasswordSuccess" hidden>Contraseña actualizada</span>
            </div>
        </form>
    </div>

</x-admin-layout>
