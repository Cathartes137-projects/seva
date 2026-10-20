ez<<<<# Documento Técnico de Migración - Proyecto SEVA (Sistema Educativo de Lengua de Señas)

## 1. Objetivos del Proyecto

### 1.1 Propósito General
El proyecto **SEVA** (**Sistema Educativo de Lengua de Señas**) es una plataforma web educativa diseñada para facilitar el aprendizaje interactivo, continuo y accesible de la Lengua de Señas. Su propósito fundamental es eliminar las barreras de comunicación que enfrentan las personas con discapacidad auditiva (sordera total o hipoacusia en distintos grados), promoviendo la equidad digital y la inclusión social y académica.

### 1.2 Principios Pedagógicos y Marcos Inclusivos
* **Diseño Universal para el Aprendizaje (DUA)**: Estructuración del contenido en múltiples formatos visuales (videos en lengua de señas, imágenes dactilológicas y texto descriptivo) para adaptarse a diversos estilos de aprendizaje.
* **Sistema Hipermedia Adaptativo (SHA)**: Personalización del recorrido educativo mediante la evaluación continua y el seguimiento de progreso individual.
* **Repetición Espaciada**: Retención a largo plazo mediante un algoritmo de repaso programado basado en la frecuencia de uso de cada término o seña.

### 1.3 Perfiles de Usuario Objetivo
* **Estudiantes con Discapacidad Auditiva**: Usuarios con sordera o hipoacusia que requieren formación técnica o aprendizaje de LSE de forma autónoma mediante estímulos visuales y táctiles.
* **Estudiantes Oyentes, Familiares y Comunidad**: Personas sin discapacidad auditiva interesadas en aprender lengua de señas para comunicarse de manera fluida e inclusiva.
* **Docentes y Educadores**: Profesionales en educación que crean o monitorean actividades, evaluando el desempeño de los estudiantes.
* **Administrativos / Soporte**: Personal encargado de la gestión general del sistema y actualización de contenidos.

---

## 2. Arquitectura y Stack Tecnológico

### 2.1 Componentes del Stack
* **Servidor Web**: Apache 2.4+ / Nginx con soporte para reescritura de URLs y cabeceras de seguridad.
* **Backend**: **PHP 8.2** utilizando la extensión **PDO** (PHP Data Objects) y **MySQLi** configurados con la codificación  para asegurar soporte completo de caracteres e inmunidad ante inyecciones SQL mediante consultas preparadas.
* **Base de Datos**: **MariaDB / MySQL** con el esquema relacional .
* **Frontend**: HTML5 limpio y semántico, CSS3 estructurado mediante variables CSS para la fácil personalización de temas (Design Tokens), e interacciones livianas en JavaScript Vanilla.

---

## 3. Flujos de Datos y Modelo Relacional de la Base de Datos

### 3.1 Flujos de Datos Principales

#### Flujo A: Registro de Usuario ()
1. El usuario envía sus datos mediante método POST: , ,  y .
2. El servidor valida la presencia de campos obligatorios, el formato del correo mediante , una longitud mínima de contraseña (>= 8 caracteres) y la coincidencia entre contraseñas.
3. Se verifica en la base de datos si el correo electrónico ya existe en la tabla .
4. Si no existe, la contraseña se hashea utilizando .
5. Se inserta el registro en la tabla  y se retorna una confirmación de éxito.

#### Flujo B: Inicio de Sesión y Control de Acceso ()
1. El usuario ingresa sus credenciales ( y ).
2. Se ejecuta una consulta preparada sobre la tabla : .
3. Se verifica la contraseña almacenada con .
4. Si es válida, se regenera el ID de sesión con  para prevenir la fijación de sesión y se inicializan las variables de sesión (, , ). Redirección automática a .

#### Flujo C: Dashboard y Cálculo de Progreso ()
1. Se valida la existencia de  en la sesión. Si no existe, redirige a .
2. Se consulta la lista completa de lecciones en  ordenada por su ID o campo .
3. Se calcula el total de lecciones existentes () y el número de lecciones marcadas como completadas por el usuario actual en la tabla  ().
4. Se calcula dinámicamente el porcentaje de progreso: .
5. Se extraen los  de las lecciones completadas para mostrar la insignia visual "Completada" en la interfaz.

### 3.2 Esquema Relacional de la Base de Datos ()

El esquema actualizado consta de 5 tablas relacionales administradas con el motor **InnoDB** y codificación :

1. **usuarios**:  (INT, PK, AUTO_INCREMENT),  (VARCHAR 100),  (VARCHAR 150, UNIQUE),  (VARCHAR 255),  (TIMESTAMP).
2. **contenidos_educativos**: uid=1000(sandbox) gid=1000(sandbox) groups=1000(sandbox) (INT, PK, AUTO_INCREMENT),  (VARCHAR 150),  (TEXT),  (VARCHAR 255),  (VARCHAR 255),  (ENUM: basico, intermedio, avanzado),  (INT),  (TIMESTAMP).
3. **progreso_usuario**: uid=1000(sandbox) gid=1000(sandbox) groups=1000(sandbox) (INT, PK, AUTO_INCREMENT),  (INT, FK -> usuarios.id_usuario),  (INT, FK -> contenidos_educativos.id),  (TINYINT 1),  (TIMESTAMP). UNIQUE KEY en (, ). Restricciones: ON DELETE CASCADE ON UPDATE CASCADE.
4. **palabras_repasadas**:  (INT, PK, AUTO_INCREMENT),  (INT, FK -> usuarios.id_usuario),  (VARCHAR 100),  (INT),  (DATE). Restricciones: ON DELETE CASCADE ON UPDATE CASCADE.
5. **cursos**:  (INT, PK, AUTO_INCREMENT),  (VARCHAR 100),  (VARCHAR 50),  (VARCHAR 50).

---

## 4. Decisiones Técnicas y de Diseño UX/UI / Accesibilidad

### 4.1 Principios de UX/UI y Leyes Cognitivas
El diseño de interfaces en SEVA se inspira en el patrón **Lingvano + M-Pesa** (lecciones breves basadas en video + navegación lineal con mínima carga de texto):

1. **Ley de Fitts**: Los elementos interactivos (botones , , accesos de formulario) poseen dimensiones táctiles mínimas de **44×44 px**, facilitando la interacción en dispositivos móviles.
2. **Ley de Miller**: La información se fragmenta en trozos manejables (*chunks*) estructurados en niveles (Básico, Intermedio, Avanzado).
3. **Ley de Hick**: Simplificación de opciones disponibles por pantalla para evitar el agobio cognitivo.
4. **Ley de Tesler (Conservación de la Complejidad)**: Preservación de campos indispensables en formularios de registro y login, reduciendo pasos innecesarios.
5. **Efecto Zeigarnik**: La presencia de la barra de progreso visual y el cálculo dinámico del porcentaje en el Dashboard motiva al estudiante a culminar las lecciones pendientes.
6. **Ley de Pregnanz y Ley de Proximidad**: Estructuración mediante rejillas adaptativas (), tarjetas delimitadas () y separación clara por contraste y color.
7. **Ley de Jakob**: Adopción de flujos estandarizados de autenticación y navegación web reconocibles inmediatamente por el usuario.

### 4.2 Accesibilidad W3C y Pautas WCAG 2 / WAI-ARIA
* **Navegación por Teclado**: Implementación obligatoria de la pseudoclase  con un contorno prominente (3px sólido) para asegurar la visibilidad del foco.
* **Soporte para Movimiento Reducido**: Incorporación de la regla  para desactivar animaciones SVG (como la seña interactiva de saludo) en usuarios sensibles.
* **Contenedores Multimedia Adaptados**: Clase  con relación de aspecto 16:9, subtítulos visibles () y etiquetas de contexto (), alineados con los estándares de sincronización WebVTT.
* **Feedback Multimodal**: Uso combinado de iconos en vectores, contrastes accesibles y mensajes de texto directo para confirmar acciones del usuario.

---

## 5. Lista de Pendientes de Desarrollo y Auditoría de Seguridad

### 5.1 Mejoras de Interfaz y UX
- [ ] Implementar componentes **Skeletons** para indicar la carga diferida de lecciones y videos.
- [ ] Completar la estandarización de **Design Tokens** en CSS para soportar temas dinámicos (Modo Claro / Modo Oscuro).
- [ ] Refinar los menús de navegación complejos aplicando estrictamente la **Ley de Hick**.
- [ ] Realizar auditorías periódicas de accesibilidad con herramientas automatizadas (Lighthouse, WAVE) y pruebas manuales de navegación por teclado conforme a WCAG 2.1 Nivel AA.

### 5.2 Seguridad y Endurecimiento (Hardening)
- [ ] **Forzar HTTPS**: Configurar redirecciones automáticas HTTP a HTTPS y activar HSTS (*HTTP Strict Transport Security*).
- [ ] **Re-hashing de Contraseñas**: Implementar actualización automática de hashes heredados durante el inicio de sesión.
- [ ] **Cabeceras HTTP de Seguridad**: Agregar  (CSP), ,  y .
- [ ] **Rate Limiting y Prevención de Fuerza Bruta**: Limitar los intentos fallidos de inicio de sesión por IP y usuario.
- [ ] **Protección contra Bots**: Integrar CAPTCHA invisible o campos Honeypot en  y .
- [ ] **Validación y Sanitización de Entradas**: Aplicar filtrado estricto en todas las variables recibidas mediante GET y POST.
- [ ] **Gestión de Secretos**: Asegurar que ningún secreto, contraseña de BD o clave API sea enviado a repositorios Git ().
- [ ] **Seguridad de Datos**: Implementar cifrado de datos sensibles en reposo y mecanismos de RLS (*Row Level Security*) o comprobación estricta de autorización por rol.

---

## 6. Guía de Ejecución y Despliegue

### 6.1 Prerrequisitos del Servidor
* **Servidor Web**: Apache 2.4 o Nginx.
* **Lenguaje de Programación**: PHP 8.2 o superior con extensiones habilitadas: , , , .
* **Base de Datos**: MariaDB 10.4+ o MySQL 8.0+.

### 6.2 Pasos de Instalación Local (Entorno de Desarrollo)
1. **Clonar el repositorio**: Configurar el directorio del proyecto.
2. **Configurar la Base de Datos**: Crear la base de datos  en MariaDB/MySQL e importar el script SQL actualizado.
3. **Configurar la Conexión en PHP ()**: Usar PDO con manejo de excepciones y .
4. **Estructura de Carpetas Multimedia**: Asegurar la presencia de  y .
5. **Iniciar el Servidor de Pruebas**: Ejecutar  e ingresar en el navegador.
