<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /GolfClass/pages/auth/login.php"); exit();
}

$yo       = (int) $_SESSION['user_id'];
$id_otro  = filter_input(INPUT_GET, 'con', FILTER_VALIDATE_INT);

if (!$id_otro || $id_otro === $yo) {
    header("Location: index.php"); exit();
}

// Datos del interlocutor
$stmt = $pdo->prepare("SELECT u.id_usuario, u.nombre, u.apellidos, r.nombre_rol FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol WHERE u.id_usuario = ? AND u.activo = 1");
$stmt->execute([$id_otro]);
$otro = $stmt->fetch();
if (!$otro) { header("Location: index.php"); exit(); }

// Marcar como leídos los mensajes recibidos
$pdo->prepare("UPDATE mensajes SET leido = 1 WHERE id_emisor = ? AND id_receptor = ? AND leido = 0")
    ->execute([$id_otro, $yo]);

// Cargar historial de mensajes
$stmt = $pdo->prepare("
    SELECT id_mensaje, id_emisor, mensaje, leido, fecha_envio
    FROM mensajes
    WHERE (id_emisor = ? AND id_receptor = ?)
       OR (id_emisor = ? AND id_receptor = ?)
    ORDER BY fecha_envio ASC
");
$stmt->execute([$yo, $id_otro, $id_otro, $yo]);
$mensajes = $stmt->fetchAll();
$ultimo_id = empty($mensajes) ? 0 : end($mensajes)['id_mensaje'];

include '../../includes/header.php';
?>

<div class="chat-wrapper d-flex flex-column" style="height:calc(100vh - 56px);">

    <!-- Cabecera del chat -->
    <div class="chat-header d-flex align-items-center gap-3 px-3 py-2 border-bottom bg-white shadow-sm">
        <a href="index.php" class="btn btn-link text-dark p-0 me-1" title="Volver">←</a>
        <div class="chat-avatar-sm">
            <?php echo strtoupper(mb_substr($otro['nombre'],0,1).mb_substr($otro['apellidos']??'',0,1)); ?>
        </div>
        <div>
            <strong class="d-block"><?php echo htmlspecialchars($otro['nombre'].' '.($otro['apellidos']??'')); ?></strong>
            <small class="text-muted"><?php echo htmlspecialchars($otro['nombre_rol']); ?></small>
        </div>
    </div>

    <!-- Área de mensajes -->
    <div class="chat-messages flex-grow-1 overflow-auto p-3" id="chatMessages">
        <div id="msgContainer">
            <?php foreach ($mensajes as $m): ?>
                <?php $propio = ($m['id_emisor'] === $yo); ?>
                <div class="msg-row <?php echo $propio ? 'msg-propio' : 'msg-otro'; ?> mb-2" data-id="<?php echo $m['id_mensaje']; ?>">
                    <div class="msg-bubble">
                        <?php echo nl2br(htmlspecialchars($m['mensaje'])); ?>
                    </div>
                    <div class="msg-meta">
                        <?php echo date('H:i', strtotime($m['fecha_envio'])); ?>
                        <?php if ($propio): ?>
                            <span class="ms-1"><?php echo $m['leido'] ? '✓✓' : '✓'; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($mensajes)): ?>
            <p class="text-center text-muted py-5 small" id="emptyMsg">
                Sé el primero en escribir 👋
            </p>
        <?php endif; ?>
    </div>

    <!-- Input de mensaje -->
    <div class="chat-input-bar border-top bg-white p-2">
        <form id="formChat" class="d-flex gap-2 align-items-end">
            <textarea id="inputMsg" class="form-control" rows="1"
                      placeholder="Escribe un mensaje…"
                      style="resize:none;max-height:120px;overflow-y:auto;"
                      required></textarea>
            <button type="submit" class="btn btn-golf px-3" style="height:42px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576zm6.787-8.201L2.758 6.808l4.33 2.758z"/>
                </svg>
            </button>
        </form>
    </div>
</div>

<style>
    body { overflow: hidden; }

    .chat-avatar-sm {
        width: 40px; height: 40px; border-radius: 50%;
        background: var(--golf-red); color: white;
        font-weight: 700; font-size: .95rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .chat-messages { background: #f0f2f5; }

    .msg-row { display: flex; flex-direction: column; max-width: 75%; }
    .msg-propio { align-self: flex-end; align-items: flex-end; margin-left: auto; }
    .msg-otro   { align-self: flex-start; align-items: flex-start; }

    .msg-bubble {
        padding: .55rem .9rem;
        border-radius: 18px;
        font-size: .92rem;
        line-height: 1.45;
        word-break: break-word;
    }
    .msg-propio .msg-bubble {
        background: var(--golf-red);
        color: white;
        border-bottom-right-radius: 4px;
    }
    .msg-otro .msg-bubble {
        background: white;
        color: #222;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,.08);
    }
    .msg-meta {
        font-size: .68rem;
        color: #999;
        margin-top: 2px;
        padding: 0 4px;
    }
    .chat-input-bar textarea:focus { box-shadow: none; border-color: var(--golf-red); }
</style>

<script>
const YO       = <?php echo $yo; ?>;
const ID_OTRO  = <?php echo $id_otro; ?>;
let ultimoId   = <?php echo $ultimo_id; ?>;
let polling;

const msgContainer = document.getElementById('msgContainer');
const chatMessages = document.getElementById('chatMessages');
const inputMsg     = document.getElementById('inputMsg');

// Scroll al último mensaje
function scrollBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
scrollBottom();

// Construir HTML de un mensaje
function buildMsgHTML(m) {
    const propio  = (m.id_emisor === YO);
    const rowCls  = propio ? 'msg-propio' : 'msg-otro';
    const bubCls  = propio ? 'msg-propio' : 'msg-otro';
    const tick    = propio ? (m.leido ? '✓✓' : '✓') : '';
    const texto   = m.mensaje.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
    return `
        <div class="msg-row ${rowCls} mb-2" data-id="${m.id_mensaje}">
            <div class="msg-bubble">${texto}</div>
            <div class="msg-meta">${m.hora}${propio ? ' <span class="ms-1">'+tick+'</span>' : ''}</div>
        </div>`;
}

// Enviar mensaje
document.getElementById('formChat').addEventListener('submit', async (e) => {
    e.preventDefault();
    const texto = inputMsg.value.trim();
    if (!texto) return;
    inputMsg.value = '';
    inputMsg.style.height = 'auto';

    const fd = new FormData();
    fd.append('id_receptor', ID_OTRO);
    fd.append('mensaje', texto);

    const res  = await fetch('enviar.php', { method:'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
        document.getElementById('emptyMsg')?.remove();
        msgContainer.insertAdjacentHTML('beforeend', buildMsgHTML(data.msg));
        ultimoId = data.msg.id_mensaje;
        scrollBottom();
    }
});

// Auto-resize textarea
inputMsg.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Enviar con Enter (Shift+Enter = nueva línea)
inputMsg.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('formChat').requestSubmit();
    }
});

// Polling de nuevos mensajes
async function pollNuevos() {
    try {
        const res  = await fetch(`nuevos.php?con=${ID_OTRO}&desde=${ultimoId}`);
        const data = await res.json();
        if (data.mensajes && data.mensajes.length) {
            document.getElementById('emptyMsg')?.remove();
            data.mensajes.forEach(m => {
                msgContainer.insertAdjacentHTML('beforeend', buildMsgHTML(m));
                ultimoId = m.id_mensaje;
            });
            scrollBottom();
        }
    } catch(e) { /* red no disponible */ }
}

polling = setInterval(pollNuevos, 3000);
// Limpiar al salir
window.addEventListener('beforeunload', () => clearInterval(polling));
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
