<?php
require_once '../../includes/db.php';

$ciudad_q = trim($_GET['ciudad'] ?? '');
$campo_q  = trim($_GET['campo']  ?? '');

$where  = "WHERE p.activo = 1 AND u.activo = 1";
$params = [];

if ($ciudad_q !== '') {
    // Busca en la ubicación libre del profesor Y en la ciudad del campo vinculado
    $where   .= " AND (p.ubicacion LIKE ? OR c.ciudad LIKE ?)";
    $like     = "%{$ciudad_q}%";
    $params[] = $like;
    $params[] = $like;
}
if ($campo_q !== '') {
    $where   .= " AND (p.ubicacion LIKE ? OR c.nombre_campo LIKE ?)";
    $like     = "%{$campo_q}%";
    $params[] = $like;
    $params[] = $like;
}

$stmt = $pdo->prepare("
    SELECT p.id_profesor, p.foto_perfil, p.precio_hora, p.puntuacion_simulada,
           p.bio, p.experiencia_anos, p.titulaciones, p.clases_online,
           p.ubicacion,
           u.nombre, u.apellidos,
           c.nombre_campo, c.ciudad AS ciudad_campo
    FROM profesores p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    LEFT JOIN campos_golf c ON p.id_campo = c.id_campo
    {$where}
    ORDER BY p.puntuacion_simulada DESC
");
$stmt->execute($params);
$profesores = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">

    <!-- Buscador avanzado -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Buscar instructor</h5>
            <form method="GET" action="">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">¿En qué ciudad buscas?</label>
                        <input type="text" name="ciudad" class="form-control"
                               placeholder="Ej: Madrid, Barcelona, online…"
                               value="<?php echo htmlspecialchars($ciudad_q); ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Nombre del Campo o Ubicación</label>
                        <input type="text" name="campo" class="form-control"
                               placeholder="Ej: La Moraleja, a domicilio…"
                               value="<?php echo htmlspecialchars($campo_q); ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-golf">Buscar</button>
                    </div>
                </div>
                <?php if ($ciudad_q !== '' || $campo_q !== ''): ?>
                    <div class="mt-2">
                        <a href="catalogo.php" class="text-muted small">✕ Limpiar filtros</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Encabezado resultado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">
            <?php if ($ciudad_q !== '' || $campo_q !== ''): ?>
                Resultados <span class="text-golf">(<?php echo count($profesores); ?>)</span>
            <?php else: ?>
                Todos los <span class="text-golf">Instructores</span>
            <?php endif; ?>
        </h2>
    </div>

    <?php if (empty($profesores)): ?>
        <div class="text-center py-5">
            <p class="text-muted fs-5">No hemos encontrado instructores con esos filtros.</p>
            <a href="catalogo.php" class="btn btn-golf mt-2">Ver todos</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($profesores as $p):
                $ubicacion_display = $p['ubicacion']
                    ?: (($p['nombre_campo'] ?? '') . (isset($p['ciudad_campo']) ? ', ' . $p['ciudad_campo'] : ''));
                $titulines = array_filter(array_map('trim', explode("\n", $p['titulaciones'] ?? '')));
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 profesor-card">
                        <div class="card-body p-4 d-flex flex-column">

                            <!-- Cabecera: avatar + nombre -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="gc-avatar gc-avatar-md me-3">
                                    <?php if (!empty($p['foto_perfil'])): ?>
                                        <img src="/uploads/<?php echo htmlspecialchars($p['foto_perfil']); ?>" alt="">
                                    <?php else: ?>
                                        <?php echo strtoupper(mb_substr($p['nombre'],0,1).mb_substr($p['apellidos']??'',0,1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="overflow-hidden">
                                    <h5 class="mb-0 fw-bold text-truncate">
                                        <?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellidos']); ?>
                                    </h5>
                                    <small class="text-muted d-block text-truncate">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-geo-alt-fill me-1" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                                        <?php echo htmlspecialchars($ubicacion_display); ?>
                                    </small>
                                </div>
                            </div>

                            <!-- Badges: online + experiencia -->
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <?php if (!empty($p['clases_online'])): ?>
                                    <span class="badge" style="background:var(--golf-red);font-size:.72rem;">Online disponible</span>
                                <?php endif; ?>
                                <?php if (!empty($p['experiencia_anos'])): ?>
                                    <span class="badge bg-light text-dark border" style="font-size:.72rem;">
                                        <?php echo (int)$p['experiencia_anos']; ?> años exp.
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Titulaciones -->
                            <?php if (!empty($titulines)): ?>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <?php foreach ($titulines as $t): ?>
                                        <span class="badge bg-light text-dark border" style="font-size:.68rem;font-weight:500;">
                                            🎓 <?php echo htmlspecialchars($t); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Bio -->
                            <p class="text-muted small flex-grow-1 mt-1">
                                <?php echo htmlspecialchars(mb_substr($p['bio'] ?? '', 0, 110)) . (mb_strlen($p['bio'] ?? '') > 110 ? '…' : ''); ?>
                            </p>

                            <!-- Puntuación y precio -->
                            <div class="d-flex align-items-center justify-content-between mt-3 mb-3">
                                <div>
                                    <?php
                                    $stars = round((float)($p['puntuacion_simulada'] ?? 5));
                                    echo '<span class="text-warning fw-bold">' . str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) . '</span>';
                                    echo ' <small class="text-muted">' . number_format($p['puntuacion_simulada'] ?? 5, 1) . '</small>';
                                    ?>
                                </div>
                                <span class="fs-5 fw-bold text-golf">
                                    <?php echo number_format($p['precio_hora'], 0); ?>€<small class="text-muted fw-normal fs-6">/h</small>
                                </span>
                            </div>

                            <a href="/pages/booking/book_class.php?id=<?php echo $p['id_profesor']; ?>"
                               class="btn btn-golf w-100">
                                Reservar clase
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .profesor-card { transition: transform .2s, box-shadow .2s; }
    .profesor-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
    /* avatares: usa .gc-avatar definido en header.php */
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
