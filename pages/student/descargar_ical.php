<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$id_reserva = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_reserva) { header("Location: mis_reservas.php"); exit(); }

$stmt = $pdo->prepare("
    SELECT r.fecha_clase, r.hora_inicio, r.hora_fin, r.id_reserva,
           u.nombre AS prof_nombre, u.apellidos AS prof_apellidos,
           COALESCE(p.ubicacion, CONCAT(c.nombre_campo,', ',c.ciudad)) AS ubicacion_display
    FROM reservas r
    JOIN profesores p ON r.id_profesor = p.id_profesor
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    LEFT JOIN campos_golf c ON p.id_campo = c.id_campo
    WHERE r.id_reserva = ? AND r.id_alumno = ? AND r.activo = 1
");
$stmt->execute([$id_reserva, $_SESSION['user_id']]);
$r = $stmt->fetch();

if (!$r) { header("Location: mis_reservas.php"); exit(); }

// Formatear fechas iCal (YYYYMMDDTHHMMSS)
$dtstart = str_replace(['-',':'], '', $r['fecha_clase']) . 'T' . str_replace(':', '', substr($r['hora_inicio'],0,5)) . '00';
$dtend   = str_replace(['-',':'], '', $r['fecha_clase']) . 'T' . str_replace(':', '', substr($r['hora_fin'],   0,5)) . '00';
$now     = gmdate('Ymd\THis\Z');

$ical = "BEGIN:VCALENDAR\r\n";
$ical .= "VERSION:2.0\r\n";
$ical .= "PRODID:-///ES\r\n";
$ical .= "BEGIN:VEVENT\r\n";
$ical .= "UID:golfclass-reserva-{$r['id_reserva']}@golfclass.com\r\n";
$ical .= "DTSTAMP:{$now}\r\n";
$ical .= "DTSTART:{$dtstart}\r\n";
$ical .= "DTEND:{$dtend}\r\n";
$ical .= "SUMMARY:Clase de Golf con {$r['prof_nombre']} {$r['prof_apellidos']}\r\n";
$ical .= "LOCATION:" . str_replace(["\r","\n"], ' ', $r['ubicacion_display']) . "\r\n";
$ical .= "DESCRIPTION:Reserva #" . $r['id_reserva'] . " en GolfClass\r\n";
$ical .= "END:VEVENT\r\n";
$ical .= "END:VCALENDAR\r\n";

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="reserva_' . $id_reserva . '.ics"');
echo $ical;
exit();
