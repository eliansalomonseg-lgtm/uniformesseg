# Sistema de Gestión y Control de Uniformes Escolares (SEG)

Sistema web para la administración, control de inventario, seguimiento de almacenes y gestión de solicitudes y entregas de uniformes escolares para la Secretaría de Educación Guerrero (SEG).

## 🚀 Características y Módulos

- **Panel de Control (Dashboard):** Métricas consolidadas de stock total, solicitudes pendientes, entregas en proceso y almacenes activos.
- **Gestión de Almacenes:** Control de existencias por almacén, tallas, género (niño/niña) y seguimiento de stock disponible.
- **Servicios Regionales:** Catálogo y vinculación con los centros de servicios regionales educativos.
- **Padrón de Escuelas:** Búsqueda y gestión de centros de trabajo educativos (CCT), nivel escolar y municipios.
- **Solicitudes de Uniformes:** Registro, actualización, desglose por tallas/sexo y control del flujo de estados (Pendiente, En Revisión, Asignada, Atendida, etc.).
- **Entregas y Distribución:** Registro de órdenes de entrega hacia servicios regionales y vinculación de solicitudes.
- **Movimientos de Almacén:** Auditoría y trazabilidad de entradas, salidas y transferencias de uniformes.

---

## 🛠️ Tecnologías Utilizadas

- **Lenguaje:** PHP 8.x (Programación orientada a objetos / Patrón MVC nativo)
- **Base de Datos:** MySQL / MariaDB (PDO con transacciones e integridad referencial)
- **Servidor Web:** Apache (XAMPP / WampServer / LAMP)
- **Frontend:** HTML5, CSS3 moderno, JavaScript vanilla y Bootstrap / Tailwind (según vistas)

---

## 📁 Estructura del Proyecto

```text
uniformesseg/
├── config/             # Configuración de base de datos y entorno
│   └── database.php
├── controllers/        # Controladores del patrón MVC
├── database/           # Scripts SQL y migraciones de la base de datos
├── models/             # Modelos y lógica de acceso a datos (PDO)
├── services/           # Servicios auxiliares (Conexión PDO, utilidades)
├── views/              # Vistas organizadas por módulos y fragmentos reutilizables
│   ├── almacenes/
│   ├── dashboard/
│   ├── entregas/
│   ├── escuelas/
│   ├── fragments/      # header, sidebar, footer
│   ├── movimientos/
│   ├── servicios_regionales/
│   └── solicitudes/
├── .gitignore          # Reglas de exclusión para Git
├── index.php           # Enrutador principal del sistema
└── README.md           # Documentación del proyecto
```

---

## ⚙️ Requisitos e Instalación

### 1. Requisitos Previos
- [XAMPP](https://www.apachefriends.org/) (con PHP 8.1+ y MySQL / MariaDB).
- Git instalado en el equipo.

### 2. Configuración en Servidor Local
1. Clona o ubica este repositorio dentro de la carpeta `htdocs`:
   ```bash
   c:\xampp\htdocs\uniformesseg
   ```

2. Inicia los servicios de **Apache** y **MySQL** desde el Panel de Control de XAMPP.

### 3. Configuración de Base de Datos
1. Accede a **phpMyAdmin** (`http://localhost/phpmyadmin`) o a la consola de MySQL.
2. Crea una base de datos llamada `uniformes_seg`:
   ```sql
   CREATE DATABASE uniformes_seg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Ejecuta los scripts SQL en el siguiente orden dentro de la carpeta `database/`:
   - `001_solicitudes_uniformes.sql`
   - `002_carga_inicial_almacenes.sql`
   - `003_campos_oficializacion_escuelas.sql`

4. Si tus credenciales de MySQL son distintas a las predeterminadas (`root` sin contraseña en `127.0.0.1`), ajusta el archivo [`config/database.php`](config/database.php) o define las variables de entorno:
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`

### 4. Acceso al Sistema
Abre tu navegador y entra a:
```
http://localhost/uniformesseg/
```
