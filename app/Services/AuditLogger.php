<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Registra una acción en archivo de auditoría con formato detallado.
     */
    public static function log($tipo, $nivel, $descripcion, $exception = null, $datos = null)
    {
        try {
            $user = Auth::check() ? Auth::user() : null;
            $usuario_nombre = $user
                ? $user->name.' ('.$user->email.')'
                : 'Invitado';

            $ip = Request::ip();
            $url = Request::fullUrl();
            $metodo = Request::method();
            $user_agent = Request::header('User-Agent');
            $fecha = now()->format('Y-m-d H:i:s');

            // Construir mensaje multilínea para archivo de auditoría
            $mensaje = "\n[".$fecha.']';
            $mensaje .= "\nTIPO: ".strtoupper($tipo);
            $mensaje .= "\nNIVEL: ".$nivel;
            $mensaje .= "\nDESCRIPCIÓN: ".$descripcion;
            $mensaje .= "\nUSUARIO: ".$usuario_nombre;
            $mensaje .= "\nIP ORIGEN: ".$ip;
            $mensaje .= "\nMÉTODO HTTP: ".$metodo;
            $mensaje .= "\nUBICACIÓN: ".$url;

            if ($datos) {
                $mensaje .= "\nDATOS: ".json_encode($datos);
            }

            if ($exception) {
                $mensaje .= "\nERROR MSG: ".$exception->getMessage();
                $mensaje .= "\nARCHIVO: ".$exception->getFile().':'.$exception->getLine();
            }

            $mensaje .= "\n--------------------------------------------------";

            // Guardar en archivo de auditoría
            Log::channel('audit')->info($mensaje);
        } catch (\Exception $e) {
            // Fallback por si falla el logging
            Log::error('Error al registrar log de auditoría: '.$e->getMessage());
        }
    }

    // Helpers para acciones comunes
    /**
     * Registra una consulta
     */
    public static function consulta($descripcion, $datos = null)
    {
        self::log('CONSULTA', 'info', $descripcion, null, $datos);
    }

    /**
     * Registra una inserción
     */
    public static function insercion($descripcion, $datos = null)
    {
        self::log('INSERCIÓN', 'success', $descripcion, null, $datos);
    }

    /**
     * Registra una actualización
     */
    public static function actualizacion($descripcion, $cambios = null)
    {
        self::log('ACTUALIZACIÓN', 'info', $descripcion, null, $cambios);
    }

    /**
     * Registra una eliminación
     */
    public static function eliminacion($descripcion, $id = null)
    {
        $datos = $id ? ['id_eliminado' => $id] : null;
        self::log('ELIMINACIÓN', 'warning', $descripcion, null, $datos);
    }

    /**
     * Registra un error
     */
    public static function error($descripcion, $exception = null)
    {
        self::log('ERROR', 'error', $descripcion, $exception);
    }

    /**
     * Registra un login
     */
    public static function login($descripcion)
    {
        self::log('LOGIN', 'info', $descripcion);
    }

    /**
     * Registra un logout
     */
    public static function logout($descripcion)
    {
        self::log('LOGOUT', 'info', $descripcion);
    }
}
