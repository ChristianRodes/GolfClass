<?php
require_once 'includes/db.php';
include 'includes/header.php';

$stmt = $pdo->query("
    SELECT p.id_profesor, p.foto_perfil, p.precio_hora, p.rating, p.bio,
           u.nombre, u.apellidos,
           c.nombre_campo, c.ciudad
    FROM profesores p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    JOIN campos_golf c ON p.id_campo = c.id_campo
    WHERE p.activo = 1 AND u.activo = 1
    ORDER BY p.rating DESC
");
$profesores = $stmt->fetchAll();
?>

<div class="hero-section" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/img/header.webp') center/cover no-repeat; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div class="container-fluid py-5 text-center">
        <h1 class="display-3 fw-bold text-white mb-3">No necesitas más vídeos de Youtube, necesitas <span class="text-golf">GolfClass.</span></h1>
        <p class="col-md-8 mx-auto fs-5 text-white mb-4">
            La plataforma diseñada para conectarte con los mejores instructores del mundo 🌍.
            <?php if (isset($_SESSION['user_name'])): ?>
                <br><br>¡Hola de nuevo, <strong class="text-white"><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>!
            <?php endif; ?>
        </p>
        <div class="d-flex justify-content-center gap-3">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="pages/auth/register.php" class="btn btn-golf btn-lg px-4">Empezar ahora</a>
                <a href="pages/auth/login.php" class="btn btn-outline-white btn-lg px-4">Iniciar sesión</a>
            <?php else: ?>
                <a href="#profesores" class="btn btn-golf btn-lg px-4">Explorar Profesores</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Catálogo de Profesores -->
<section id="profesores" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Nuestros <span class="text-golf">Instructores</span></h2>
            <p class="text-muted fs-5">Elige al profesional que mejor se adapte a tu nivel y objetivos.</p>
        </div>

        <?php if (empty($profesores)): ?>
            <div class="text-center py-5">
                <p class="text-muted fs-5">Aún no hay profesores disponibles. ¡Vuelve pronto!</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($profesores as $p): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 profesor-card">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="profesor-avatar me-3">
                                        <?php if (!empty($p['foto_perfil']) && file_exists('uploads/' . $p['foto_perfil'])): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($p['foto_perfil']); ?>"
                                                 alt="Foto de <?php echo htmlspecialchars($p['nombre']); ?>"
                                                 class="rounded-circle" style="width:64px;height:64px;object-fit:cover;">
                                        <?php else: ?>
                                            <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center">
                                                <?php echo strtoupper(mb_substr($p['nombre'], 0, 1) . mb_substr($p['apellidos'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellidos']); ?></h5>
                                        <small class="text-muted">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-geo-alt-fill me-1" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                                            <?php echo htmlspecialchars($p['nombre_campo']); ?>, <?php echo htmlspecialchars($p['ciudad']); ?>
                                        </small>
                                    </div>
                                </div>

                                <p class="text-muted small flex-grow-1"><?php echo htmlspecialchars(mb_substr($p['bio'], 0, 120)) . (mb_strlen($p['bio']) > 120 ? '…' : ''); ?></p>

                                <div class="d-flex align-items-center justify-content-between mt-3">
                                    <div>
                                        <span class="text-warning fw-bold">
                                            <?php
                                            $rating = round($p['rating']);
                                            echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                                            ?>
                                        </span>
                                        <small class="text-muted ms-1"><?php echo number_format($p['rating'], 1); ?></small>
                                    </div>
                                    <span class="fs-5 fw-bold text-golf"><?php echo number_format($p['precio_hora'], 0); ?>€<small class="text-muted fw-normal fs-6">/h</small></span>
                                </div>

                                <a href="pages/student/reservar.php?id=<?php echo $p['id_profesor']; ?>"
                                   class="btn btn-golf w-100 mt-3">
                                    Reservar clase
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    .profesor-card { transition: transform .2s, box-shadow .2s; }
    .profesor-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
    .avatar-placeholder {
        width: 64px; height: 64px;
        background-color: var(--golf-red);
        color: white;
        font-weight: 700;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
