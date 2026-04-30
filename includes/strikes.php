<?php
/**
 * Sistema de strikes de GolfClass.
 *
 * Reglas:
 *   - Cancelar una reserva              → 1 strike
 *   - Profesor marca "No presentado"    → 1 strike
 *   - 2 strikes en las últimas 5 reservas → ban temporal 48 h
 *   - 5 strikes totales                 → cuenta bloqueada permanentemente
 */

/**
 * Registra un strike para un alumno y aplica consecuencias si procede.
 * Devuelve un array con 'tipo' => null | 'temporal' | 'permanente'.
 */
function registrar_strike(PDO $pdo, int $id_alumno, int $id_reserva, string $motivo): array
{
    // Insertar strike (IGNORE si ya existe uno para esta reserva)
    $pdo->prepare("
        INSERT IGNORE INTO strikes (id_alumno, id_reserva, motivo)
        VALUES (?, ?, ?)
    ")->execute([$id_alumno, $id_reserva, $motivo]);

    $info = get_strike_info($pdo, $id_alumno);

    // ── 5 strikes totales → bloqueo permanente ────────────────
    if ($info['total'] >= 5) {
        $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id_usuario = ?")
            ->execute([$id_alumno]);
        return ['tipo' => 'permanente', 'info' => $info];
    }

    // ── 2 strikes en últimas 5 reservas → ban 48 h ───────────
    if ($info['en_ultimas_5'] >= 2) {
        $ban_hasta = date('Y-m-d H:i:s', strtotime('+48 hours'));
        $pdo->prepare("UPDATE usuarios SET ban_hasta = ? WHERE id_usuario = ?")
            ->execute([$ban_hasta, $id_alumno]);
        $info['ban_hasta']  = $ban_hasta;
        $info['ban_activo'] = true;
        return ['tipo' => 'temporal', 'info' => $info];
    }

    return ['tipo' => null, 'info' => $info];
}

/**
 * Devuelve toda la información de strikes de un alumno.
 */
function get_strike_info(PDO $pdo, int $id_alumno): array
{
    // Total strikes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM strikes WHERE id_alumno = ?");
    $stmt->execute([$id_alumno]);
    $total = (int) $stmt->fetchColumn();

    // Strikes en las últimas 5 reservas del alumno
    $stmt = $pdo->prepare("
        SELECT COUNT(s.id_strike)
        FROM (
            SELECT id_reserva
            FROM reservas
            WHERE id_alumno = ?
            ORDER BY fecha_clase DESC, hora_inicio DESC
            LIMIT 5
        ) AS ultimas
        JOIN strikes s ON s.id_reserva = ultimas.id_reserva
    ");
    $stmt->execute([$id_alumno]);
    $en_ultimas_5 = (int) $stmt->fetchColumn();

    // Ban activo
    $stmt = $pdo->prepare("SELECT ban_hasta FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id_alumno]);
    $ban_hasta  = $stmt->fetchColumn();
    $ban_activo = $ban_hasta && strtotime($ban_hasta) > time();

    // Historial de los últimos 10 strikes
    $stmt = $pdo->prepare("
        SELECT s.id_strike, s.motivo, s.fecha,
               r.fecha_clase, r.hora_inicio,
               u.nombre AS prof_nombre, u.apellidos AS prof_apellidos
        FROM strikes s
        JOIN reservas r ON s.id_reserva = r.id_reserva
        JOIN profesores p ON r.id_profesor = p.id_profesor
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE s.id_alumno = ?
        ORDER BY s.fecha DESC
        LIMIT 10
    ");
    $stmt->execute([$id_alumno]);
    $historial = $stmt->fetchAll();

    return [
        'total'       => $total,
        'en_ultimas_5'=> $en_ultimas_5,
        'ban_activo'  => $ban_activo,
        'ban_hasta'   => $ban_hasta ?: null,
        'historial'   => $historial,
    ];
}

/**
 * Comprueba si un alumno tiene ban activo.
 * Devuelve 'permanente', 'temporal' o null.
 */
function check_ban(PDO $pdo, int $id_alumno): ?string
{
    $stmt = $pdo->prepare("SELECT activo, ban_hasta FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id_alumno]);
    $u = $stmt->fetch();

    if (!$u || !$u['activo']) return 'permanente';
    if ($u['ban_hasta'] && strtotime($u['ban_hasta']) > time()) return 'temporal';
    return null;
}

/**
 * Formatea un timestamp de ban como "Xh Ym restantes".
 */
function tiempo_restante_ban(string $ban_hasta): string
{
    $seg = strtotime($ban_hasta) - time();
    if ($seg <= 0) return '0m';
    $h = floor($seg / 3600);
    $m = floor(($seg % 3600) / 60);
    return "{$h}h {$m}m";
}
