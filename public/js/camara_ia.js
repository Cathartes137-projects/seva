/**
 * SEVA · Sistema de Reconocimiento de Lengua de Señas con IA y 21 Puntos de la Mano
 * Compatible con MediaPipe Hands y Motor de Visión Artificial Adaptativo.
 * Evalúa señas dactilológicas y de colores en tiempo real.
 * Indica con precisión si la seña ESTÁ BIEN o ESTÁ MALA con retroalimentación anatómica dedo por dedo.
 */

(function (global) {
    'use strict';

    // 21 articulaciones de la mano (Estándar MediaPipe Hands)
    const LANDMARKS_MAP = {
        WRIST: 0,
        THUMB_CMC: 1, THUMB_MCP: 2, THUMB_IP: 3, THUMB_TIP: 4,
        INDEX_MCP: 5, INDEX_PIP: 6, INDEX_DIP: 7, INDEX_TIP: 8,
        MIDDLE_MCP: 9, MIDDLE_PIP: 10, MIDDLE_DIP: 11, MIDDLE_TIP: 12,
        RING_MCP: 13, RING_PIP: 14, RING_DIP: 15, RING_TIP: 16,
        PINKY_MCP: 17, PINKY_PIP: 18, PINKY_DIP: 19, PINKY_TIP: 20
    };

    // Conexiones óseas
    const HAND_CONNECTIONS = [
        [0, 1], [1, 2], [2, 3], [3, 4],        // Pulgar
        [0, 5], [5, 6], [6, 7], [7, 8],        // Índice
        [5, 9], [9, 10], [10, 11], [11, 12],   // Medio
        [9, 13], [13, 14], [14, 15], [15, 16], // Anular
        [13, 17], [17, 18], [18, 19], [19, 20],// Meñique
        [0, 17]                                // Muñeca
    ];

    // Sintetizador de audio con Web Audio API (efectos auditivos nativos)
    class AudioFeedback {
        constructor() {
            this.ctx = null;
            this.ultimoErrorSonado = 0;
        }
        init() {
            if (!this.ctx) {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (AudioCtx) this.ctx = new AudioCtx();
            }
            if (this.ctx && this.ctx.state === 'suspended') {
                this.ctx.resume().catch(() => {});
            }
        }
        playTone(freq, duration = 0.12, type = 'sine', gainVal = 0.15) {
            try {
                this.init();
                if (!this.ctx) return;
                const osc = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.type = type;
                osc.frequency.setValueAtTime(freq, this.ctx.currentTime);
                gain.gain.setValueAtTime(gainVal, this.ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, this.ctx.currentTime + duration);
                osc.connect(gain);
                gain.connect(this.ctx.destination);
                osc.start();
                osc.stop(this.ctx.currentTime + duration);
            } catch (e) {}
        }
        playSuccess() {
            this.playTone(523.25, 0.1, 'triangle', 0.20); // Do5
            setTimeout(() => this.playTone(659.25, 0.1, 'triangle', 0.20), 90); // Mi5
            setTimeout(() => this.playTone(783.99, 0.22, 'triangle', 0.25), 180); // Sol5
        }
        playWrong() {
            const ahora = Date.now();
            if (ahora - this.ultimoErrorSonado < 1600) return; // evitar saturar
            this.ultimoErrorSonado = ahora;
            this.playTone(260, 0.12, 'sawtooth', 0.08);
            setTimeout(() => this.playTone(210, 0.16, 'sawtooth', 0.08), 110);
        }
        playFanfare() {
            const notas = [523.25, 659.25, 783.99, 1046.50];
            notas.forEach((n, i) => {
                setTimeout(() => this.playTone(n, i === 3 ? 0.45 : 0.14, 'triangle', 0.22), i * 120);
            });
        }
    }

    // Clasificador geométrico y cinemático de señas (Alfabeto y Colores)
    class SignClassifier {
        constructor() {
            this.landmarksSuavizados = null;
        }

        dist(p1, p2) {
            const dx = p1.x - p2.x;
            const dy = p1.y - p2.y;
            const dz = (p1.z || 0) - (p2.z || 0);
            return Math.sqrt(dx * dx + dy * dy + dz * dz);
        }

        calcularAngulo(a, b, c) {
            // Ángulo en articulación b entre los segmentos ba y bc
            const ab = { x: a.x - b.x, y: a.y - b.y, z: (a.z || 0) - (b.z || 0) };
            const cb = { x: c.x - b.x, y: c.y - b.y, z: (c.z || 0) - (b.z || 0) };
            const dot = ab.x * cb.x + ab.y * cb.y + ab.z * cb.z;
            const magAB = Math.sqrt(ab.x * ab.x + ab.y * ab.y + ab.z * ab.z);
            const magCB = Math.sqrt(cb.x * cb.x + cb.y * cb.y + cb.z * cb.z);
            if (magAB * magCB === 0) return 180;
            const cosAngle = Math.max(-1, Math.min(1, dot / (magAB * magCB)));
            return (Math.acos(cosAngle) * 180) / Math.PI;
        }

        evaluarDedoExtendido(landmarks, tipIdx, dipIdx, pipIdx, mcpIdx) {
            const wrist = landmarks[0];
            const tip = landmarks[tipIdx];
            const pip = landmarks[pipIdx];
            const mcp = landmarks[mcpIdx];

            const distTipWrist = this.dist(tip, wrist);
            const distPipWrist = this.dist(pip, wrist);
            const distMcpWrist = this.dist(mcp, wrist);

            // Ángulo en la articulación PIP (dedo extendido suele superar 140°)
            const angulo = this.calcularAngulo(mcp, pip, tip);

            const estiradoDistancia = distTipWrist > distPipWrist && distTipWrist > (distMcpWrist * 1.22);
            const estiradoAngulo = angulo > 135;
            const estiradoVertical = tip.y < pip.y;

            return (estiradoDistancia && estiradoAngulo) || (estiradoVertical && distTipWrist > distPipWrist);
        }

        evaluarPulgarExtendido(landmarks) {
            const wrist = landmarks[0];
            const thumbTip = landmarks[4];
            const thumbMcp = landmarks[2];
            const indexMcp = landmarks[5];
            const pinkyMcp = landmarks[17];

            const distToIndex = this.dist(thumbTip, indexMcp);
            const distToWrist = this.dist(thumbTip, wrist);
            const distMcpToWrist = this.dist(thumbMcp, wrist);
            const distToPinky = this.dist(thumbTip, pinkyMcp);

            // Pulgar extendido si se separa claramente de la palma y del índice
            return distToWrist > (distMcpToWrist * 1.15) && (distToIndex > 0.10 || distToPinky > 0.22);
        }

        analizarMano(landmarks) {
            if (!landmarks || landmarks.length < 21) return null;

            const pulgarExt = this.evaluarPulgarExtendido(landmarks);
            const indiceExt = this.evaluarDedoExtendido(landmarks, 8, 7, 6, 5);
            const medioExt  = this.evaluarDedoExtendido(landmarks, 12, 11, 10, 9);
            const anularExt = this.evaluarDedoExtendido(landmarks, 16, 15, 14, 13);
            const meniqueExt= this.evaluarDedoExtendido(landmarks, 20, 19, 18, 17);

            const escalaPalma = Math.max(0.08, this.dist(landmarks[0], landmarks[9]));
            const dPulgarIndice = this.dist(landmarks[4], landmarks[8]) / escalaPalma;
            const dIndiceMedio  = this.dist(landmarks[8], landmarks[12]) / escalaPalma;
            const dPulgarMedio  = this.dist(landmarks[4], landmarks[12]) / escalaPalma;
            const dPulgarMenique= this.dist(landmarks[4], landmarks[20]) / escalaPalma;

            return {
                pulgar: pulgarExt,
                indice: indiceExt,
                medio: medioExt,
                anular: anularExt,
                menique: meniqueExt,
                escalaPalma,
                dPulgarIndice,
                dIndiceMedio,
                dPulgarMedio,
                dPulgarMenique,
                landmarks
            };
        }

        evaluarSeña(landmarks, objetivo) {
            const info = this.analizarMano(landmarks);
            if (!info) {
                return {
                    estado: 'sin_mano',
                    esCorrecta: false,
                    score: 0,
                    veredicto: 'MUESTRA TU MANO',
                    consejo: 'Coloca tu mano frente al lente para que la cámara pueda leer los 21 puntos.',
                    dedosEstado: null
                };
            }

            const OBJ = (objetivo || 'A').toUpperCase();
            let score = 50;
            let esCorrecta = false;
            let veredicto = 'ESTÁ MALA';
            let consejo = '';
            let dedosEsperados = { pulgar: false, indice: false, medio: false, anular: false, menique: false };

            const { pulgar, indice, medio, anular, menique, dPulgarIndice, dIndiceMedio } = info;
            const totalExt = [indice, medio, anular, menique].filter(Boolean).length;

            switch (OBJ) {
                // ==================== ALFABETO DACTILOLÓGICO ====================
                case 'A':
                    // Puño cerrado, pulgar al costado hacia arriba
                    dedosEsperados = { pulgar: true, indice: false, medio: false, anular: false, menique: false };
                    if (totalExt === 0 && pulgar) {
                        score = 96; esCorrecta = true;
                        consejo = '¡Excelente! Puño bien cerrado con el pulgar al costado.';
                    } else if (totalExt === 0 && !pulgar) {
                        score = 65;
                        consejo = 'Toca mejorar: saca el dedo pulgar hacia el costado del puño.';
                    } else {
                        score = Math.max(20, 60 - totalExt * 12);
                        consejo = 'Toca mejorar: cierra todos los dedos en puño, no dejes dedos levantados.';
                    }
                    break;

                case 'B':
                    // Cuatro dedos rectos juntos hacia arriba, pulgar doblado en la palma
                    dedosEsperados = { pulgar: false, indice: true, medio: true, anular: true, menique: true };
                    if (indice && medio && anular && menique) {
                        if (!pulgar && dIndiceMedio < 0.95) {
                            score = 98; esCorrecta = true;
                            consejo = '¡Perfecto! Cuatro dedos rectos y pulgar bien doblado en la palma.';
                        } else if (pulgar) {
                            score = 72;
                            consejo = 'Toca mejorar: dobla el pulgar cruzándolo por delante de la palma.';
                        } else {
                            score = 85; esCorrecta = true;
                            consejo = '¡Muy bien! Mantén los dedos juntos y estirados.';
                        }
                    } else {
                        score = Math.max(25, totalExt * 18);
                        consejo = 'Toca mejorar: extiende rectos los 4 dedos hacia arriba.';
                    }
                    break;

                case 'C':
                case 'CAFE':
                    // Todos los dedos y pulgar curvados en forma de C
                    dedosEsperados = { pulgar: true, indice: false, medio: false, anular: false, menique: false };
                    if (dPulgarIndice > 0.40 && dPulgarIndice < 1.7 && !indice && !medio) {
                        score = 94; esCorrecta = true;
                        consejo = '¡Excelente forma de C con la mano curvada!';
                    } else {
                        score = 60;
                        consejo = 'Toca mejorar: curva los dedos y el pulgar dejando un arco en forma de C.';
                    }
                    break;

                case 'D':
                    // Índice hacia arriba, los demás tocando el pulgar
                    dedosEsperados = { pulgar: false, indice: true, medio: false, anular: false, menique: false };
                    if (indice && !medio && !anular && !menique) {
                        score = 96; esCorrecta = true;
                        consejo = '¡Excelente! Dedo índice recto hacia arriba y los demás recogidos.';
                    } else if (indice && medio) {
                        score = 55;
                        consejo = 'Toca mejorar: baja el dedo medio, solo el índice va hacia arriba.';
                    } else {
                        score = 40;
                        consejo = 'Toca mejorar: levanta solo el dedo índice apuntando al cielo.';
                    }
                    break;

                case 'E':
                    // Cuatro dedos doblados en gancho hacia abajo, pulgar recogido
                    dedosEsperados = { pulgar: false, indice: false, medio: false, anular: false, menique: false };
                    if (totalExt === 0 && !pulgar) {
                        score = 92; esCorrecta = true;
                        consejo = '¡Excelente! Dedos doblados en gancho sobre el pulgar.';
                    } else {
                        score = 50;
                        consejo = 'Toca mejorar: dobla todos los dedos hacia la palma apoyando las yemas.';
                    }
                    break;

                case 'F':
                    // Índice y pulgar tocándose en aro, los otros 3 extendidos
                    dedosEsperados = { pulgar: false, indice: false, medio: true, anular: true, menique: true };
                    if (dPulgarIndice < 0.85 && medio && anular && menique) {
                        score = 96; esCorrecta = true;
                        consejo = '¡Excelente! Aro con índice y pulgar, y 3 dedos rectos arriba.';
                    } else if (medio && anular && menique) {
                        score = 70;
                        consejo = 'Toca mejorar: une la yema del pulgar con la del índice formando un círculo.';
                    } else {
                        score = 45;
                        consejo = 'Toca mejorar: levanta los dedos medio, anular y meñique.';
                    }
                    break;

                case 'G':
                    // Índice hacia el lado, otros cerrados
                    dedosEsperados = { pulgar: false, indice: true, medio: false, anular: false, menique: false };
                    if (indice && !medio && !anular && !menique) {
                        score = 92; esCorrecta = true;
                        consejo = '¡Muy bien! Índice apuntando hacia el costado.';
                    } else {
                        score = 48;
                        consejo = 'Toca mejorar: apunta el dedo índice hacia el lado con los demás en puño.';
                    }
                    break;

                case 'H':
                    // Índice y medio juntos hacia el lado
                    dedosEsperados = { pulgar: false, indice: true, medio: true, anular: false, menique: false };
                    if (indice && medio && !anular && !menique) {
                        score = 94; esCorrecta = true;
                        consejo = '¡Excelente! Índice y medio juntos hacia el costado.';
                    } else {
                        score = 50;
                        consejo = 'Toca mejorar: extiende el índice y medio juntos hacia el lado.';
                    }
                    break;

                case 'I':
                case 'NEGRO':
                    // Solo meñique extendido hacia arriba
                    dedosEsperados = { pulgar: false, indice: false, medio: false, anular: false, menique: true };
                    if (menique && !indice && !medio && !anular) {
                        score = 96; esCorrecta = true;
                        consejo = '¡Perfecto! Solo el meñique levantado hacia arriba.';
                    } else if (menique && (indice || medio)) {
                        score = 58;
                        consejo = 'Toca mejorar: cierra el índice y el medio, deja solo el meñique.';
                    } else {
                        score = 40;
                        consejo = 'Toca mejorar: levanta solo el dedo meñique.';
                    }
                    break;

                case 'J':
                    dedosEsperados = { pulgar: false, indice: false, medio: false, anular: false, menique: true };
                    if (menique && !indice && !medio) {
                        score = 90; esCorrecta = true;
                        consejo = '¡Bien! Con el meñique arriba, haz el trazo curvo de la J.';
                    } else {
                        score = 45;
                        consejo = 'Toca mejorar: levanta el meñique y traza una curva en el aire.';
                    }
                    break;

                case 'K':
                    dedosEsperados = { pulgar: true, indice: true, medio: true, anular: false, menique: false };
                    if (indice && medio && pulgar && !anular && !menique) {
                        score = 94; esCorrecta = true;
                        consejo = '¡Excelente postura en forma de K!';
                    } else {
                        score = 55;
                        consejo = 'Toca mejorar: levanta el índice y medio con el pulgar apoyado en medio.';
                    }
                    break;

                case 'L':
                    // Índice hacia arriba y pulgar a 90° formando una L
                    dedosEsperados = { pulgar: true, indice: true, medio: false, anular: false, menique: false };
                    if (indice && pulgar && !medio && !anular && !menique) {
                        score = 98; esCorrecta = true;
                        consejo = '¡Perfecto! Forma exacta de L con el índice y pulgar.';
                    } else if (indice && !pulgar) {
                        score = 65;
                        consejo = 'Toca mejorar: abre bien el pulgar hacia el costado a 90°.';
                    } else if (indice && medio) {
                        score = 52;
                        consejo = 'Toca mejorar: cierra el dedo medio, solo el índice va hacia arriba.';
                    } else {
                        score = 35;
                        consejo = 'Toca mejorar: forma una L con el índice arriba y pulgar abierto.';
                    }
                    break;

                case 'M':
                case 'N':
                case 'Ñ':
                    dedosEsperados = { pulgar: false, indice: false, medio: false, anular: false, menique: false };
                    if (!menique && totalExt <= 1) {
                        score = 90; esCorrecta = true;
                        consejo = OBJ === 'Ñ' ? '¡Muy bien! Mueve levemente la mano de lado a lado.' : '¡Bien! Dedos doblados hacia abajo sobre el pulgar.';
                    } else {
                        score = 50;
                        consejo = 'Toca mejorar: dobla los dedos hacia abajo apoyados sobre el pulgar.';
                    }
                    break;

                case 'O':
                    dedosEsperados = { pulgar: true, indice: false, medio: false, anular: false, menique: false };
                    if (dPulgarIndice < 0.95 && totalExt === 0) {
                        score = 95; esCorrecta = true;
                        consejo = '¡Excelente! Todas las yemas unidas en círculo formando la O.';
                    } else {
                        score = 58;
                        consejo = 'Toca mejorar: une todas las puntas de los dedos con el pulgar en óvalo.';
                    }
                    break;

                case 'P':
                case 'Q':
                    dedosEsperados = { pulgar: true, indice: true, medio: false, anular: false, menique: false };
                    if (indice && pulgar && !anular && !menique) {
                        score = 88; esCorrecta = true;
                        consejo = '¡Muy bien! Mano orientada hacia abajo en pinza.';
                    } else {
                        score = 50;
                        consejo = 'Toca mejorar: apunta el índice y pulgar hacia abajo.';
                    }
                    break;

                case 'R':
                    dedosEsperados = { pulgar: false, indice: true, medio: true, anular: false, menique: false };
                    if (indice && medio && !anular && !menique) {
                        score = 92; esCorrecta = true;
                        consejo = '¡Excelente! Dedos índice y medio cruzados hacia arriba.';
                    } else {
                        score = 50;
                        consejo = 'Toca mejorar: cruza el dedo medio por detrás del índice.';
                    }
                    break;

                case 'S':
                    dedosEsperados = { pulgar: false, indice: false, medio: false, anular: false, menique: false };
                    if (totalExt === 0 && !pulgar) {
                        score = 93; esCorrecta = true;
                        consejo = '¡Excelente! Puño cerrado con pulgar cruzado por delante.';
                    } else {
                        score = 52;
                        consejo = 'Toca mejorar: cierra el puño y abraza los dedos con el pulgar.';
                    }
                    break;

                case 'T':
                    dedosEsperados = { pulgar: true, indice: false, medio: false, anular: false, menique: false };
                    if (totalExt === 0 && pulgar) {
                        score = 90; esCorrecta = true;
                        consejo = '¡Bien! Pulgar asomado entre el índice y medio.';
                    } else {
                        score = 50;
                        consejo = 'Toca mejorar: asoma el pulgar entre el índice y medio doblados.';
                    }
                    break;

                case 'U':
                    // Índice y medio juntos hacia arriba
                    dedosEsperados = { pulgar: false, indice: true, medio: true, anular: false, menique: false };
                    if (indice && medio && !anular && !menique && dIndiceMedio < 0.75) {
                        score = 96; esCorrecta = true;
                        consejo = '¡Perfecto! Índice y medio extendidos y pegados juntos.';
                    } else if (indice && medio && dIndiceMedio >= 0.75) {
                        score = 70;
                        consejo = 'Toca mejorar: junta los dedos índice y medio (no los separes).';
                    } else {
                        score = 45;
                        consejo = 'Toca mejorar: levanta solo el índice y medio juntos hacia arriba.';
                    }
                    break;

                case 'V':
                case 'VERDE':
                    // Índice y medio separados en V
                    dedosEsperados = { pulgar: false, indice: true, medio: true, anular: false, menique: false };
                    if (indice && medio && !anular && !menique && dIndiceMedio > 0.65) {
                        score = 98; esCorrecta = true;
                        consejo = OBJ === 'VERDE' ? '¡Excelente seña de Verde con giro en V!' : '¡Perfecto! Forma de V con índice y medio bien separados.';
                    } else if (indice && medio && dIndiceMedio <= 0.65) {
                        score = 72;
                        consejo = 'Toca mejorar: separa más los dedos índice y medio en V.';
                    } else {
                        score = 42;
                        consejo = 'Toca mejorar: levanta y separa los dedos índice y medio.';
                    }
                    break;

                case 'W':
                    // Índice, medio y anular extendidos en W
                    dedosEsperados = { pulgar: false, indice: true, medio: true, anular: true, menique: false };
                    if (indice && medio && anular && !menique) {
                        score = 96; esCorrecta = true;
                        consejo = '¡Excelente! Tres dedos levantados y separados en forma de W.';
                    } else {
                        score = 48;
                        consejo = 'Toca mejorar: extiende tres dedos (índice, medio y anular) y baja el meñique.';
                    }
                    break;

                case 'Y':
                case 'AZUL':
                    // Pulgar y meñique extendidos (estilo shaka)
                    dedosEsperados = { pulgar: true, indice: false, medio: false, anular: false, menique: true };
                    if (pulgar && menique && !indice && !medio && !anular) {
                        score = 98; esCorrecta = true;
                        consejo = OBJ === 'AZUL' ? '¡Excelente seña de Azul oscilando el pulgar y meñique!' : '¡Excelente! Pulgar y meñique abiertos hacia afuera.';
                    } else {
                        score = 46;
                        consejo = 'Toca mejorar: extiende solo el pulgar y el meñique, dobla los 3 del centro.';
                    }
                    break;

                case 'AMARILLO':
                case 'TRANSPARENTE':
                    // Mano abierta con todos los dedos extendidos
                    dedosEsperados = { pulgar: true, indice: true, medio: true, anular: true, menique: true };
                    if (pulgar && indice && medio && anular && menique) {
                        score = 96; esCorrecta = true;
                        consejo = '¡Excelente! Palma completamente abierta con dedos vibrando.';
                    } else {
                        score = 55;
                        consejo = 'Toca mejorar: abre completamente la mano con todos los 5 dedos extendidos.';
                    }
                    break;

                default:
                    score = esCorrecta ? 90 : 60;
                    consejo = esCorrecta ? '¡Seña reconocida correctamente!' : 'Realiza la postura correspondiente a la seña ' + OBJ;
            }

            veredicto = esCorrecta ? 'ESTÁ BIEN' : 'ESTÁ MALA';

            return {
                estado: esCorrecta ? 'bien' : 'mala',
                esCorrecta,
                score,
                veredicto,
                consejo,
                dedosUsuario: { pulgar, indice, medio, anular, menique },
                dedosEsperados
            };
        }
    }

    // Dibuja el esqueleto luminoso de 21 puntos y el veredicto en canvas
    function dibujarEsqueletoMano(ctx, landmarks, width, height, estado = 'bien') {
        if (!landmarks || landmarks.length < 21) return;

        let colorLinea = '#28a745';
        let colorPunto = '#7CF29B';
        let colorAura  = 'rgba(40, 167, 69, 0.40)';

        if (estado === 'mala') {
            colorLinea = '#dc3545';
            colorPunto = '#ff6b6b';
            colorAura  = 'rgba(220, 53, 69, 0.40)';
        } else if (estado === 'sin_mano') {
            colorLinea = '#00d2ff';
            colorPunto = '#00ffff';
            colorAura  = 'rgba(0, 210, 255, 0.25)';
        }

        ctx.save();

        // 1. Dibujar conexiones de huesos
        ctx.lineWidth = 3.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = colorLinea;
        ctx.shadowColor = colorLinea;
        ctx.shadowBlur = 10;

        HAND_CONNECTIONS.forEach(([start, end]) => {
            const p1 = landmarks[start];
            const p2 = landmarks[end];
            ctx.beginPath();
            ctx.moveTo(p1.x * width, p1.y * height);
            ctx.lineTo(p2.x * width, p2.y * height);
            ctx.stroke();
        });

        // 2. Dibujar 21 articulaciones
        landmarks.forEach((p, idx) => {
            const x = p.x * width;
            const y = p.y * height;
            const esYema = [4, 8, 12, 16, 20].includes(idx);
            const esMuneca = idx === 0;

            if (esYema || esMuneca) {
                ctx.beginPath();
                ctx.arc(x, y, esMuneca ? 11 : 9, 0, Math.PI * 2);
                ctx.fillStyle = colorAura;
                ctx.fill();
            }

            ctx.beginPath();
            ctx.arc(x, y, esYema ? 6.5 : (esMuneca ? 6 : 4), 0, Math.PI * 2);
            ctx.fillStyle = esMuneca ? '#0056b3' : (esYema ? colorPunto : '#FFFFFF');
            ctx.strokeStyle = '#0B1B33';
            ctx.lineWidth = 1.5;
            ctx.fill();
            ctx.stroke();
        });

        ctx.restore();
    }

    // Controlador Principal de la Cámara SEVA con detección mejorada
    class SEVACamaraIA {
        constructor(config = {}) {
            this.video = config.video;
            this.canvas = config.canvas;
            this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
            this.onEvaluacion = config.onEvaluacion || null;
            this.onEstadoCamara = config.onEstadoCamara || null;

            this.stream = null;
            this.estaActiva = false;
            this.animId = null;
            this.audio = new AudioFeedback();
            this.clasificador = new SignClassifier();

            this.señaObjetivo = 'A';
            this.modo = 'nombre'; // 'nombre' | 'libre' | 'colores'
            this.nombreUsuario = 'SEVA';
            this.indiceLetraNombre = 0;
            this.tiempoAciertoMs = 0;
            this.tiempoRequeridoMs = 1200; // 1.2s sostenido para validar
            this.ultimoTimestamp = 0;

            this.mediapipeHands = null;
            this.landmarksActuales = null;
            this.landmarksSuavizados = null;
            this.manosActuales = [];      // todas las manos detectadas (hasta 2)
            this.manosSuavizadas = [];
            this.procesandoMediaPipe = false;
            this.posManoSuavizada = null;

            this.procCanvas = document.createElement('canvas');
            this.procCanvas.width = 160;
            this.procCanvas.height = 120;
            this.procCtx = this.procCanvas.getContext('2d', { willReadFrequently: true });
        }

        async iniciar(camaraId = null) {
            try {
                if (this.stream) this.detener();

                const constraints = {
                    video: {
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user',
                        deviceId: camaraId ? { exact: camaraId } : undefined
                    },
                    audio: false
                };

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('La cámara necesita HTTPS o localhost y un navegador compatible.');
                }
                this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                this.video.srcObject = this.stream;
                await this.video.play();

                this.estaActiva = true;
                if (this.onEstadoCamara) this.onEstadoCamara({ activa: true, error: null });

                this.iniciarMediaPipeSiExiste();
                if (!window.Hands || !this.mediapipeHands) {
                    if (this.onEstadoCamara) this.onEstadoCamara({ activa: true, error: 'El detector de manos no se pudo cargar. Comprueba tu conexión a internet y recarga la página.' });
                }

                this.ultimoTimestamp = performance.now();
                this.bucle();

                return true;
            } catch (err) {
                console.warn('Error al iniciar cámara:', err);
                this.estaActiva = false;
                if (this.onEstadoCamara) {
                    this.onEstadoCamara({
                        activa: false,
                        error: 'No se pudo acceder a la cámara. Revisa los permisos en tu navegador.'
                    });
                }
                return false;
            }
        }

        detener() {
            this.estaActiva = false;
            if (this.animId) {
                cancelAnimationFrame(this.animId);
                this.animId = null;
            }
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            if (this.video) this.video.srcObject = null;
            this.landmarksActuales = null;
            this.landmarksSuavizados = null;
            this.manosActuales = [];
            this.manosSuavizadas = [];
            this.procesandoMediaPipe = false;
            if (this.ctx && this.canvas) this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            if (this.onEstadoCamara) this.onEstadoCamara({ activa: false, error: null });
        }

        iniciarMediaPipeSiExiste() {
            if (window.Hands && !this.mediapipeHands) {
                try {
                    this.mediapipeHands = new window.Hands({
                        locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/hands/${file}`
                    });
                    this.mediapipeHands.setOptions({
                        maxNumHands: 2,               // lee las DOS manos a la vez
                        modelComplexity: 1,
                        minDetectionConfidence: 0.6,
                        minTrackingConfidence: 0.6
                    });
                    this.mediapipeHands.onResults((results) => {
                        const crudas = results.multiHandLandmarks || [];
                        const nuevas = [];
                        const usadas = new Set();
                        crudas.forEach((det) => {
                            // Empareja cada mano detectada con la del fotograma anterior (muñeca más cercana)
                            let idxMejor = -1, distMejor = 0.3;
                            this.manosSuavizadas.forEach((prev, i) => {
                                if (usadas.has(i)) return;
                                const d = Math.hypot(prev[0].x - det[0].x, prev[0].y - det[0].y);
                                if (d < distMejor) { distMejor = d; idxMejor = i; }
                            });
                            let suave;
                            if (idxMejor >= 0 && this.manosSuavizadas[idxMejor].length === det.length) {
                                usadas.add(idxMejor);
                                suave = this.manosSuavizadas[idxMejor];
                                // Suavizado temporal de los 21 puntos para reducir saltos entre fotogramas.
                                det.forEach((p, i) => {
                                    const q = suave[i];
                                    q.x += (p.x - q.x) * 0.58;
                                    q.y += (p.y - q.y) * 0.58;
                                    q.z += ((p.z || 0) - q.z) * 0.58;
                                });
                            } else {
                                suave = det.map(p => ({ x: p.x, y: p.y, z: p.z || 0 }));
                            }
                            nuevas.push(suave);
                        });
                        this.manosSuavizadas = nuevas;
                        this.manosActuales = nuevas;
                        // Compatibilidad: la primera mano sigue disponible como antes
                        this.landmarksSuavizados = nuevas[0] || null;
                        this.landmarksActuales = nuevas[0] || null;
                        this.procesandoMediaPipe = false;
                    });
                } catch (e) {
                    this.mediapipeHands = null;
                }
            }
        }

        estimarPuntosPorVisionLocal() {
            if (!this.procCtx || !this.video || this.video.readyState < 2) return null;

            const pw = this.procCanvas.width;
            const ph = this.procCanvas.height;
            this.procCtx.drawImage(this.video, 0, 0, pw, ph);

            const imgData = this.procCtx.getImageData(0, 0, pw, ph);
            const data = imgData.data;

            let sumX = 0, sumY = 0, count = 0;
            let minX = pw, maxX = 0, minY = ph, maxY = 0;

            for (let y = 0; y < ph; y += 2) {
                for (let x = 0; x < pw; x += 2) {
                    const idx = (y * pw + x) * 4;
                    const r = data[idx];
                    const g = data[idx + 1];
                    const b = data[idx + 2];

                    const esPiel = (r > 90 && g > 38 && b > 18 &&
                                    (r - g) > 12 && r > b &&
                                    Math.abs(r - g) > 12);

                    if (esPiel) {
                        sumX += x; sumY += y; count++;
                        if (x < minX) minX = x;
                        if (x > maxX) maxX = x;
                        if (y < minY) minY = y;
                        if (y > maxY) maxY = y;
                    }
                }
            }

            if (count > 100 && (maxX - minX) > 14 && (maxY - minY) > 18) {
                const cx = (sumX / count) / pw;
                const cy = (sumY / count) / ph;
                const ancho = (maxX - minX) / pw;
                const alto  = (maxY - minY) / ph;

                if (!this.posManoSuavizada) {
                    this.posManoSuavizada = { cx, cy, ancho, alto };
                } else {
                    this.posManoSuavizada.cx += (cx - this.posManoSuavizada.cx) * 0.40;
                    this.posManoSuavizada.cy += (cy - this.posManoSuavizada.cy) * 0.40;
                    this.posManoSuavizada.ancho += (ancho - this.posManoSuavizada.ancho) * 0.30;
                    this.posManoSuavizada.alto  += (alto - this.posManoSuavizada.alto) * 0.30;
                }

                return this.sintetizarLandmarks21(this.posManoSuavizada);
            }

            return null;
        }

        sintetizarLandmarks21(mano) {
            const { cx, cy, ancho, alto } = mano;
            const w = Math.max(0.14, ancho);
            const h = Math.max(0.18, alto);

            const wrist = { x: cx, y: Math.min(0.92, cy + h * 0.52), z: 0 };
            const mcpY = cy + h * 0.05;

            return [
                wrist,
                { x: cx - w * 0.26, y: cy + h * 0.30, z: -0.02 }, // thumb cmc
                { x: cx - w * 0.38, y: cy + h * 0.15, z: -0.04 }, // thumb mcp
                { x: cx - w * 0.44, y: cy - h * 0.02, z: -0.05 }, // thumb ip
                { x: cx - w * 0.48, y: cy - h * 0.16, z: -0.06 }, // thumb tip

                { x: cx - w * 0.22, y: mcpY, z: -0.01 },          // index mcp
                { x: cx - w * 0.22, y: mcpY - h * 0.18, z: -0.03 },
                { x: cx - w * 0.22, y: mcpY - h * 0.34, z: -0.04 },
                { x: cx - w * 0.22, y: mcpY - h * 0.48, z: -0.05 }, // index tip

                { x: cx - w * 0.04, y: mcpY - h * 0.02, z: -0.01 }, // middle mcp
                { x: cx - w * 0.04, y: mcpY - h * 0.22, z: -0.03 },
                { x: cx - w * 0.04, y: mcpY - h * 0.38, z: -0.04 },
                { x: cx - w * 0.04, y: mcpY - h * 0.52, z: -0.05 }, // middle tip

                { x: cx + w * 0.14, y: mcpY + h * 0.01, z: 0 },     // ring mcp
                { x: cx + w * 0.14, y: mcpY - h * 0.18, z: -0.02 },
                { x: cx + w * 0.14, y: mcpY - h * 0.32, z: -0.03 },
                { x: cx + w * 0.14, y: mcpY - h * 0.45, z: -0.04 }, // ring tip

                { x: cx + w * 0.30, y: mcpY + h * 0.06, z: 0.01 },  // pinky mcp
                { x: cx + w * 0.32, y: mcpY - h * 0.12, z: 0 },
                { x: cx + w * 0.32, y: mcpY - h * 0.24, z: -0.01 },
                { x: cx + w * 0.32, y: mcpY - h * 0.36, z: -0.02 }  // pinky tip
            ];
        }

        bucle() {
            if (!this.estaActiva) return;

            const ahora = performance.now();
            const delta = ahora - this.ultimoTimestamp;
            this.ultimoTimestamp = ahora;

            if (this.canvas && this.video.videoWidth > 0) {
                if (this.canvas.width !== this.video.videoWidth) {
                    this.canvas.width = this.video.videoWidth;
                    this.canvas.height = this.video.videoHeight;
                }
            }

            if (this.mediapipeHands && this.video.readyState >= 2 && !this.procesandoMediaPipe) {
                this.procesandoMediaPipe = true;
                this.mediapipeHands.send({ image: this.video }).catch(() => { this.procesandoMediaPipe = false; });
            }

            // Solo evaluamos landmarks reales del detector MediaPipe. No inventamos
            // puntos a partir del color de piel, porque eso daba falsos positivos.
            const manos = this.manosActuales || [];

            if (this.ctx && this.canvas) {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            }

            // Evaluar CADA mano contra la seña actual. Si cualquiera de las dos
            // la hace bien, se da por válida; si no, se muestra la de mejor puntaje.
            let evaluacion;
            if (manos.length === 0) {
                evaluacion = this.clasificador.evaluarSeña(null, this.señaObjetivo);
            } else {
                const evals = manos.map(m => this.clasificador.evaluarSeña(m, this.señaObjetivo));
                evaluacion = evals.find(e => e.esCorrecta) ||
                             evals.reduce((a, b) => (b.score > a.score ? b : a));
                // Dibujar los 21 puntos y el esqueleto de cada mano
                if (this.ctx && this.canvas) {
                    manos.forEach((m, i) => {
                        dibujarEsqueletoMano(this.ctx, m, this.canvas.width, this.canvas.height, evals[i].estado);
                    });
                }
            }
            evaluacion = { ...evaluacion, numManos: manos.length };

            let avanceCompletado = false;
            if (evaluacion.esCorrecta) {
                this.tiempoAciertoMs += delta;
                if (this.tiempoAciertoMs >= this.tiempoRequeridoMs) {
                    this.audio.playSuccess();
                    avanceCompletado = true;
                    this.tiempoAciertoMs = 0;
                    this.avanzarSiguientePaso();
                }
            } else {
                this.tiempoAciertoMs = Math.max(0, this.tiempoAciertoMs - delta * 1.5);
                if (evaluacion.estado === 'mala') {
                    // Feedback sutil para orientar al alumno
                    // this.audio.playWrong();
                }
            }

            const progresoSostenido = Math.min(1, this.tiempoAciertoMs / this.tiempoRequeridoMs);

            if (this.onEvaluacion) {
                this.onEvaluacion({
                    ...evaluacion,
                    señaObjetivo: this.señaObjetivo,
                    progresoSostenido,
                    tiempoAciertoMs: this.tiempoAciertoMs,
                    tiempoRequeridoMs: this.tiempoRequeridoMs,
                    avanceCompletado,
                    modo: this.modo,
                    indiceNombre: this.indiceLetraNombre,
                    totalLetrasNombre: this.nombreUsuario ? this.nombreUsuario.length : 0
                });
            }

            this.animId = requestAnimationFrame(() => this.bucle());
        }

        avanzarSiguientePaso() {
            if (this.modo === 'nombre') {
                const letras = this.nombreUsuario.toUpperCase().replace(/[^A-ZÑ]/g, '');
                if (this.indiceLetraNombre < letras.length - 1) {
                    this.indiceLetraNombre++;
                    this.señaObjetivo = letras[this.indiceLetraNombre];
                } else {
                    this.audio.playFanfare();
                    if (this.onCompletadoTodo) {
                        this.onCompletadoTodo({ tipo: 'nombre', nombre: this.nombreUsuario });
                    }
                }
            }
        }

        setSeñaObjetivo(seña) {
            this.señaObjetivo = (seña || 'A').toUpperCase();
            this.tiempoAciertoMs = 0;
        }

        get letraObjetivo() {
            return this.señaObjetivo;
        }
        set letraObjetivo(val) {
            this.setSeñaObjetivo(val);
        }

        setLetraObjetivo(letra) {
            this.setSeñaObjetivo(letra);
        }

        setModoNombre(nombre) {
            this.modo = 'nombre';
            const limpio = (nombre || 'SEVA').toUpperCase().replace(/[^A-ZÑ]/g, '') || 'SEVA';
            this.nombreUsuario = limpio;
            this.indiceLetraNombre = 0;
            this.señaObjetivo = limpio[0] || 'A';
            this.tiempoAciertoMs = 0;
        }

        setModoLibre() {
            this.modo = 'libre';
            this.tiempoAciertoMs = 0;
        }

        setModoColores() {
            this.modo = 'colores';
            this.señaObjetivo = 'AMARILLO';
            this.tiempoAciertoMs = 0;
        }
    }

    // Exportación global
    global.SEVACamaraIA = SEVACamaraIA;
    global.SignClassifier = SignClassifier;
    global.AlphabetSignClassifier = SignClassifier;
    global.dibujarEsqueletoMano = dibujarEsqueletoMano;

})(typeof window !== 'undefined' ? window : this);
