<?php
require_once 'includes/db.php';
include 'includes/header.php';

// Top 3 para el carrusel
$stmt = $pdo->query("
    SELECT p.id_profesor, p.foto_perfil, p.precio_hora, p.puntuacion_simulada, p.bio, p.experiencia_anos,
           u.nombre, u.apellidos,
           c.nombre_campo, c.ciudad
    FROM profesores p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    JOIN campos_golf c ON p.id_campo = c.id_campo
    WHERE p.activo = 1 AND u.activo = 1
    ORDER BY p.puntuacion_simulada DESC
    LIMIT 3
");
$top_profesores = $stmt->fetchAll();

?>

<!-- ═══════════════════════════════════════════
     HERO: buscador central
═══════════════════════════════════════════ -->
<section class="hero-section" style="
    background: linear-gradient(rgba(0,0,0,0.62), rgba(0,0,0,0.62)),
                url('assets/img/header.webp') center/cover no-repeat;
    min-height: 92vh;
    display: flex; align-items: center; justify-content: center;">
    <div class="container text-center py-5">

        <?php if (isset($_SESSION['user_name'])): ?>
            <p class="text-white-50 mb-2 fs-6">
                Bienvenido de nuevo, <strong class="text-white"><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
            </p>
        <?php endif; ?>

        <h1 class="display-3 fw-bold text-white mb-2">
            ¿Dónde quieres<br><span class="text-golf">entrenar hoy?</span>
        </h1>
        <p class="text-white-50 fs-5 mb-5">
            Conecta con instructores certificados en tu ciudad. Sin excusas.
        </p>

        <!-- Buscador por ciudad -->
        <form action="pages/profesores/catalogo.php" method="GET"
              class="d-flex justify-content-center gap-2 flex-wrap">
            <div class="input-group hero-search-group shadow-lg">
                <span class="input-group-text bg-white border-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#6c757d" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                </span>
                <input type="text" name="ciudad" class="form-control form-control-lg border-0"
                       placeholder="Madrid, Barcelona, Sevilla…" style="min-width:280px;">
                <button class="btn btn-golf px-4 fw-bold" type="submit">Buscar instructores</button>
            </div>
        </form>

        <div class="mt-4">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="pages/auth/register.php" class="btn btn-outline-white btn-sm px-4 me-2">Crear cuenta gratis</a>
                <a href="pages/auth/login.php" class="text-white-50 small">¿Ya tienes cuenta? Inicia sesión</a>
            <?php else: ?>
                <a href="pages/profesores/catalogo.php" class="btn btn-outline-white btn-sm px-4">Ver todos los profesores</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     CARRUSEL: Profesores de Élite
═══════════════════════════════════════════ -->
<?php if (!empty($top_profesores)): ?>
<section class="py-5" style="background:#111;">
    <div class="container">
        <div class="text-center mb-4">
            <span class="badge text-bg-danger mb-2 px-3 py-2">TOP RATED</span>
            <h2 class="fw-bold text-white mb-0">Profesores de <span class="text-golf">Élite</span></h2>
        </div>

        <div id="carouselElite" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4500">

            <div class="carousel-indicators">
                <?php foreach ($top_profesores as $i => $_): ?>
                    <button type="button" data-bs-target="#carouselElite" data-bs-slide-to="<?php echo $i; ?>"
                            <?php echo $i === 0 ? 'class="active" aria-current="true"' : ''; ?>></button>
                <?php endforeach; ?>
            </div>

            <div class="carousel-inner py-4">
                <?php foreach ($top_profesores as $i => $p): ?>
                    <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                        <div class="d-flex justify-content-center">
                            <div class="card border-0 shadow-lg text-center carousel-card">
                                <div class="card-body p-5">
                                    <!-- Avatar -->
                                    <?php if (!empty($p['foto_perfil'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($p['foto_perfil']); ?>"
                                             class="rounded-circle mb-3" style="width:100px;height:100px;object-fit:cover;" alt="Foto">
                                    <?php else: ?>
                                        <div class="carousel-avatar mx-auto mb-3">
                                            <?php echo strtoupper(mb_substr($p['nombre'], 0, 1) . mb_substr($p['apellidos'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Medalla de posición -->
                                    <div class="position-absolute top-0 start-50 translate-middle-x mt-3">
                                        <span class="badge rounded-pill px-3 py-2"
                                              style="background:var(--golf-red);font-size:.8rem;">
                                            #<?php echo $i + 1; ?> Élite
                                        </span>
                                    </div>

                                    <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellidos']); ?></h4>
                                    <p class="text-muted mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-geo-alt-fill me-1" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                                        <?php echo htmlspecialchars($p['nombre_campo'] . ', ' . $p['ciudad']); ?>
                                    </p>

                                    <div class="mb-3">
                                        <?php
                                        $s = round((float)($p['puntuacion_simulada'] ?? 5));
                                        echo '<span class="text-warning fs-5">' . str_repeat('★', $s) . str_repeat('☆', 5 - $s) . '</span>';
                                        echo ' <span class="fw-bold text-golf">' . number_format($p['puntuacion_simulada'] ?? 5, 1) . '</span>';
                                        ?>
                                    </div>

                                    <?php if (!empty($p['bio'])): ?>
                                        <p class="text-muted small mb-3 px-2">
                                            <?php echo htmlspecialchars(mb_substr($p['bio'], 0, 130)) . '…'; ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="d-flex align-items-center justify-content-center gap-3 mb-4">
                                        <?php if (!empty($p['experiencia_anos'])): ?>
                                            <span class="badge bg-light text-dark border"><?php echo (int)$p['experiencia_anos']; ?> años</span>
                                        <?php endif; ?>
                                        <span class="fs-4 fw-bold text-golf">
                                            <?php echo number_format($p['precio_hora'], 0); ?>€<small class="text-muted fw-normal fs-6">/h</small>
                                        </span>
                                    </div>

                                    <a href="pages/booking/book_class.php?id=<?php echo $p['id_profesor']; ?>"
                                       class="btn btn-golf px-5">
                                        Reservar clase
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#carouselElite" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselElite" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     CÓMO FUNCIONA
═══════════════════════════════════════════ -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-6 fw-bold mb-2">
                Empieza eligiendo tu instructor.
                <span class="text-golf">Comparte tus objetivos,<br>tu nivel y lo que quieres mejorar.</span>
            </h2>
            <p class="text-muted fs-5 col-md-7 mx-auto">
                Recibirás una clase personalizada con feedback específico adaptado a tus metas.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="como-step h-100">
                    <div class="step-num">1</div>
                    <h5 class="fw-bold mt-3 mb-2">Elige tu instructor</h5>
                    <p class="text-muted mb-0">
                        Explora nuestro catálogo, filtra por ciudad o campo de golf y encuentra al profesional que mejor encaje con tu nivel y estilo de aprendizaje.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="como-step h-100">
                    <div class="step-num">2</div>
                    <h5 class="fw-bold mt-3 mb-2">Reserva tu clase</h5>
                    <p class="text-muted mb-0">
                        Selecciona la fecha y hora que mejor te venga. El sistema confirma la disponibilidad en tiempo real. Todo gestionado desde tu perfil.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="como-step h-100">
                    <div class="step-num">3</div>
                    <h5 class="fw-bold mt-3 mb-2">Mejora tu juego</h5>
                    <p class="text-muted mb-0">
                        Acude a tu clase, recibe feedback personalizado y sigue tu progreso. Reserva tu próxima sesión en segundos desde el mismo sitio.
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="pages/profesores/catalogo.php" class="btn btn-golf btn-lg px-5">Explorar instructores</a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     ¿POR QUÉ GOLFCLASS? — Tabla comparativa
═══════════════════════════════════════════ -->
<section class="py-5" style="background:#f8f9fa;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-6 fw-bold mb-2">¿Por qué <span class="text-golf">GolfClass?</span></h2>
            <p class="text-muted fs-5">La forma moderna de aprender golf. Así nos comparamos.</p>
        </div>

        <div class="table-responsive">
            <table class="tabla-comparativa w-100">
                <thead>
                    <tr>
                        <th style="width:34%"></th>
                        <th class="col-tradicional text-center" style="width:22%">Clases tradicionales</th>
                        <th class="col-youtube text-center" style="width:22%">YouTube / Internet</th>
                        <th class="col-golf text-center" style="width:22%"><span class="text-golf fw-bold">GolfClass</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $filas = [
                        ['Personalizado para ti',            true,  false, true],
                        ['Instructores en toda España',      false, false, true],
                        ['Fácil encontrar al instructor ideal', false, false, true],
                        ['Válido para todos los niveles',    true,  false, true],
                        ['Gestión de reservas online',       false, false, true],
                        ['Comunicación directa entre clases',false, false, true],
                        ['Sin desplazamientos innecesarios', false, true,  true],
                    ];
                    foreach ($filas as $f): ?>
                    <tr>
                        <td class="feature-label"><?php echo $f[0]; ?></td>
                        <td class="text-center"><?php echo $f[1] ? '<span class="check-ok">✓</span>' : '<span class="check-no">✕</span>'; ?></td>
                        <td class="text-center"><?php echo $f[2] ? '<span class="check-ok">✓</span>' : '<span class="check-no">✕</span>'; ?></td>
                        <td class="text-center"><?php echo $f[3] ? '<span class="check-golf">✓</span>' : '<span class="check-no">✕</span>'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     MÉTRICAS
═══════════════════════════════════════════ -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-6 fw-bold">Únete a la comunidad que<br><span class="text-golf">está cambiando el golf en España</span></h2>
        </div>
        <div class="row g-4 text-center justify-content-center">
            <?php
            $metricas = [
                ['🏌️', '12+',    'instructores verificados'],
                ['📅', '500+',   'clases completadas'],
                ['⭐', '4.8',    'valoración media'],
                ['⛳', '10+',    'campos de golf'],
                ['🌍', 'España', 'cobertura nacional'],
                ['💸', '0€',     'comisiones ocultas'],
            ];
            foreach ($metricas as $m): ?>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="metrica-card">
                    <div class="metrica-icon"><?php echo $m[0]; ?></div>
                    <div class="metrica-num"><?php echo $m[1]; ?></div>
                    <div class="metrica-label"><?php echo $m[2]; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     FAQs
═══════════════════════════════════════════ -->
<section class="py-5" style="background:#111;">
    <div class="container">
        <h2 class="display-6 fw-bold text-white mb-5">Preguntas frecuentes</h2>

        <?php
        $faqs_col1 = [
            ['¿Qué es GolfClass?',
             'GolfClass es una plataforma online que conecta a alumnos con instructores de golf certificados en toda España. Puedes buscar, comparar y reservar clases directamente desde la web, sin intermediarios.'],
            ['¿Cómo reservo una clase?',
             'Explora el catálogo, elige tu instructor, selecciona la fecha y la hora que prefieras y confirma la reserva. Recibirás la confirmación en tu perfil de inmediato.'],
            ['¿Cuánto cuestan las clases?',
             'Cada instructor fija su propio precio por hora. En nuestro catálogo puedes ver el precio antes de reservar, sin costes adicionales ni sorpresas.'],
            ['¿Cómo elijo al instructor adecuado?',
             'Filtra por ciudad o campo de golf, consulta la biografía y la valoración de cada instructor. Si tienes dudas, prueba con una clase de iniciación.'],
        ];
        $faqs_col2 = [
            ['¿Puedo cancelar mi reserva?',
             'Sí. Desde la sección "Mis Reservas" puedes cancelar cualquier reserva con estado Pendiente. Las reservas ya confirmadas deben gestionarse directamente con el instructor.'],
            ['Soy instructor, ¿cómo me registro?',
             'Haz clic en "¿Eres Coach?" en el menú superior o en el banner de la página principal. El proceso de alta tarda menos de 2 minutos.'],
            ['¿En qué ciudades está disponible?',
             'GolfClass opera en toda España. Actualmente tenemos instructores en Madrid, Barcelona, Valencia, Sevilla, Marbella, Bilbao, San Sebastián, Alicante, Huelva y Sitges, con nuevas incorporaciones cada semana.'],
            ['¿Qué pasa si no quedo satisfecho?',
             'Tu satisfacción es nuestra prioridad. Si una clase no cumple tus expectativas, ponemos en contacto con el instructor para buscar la mejor solución.'],
        ];
        ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="accordion accordion-flush" id="faqCol1">
                    <?php foreach ($faqs_col1 as $i => $faq): ?>
                    <div class="faq-item">
                        <button class="faq-btn collapsed" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faq1_<?php echo $i; ?>">
                            <?php echo $faq[0]; ?>
                            <svg class="faq-chevron" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>
                        </button>
                        <div id="faq1_<?php echo $i; ?>" class="collapse faq-body">
                            <p class="mb-0"><?php echo $faq[1]; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="accordion accordion-flush" id="faqCol2">
                    <?php foreach ($faqs_col2 as $i => $faq): ?>
                    <div class="faq-item">
                        <button class="faq-btn collapsed" type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faq2_<?php echo $i; ?>">
                            <?php echo $faq[0]; ?>
                            <svg class="faq-chevron" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>
                        </button>
                        <div id="faq2_<?php echo $i; ?>" class="collapse faq-body">
                            <p class="mb-0"><?php echo $faq[1]; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════
     CTA BANNER: Captación de Profesores
═══════════════════════════════════════════ -->
<section class="py-5" style="background:#1a1a1a;">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge mb-2 px-3 py-2" style="background:var(--golf-red);font-size:.8rem;">PARA PROFESORES</span>
                <h2 class="fw-bold text-white mb-2">¿Eres profesor de Golf?</h2>
                <p class="text-white-50 fs-5 mb-0">
                    Únete a nuestra red, gestiona tus clases en tiempo real y llega a más alumnos que nunca. Sin comisiones abusivas.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="pages/auth/register.php?role=teacher" class="btn btn-golf btn-lg px-5 py-3">
                    Regístrate como Profesor
                </a>
                <p class="text-white-50 small mt-2 mb-0">Es gratis. Empieza hoy.</p>
            </div>
        </div>
    </div>
</section>

<style>
    /* ── Hero ── */
    .hero-search-group { border-radius: 10px; overflow: hidden; max-width: 620px; }
    .hero-search-group .form-control:focus { box-shadow: none; }

    /* ── Carrusel ── */
    .carousel-card { max-width: 440px; width: 100%; position: relative; border-radius: 16px; }
    .carousel-avatar {
        width: 100px; height: 100px; background: var(--golf-red); color: white;
        font-weight: 700; font-size: 2rem; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }

    /* ── Cómo funciona ── */
    .como-step {
        background: #f8f9fa;
        border-radius: 14px;
        padding: 2rem;
        border-top: 4px solid var(--golf-red);
    }
    .step-num {
        width: 48px; height: 48px; border-radius: 50%;
        background: var(--golf-red); color: white;
        font-weight: 700; font-size: 1.3rem;
        display: flex; align-items: center; justify-content: center;
    }

    /* ── Tabla comparativa ── */
    .tabla-comparativa { border-collapse: separate; border-spacing: 0; }
    .tabla-comparativa thead th {
        padding: 1rem 1.5rem;
        font-size: .85rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #555;
    }
    .col-tradicional { background: #fdf8ec; border-radius: 8px 8px 0 0; color: #997a1f !important; }
    .col-youtube     { background: #eef0f8; border-radius: 8px 8px 0 0; color: #3c4a8a !important; }
    .col-golf        { background: #fff0f0; border-radius: 8px 8px 0 0; }
    .tabla-comparativa tbody tr { border-bottom: 1px solid #eee; }
    .tabla-comparativa tbody td { padding: .9rem 1.5rem; font-size: .95rem; }
    .feature-label { font-weight: 500; color: #333; }
    .check-ok   { color: #28a745; font-size: 1.1rem; font-weight: 700; }
    .check-no   { color: #dc3545; font-size: 1.1rem; font-weight: 700; }
    .check-golf { color: var(--golf-red); font-size: 1.1rem; font-weight: 700; }

    /* ── Métricas ── */
    .metrica-card { padding: 1.5rem .5rem; }
    .metrica-icon { font-size: 2rem; margin-bottom: .5rem; }
    .metrica-num  { font-size: 2rem; font-weight: 700; color: var(--golf-dark); line-height: 1; }
    .metrica-label { font-size: .85rem; color: #666; margin-top: .25rem; }

    /* ── FAQs ── */
    .faq-item { border-bottom: 1px solid rgba(255,255,255,.1); }
    .faq-btn {
        width: 100%;
        background: none;
        border: none;
        color: white;
        font-size: 1rem;
        font-weight: 500;
        padding: 1.25rem 0;
        text-align: left;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
        transition: color .2s;
    }
    .faq-btn:not(.collapsed) { color: var(--golf-red); }
    .faq-chevron {
        flex-shrink: 0;
        transition: transform .25s;
    }
    .faq-btn:not(.collapsed) .faq-chevron { transform: rotate(180deg); }
    .faq-body { color: rgba(255,255,255,.6); padding-bottom: 1.25rem; font-size: .95rem; line-height: 1.7; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
