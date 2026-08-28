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

if (! function_exists('hora_es')) {
    /**
     * Carbon en español da "9:11 p. m." -con espacio normal ese "p. m."
     * puede partirse en dos líneas ("p.\nm.") si el contenedor es angosto
     * (pasó en las stat cards del Dashboard/Caja). Este espacio va con
     * NBSP para que el meridiano siempre quede junto; el espacio entre la
     * hora y el meridiano sí puede partirse, ese salto se ve bien.
     */
    function hora_es(\Carbon\Carbon $fecha): string
    {
        return preg_replace('/([ap])\. m\./', "$1.\u{00A0}m.", $fecha->locale('es')->translatedFormat('g:i a'));
    }
}
