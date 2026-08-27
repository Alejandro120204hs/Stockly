<?php

use Illuminate\Support\Facades\File;

if (! function_exists('asset_v')) {
    /**
     * Igual que asset(), pero le agrega "?v=<fecha de modificación>" al
     * final -así el navegador SIEMPRE trae la versión más reciente de un
     * CSS/JS en cuanto se despliega un cambio, sin que el cliente tenga
     * que forzar un refresco (Ctrl+Shift+R) para dejar de ver el archivo
     * viejo cacheado.
     */
    function asset_v(string $path): string
    {
        $absolute = public_path($path);
        $version = File::exists($absolute) ? File::lastModified($absolute) : time();

        return asset($path).'?v='.$version;
    }
}
