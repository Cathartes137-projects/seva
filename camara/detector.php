<?php
// =============================================================================
// camara/detector.php - Detector de manos (MediaPipe Hands)
// =============================================================================
// Módulo aislado en su propia carpeta para que su CSP y su permiso de cámara
// no afecten al resto del sitio. La lógica de cámara/MediaPipe se conserva
// tal como la entregó la rama seva-mary V-2-2.1; queda pendiente su fase de
// revisión específica.
// =============================================================================

require __DIR__ . '/../conexion.php';
require __DIR__ . '/../seguridad.php';

seguridad_iniciar_sesion();

// La página vive en /camara/: el redirect relativo de seguridad.php apuntaría
// a /camara/login.php. Se conserva la misma comprobación con la ruta correcta.
if (empty($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

$usuario_id = (int) $_SESSION['usuario_id'];
$nombre     = $_SESSION['nombre'] ?? 'Estudiante';
$base       = '../';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Detector de manos · SEVA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/estilos.css">
<style>
    .detector { max-width: 1100px; margin: auto; padding: 1.5rem; }
    .hero-d { background: var(--ink); color: #fff; padding: 1.5rem; border-radius: 1.2rem; }
    .hero-d h1 { color: #fff; }
    .hero-d p { color: #C9D6EC; margin-bottom: 0; }
    .aviso-modulo { margin-top: .8rem; padding: .65rem .85rem; border-radius: .7rem; background: var(--alerta-amarillo-bg); color: var(--alerta-amarillo); border-left: 5px solid var(--alerta-amarillo-line); font-size: .9rem; }
    .hero-d p.aviso-modulo { color: var(--alerta-amarillo); }
    .grid-d { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(260px, 1fr); gap: 1rem; margin-top: 1rem; }
    .panel-d { background: #fff; border: 1px solid var(--line); border-radius: 1rem; padding: 1rem; box-shadow: var(--sombra); }
    .video-wrap { position: relative; background: #071426; border-radius: .8rem; overflow: hidden; min-height: 260px; aspect-ratio: 4/3; }
    .video-wrap video, .video-wrap canvas { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
    .video-wrap canvas { pointer-events: none; }
    .btn-d { border: 0; border-radius: .7rem; min-height: 44px; padding: .75rem 1rem; background: var(--azul); color: #fff; font-weight: 700; cursor: pointer; margin: .25rem; }
    .btn-d.alt { background: var(--verde-oscuro); }
    .btn-d:disabled { opacity: .5; cursor: not-allowed; }
    .stat { padding: .7rem; background: var(--nivel-intermedio-bg); border-radius: .6rem; margin: .5rem 0; }
    .note { font-size: .92rem; color: #4C5670; line-height: 1.5; }
    .progress { height: 8px; background: var(--line); border-radius: 99px; overflow: hidden; }
    .progress span { display: block; height: 100%; width: 0; background: var(--verde-oscuro); transition: width .15s; }
    @media (max-width: 760px) { .grid-d { grid-template-columns: 1fr; } }
</style>
</head>
<body class="pagina-camara">
<?php require __DIR__ . '/../partials/topbar.php'; ?>

<main class="detector" id="contenido">
    <section class="hero-d">
        <h1>Detector de manos de SEVA ✋</h1>
        <p>Observa los puntos de referencia de tus manos para practicar la colocación y el movimiento.</p>
        <p class="aviso-modulo"><strong>Módulo en revisión:</strong> la detección puede variar según la cámara, la luz y la conexión. Se ajustará en una fase específica; por ahora sirve como referencia visual.</p>
    </section>

    <div class="grid-d">
        <section class="panel-d" aria-labelledby="titulo-camara">
            <h2 id="titulo-camara">Cámara en vivo</h2>
            <p class="note">Ubica las dos manos completas dentro del encuadre, con buena luz y el fondo despejado.</p>
            <div class="video-wrap">
                <video id="video" playsinline muted aria-label="Vista de la cámara con puntos de referencia de las manos"></video>
                <canvas id="overlay" aria-hidden="true"></canvas>
            </div>
            <button type="button" class="btn-d alt" id="start">Activar cámara</button>
            <button type="button" class="btn-d" id="stop" disabled>Detener cámara</button>
            <p id="estado" role="status" aria-live="polite">Cámara apagada.</p>
        </section>

        <aside class="panel-d" aria-labelledby="titulo-lectura">
            <h2 id="titulo-lectura">Lectura de puntos</h2>
            <div class="stat">Manos detectadas: <strong id="manos">0 / 2</strong></div>
            <div class="stat">Puntos detectados: <strong id="puntos">0 / 42</strong></div>
            <div class="progress" role="progressbar" aria-label="Puntos detectados" aria-valuemin="0" aria-valuemax="42" aria-valuenow="0" id="barra-progreso"><span id="barra"></span></div>
            <p id="detalle" aria-live="polite">Activa la cámara para comenzar.</p>
            <h3>Consejos para mejorar</h3>
            <ul>
                <li>Mantén las manos completas dentro de la imagen.</li>
                <li>Evita contraluz y movimientos demasiado rápidos.</li>
                <li>Separa un poco las manos para que no se tapen entre sí.</li>
                <li>Coloca la cámara a la altura del pecho o las manos.</li>
            </ul>
            <p class="note"><strong>Importante:</strong> detectar puntos no equivale a reconocer una seña ni a evaluar si está correcta. La LSC incluye configuración, orientación, ubicación, movimiento y expresiones no manuales; se necesita contenido validado y un modelo entrenado para calificar señas.</p>
            <a class="btn-d" href="../practica.php">Volver a practicar</a>
        </aside>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/@mediapipe/hands/hands.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
<script>
const video=document.getElementById('video'),canvas=document.getElementById('overlay'),ctx=canvas.getContext('2d');
const estado=document.getElementById('estado'),manos=document.getElementById('manos'),puntos=document.getElementById('puntos'),barra=document.getElementById('barra'),detalle=document.getElementById('detalle');
const barraProgreso=document.getElementById('barra-progreso');
const btnIniciar=document.getElementById('start'),btnDetener=document.getElementById('stop');
let hands=null,camera=null,active=false,starting=false;
const LINKS=[[0,1],[1,2],[2,3],[3,4],[0,5],[5,6],[6,7],[7,8],[5,9],[9,10],[10,11],[11,12],[9,13],[13,14],[14,15],[15,16],[13,17],[17,18],[18,19],[19,20],[0,17]];
function setEstado(t){estado.textContent=t}
function resetStats(){manos.textContent='0 / 2';puntos.textContent='0 / 42';barra.style.width='0%';if(barraProgreso)barraProgreso.setAttribute('aria-valuenow','0')}
function drawConnections(lm,color){
  ctx.strokeStyle=color;ctx.lineWidth=3;
  LINKS.forEach(([a,b])=>{ctx.beginPath();ctx.moveTo(lm[a].x*canvas.width,lm[a].y*canvas.height);ctx.lineTo(lm[b].x*canvas.width,lm[b].y*canvas.height);ctx.stroke()});
  lm.forEach((p,i)=>{ctx.beginPath();ctx.arc(p.x*canvas.width,p.y*canvas.height,i%4===0?5:3,0,Math.PI*2);ctx.fillStyle=i%4===0?'#ffb400':'#28a745';ctx.fill()});
}
function onResults(results){
  if(!active)return;
  const w=video.videoWidth||640,h=video.videoHeight||480;
  if(canvas.width!==w||canvas.height!==h){canvas.width=w;canvas.height=h}
  ctx.clearRect(0,0,w,h);
  const list=results.multiHandLandmarks||[];
  list.forEach((lm,i)=>drawConnections(lm,i===0?'#35d07f':'#57b7ff'));
  const n=Math.min(list.length,2),p=n*21;
  manos.textContent=n+' / 2';puntos.textContent=p+' / 42';barra.style.width=(p/42*100)+'%';
  if(barraProgreso)barraProgreso.setAttribute('aria-valuenow',String(p));
  detalle.textContent=n===2?'¡Ambas manos detectadas! Se ven los 21 puntos de cada mano.':n===1?'Se detectó una mano. Muestra la otra completa si la seña la necesita.':'No se detectan manos; acércalas y mejora la luz.';
}
async function iniciar(){
  if(active||starting)return;
  if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){setEstado('Tu navegador no permite usar la cámara. Abre el sitio desde http://localhost o HTTPS, con Chrome o Edge.');return}
  if(!window.Hands||!window.Camera){setEstado('No se pudo cargar MediaPipe. Comprueba tu conexión a Internet.');return}
  starting=true;btnIniciar.disabled=true;setEstado('Solicitando permiso para usar la cámara…');
  try{
    hands=new Hands({locateFile:(file)=>`https://cdn.jsdelivr.net/npm/@mediapipe/hands/${file}`});
    hands.setOptions({maxNumHands:2,modelComplexity:1,minDetectionConfidence:.65,minTrackingConfidence:.6});
    hands.onResults(onResults);
    camera=new Camera(video,{onFrame:async()=>{if(active&&hands)await hands.send({image:video})},width:1280,height:720});
    active=true;await camera.start();
    btnDetener.disabled=false;setEstado('Cámara activa. Coloca las manos dentro del recuadro.');
  }catch(e){
    detener();
    setEstado(e&&e.name==='NotAllowedError'?'Permiso denegado. Autoriza la cámara en el navegador y vuelve a intentarlo.':e&&e.name==='NotFoundError'?'No se encontró ninguna cámara conectada.':'No se pudo iniciar: '+((e&&e.message)||'revisa la cámara y la conexión.'));
  }finally{starting=false}
}
function detener(){
  active=false;
  try{camera&&camera.stop()}catch(_){}
  if(video.srcObject){video.srcObject.getTracks().forEach(t=>t.stop());video.srcObject=null}
  try{hands&&hands.close()}catch(_){}
  hands=null;camera=null;
  ctx.clearRect(0,0,canvas.width,canvas.height);resetStats();
  detalle.textContent='Activa la cámara para comenzar.';
  btnIniciar.disabled=false;btnDetener.disabled=true;setEstado('Cámara apagada.');
}
btnIniciar.addEventListener('click',iniciar);
btnDetener.addEventListener('click',detener);
window.addEventListener('pagehide',detener);
</script>
<script src="../js/seva-ui.js"></script>
<script>
if (window.SEVA) {
    SEVA.init(<?= $usuario_id ?>);
    SEVA.menu();
}
</script>
</body>
</html>
