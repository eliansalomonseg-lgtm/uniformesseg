const contenedorAutocompletar = document.querySelector('.autocomplete-wrap');

if (contenedorAutocompletar) {
    const campoBusqueda = contenedorAutocompletar.querySelector('input');
    const resultadosBusqueda = contenedorAutocompletar.querySelector('.autocomplete-results');
    const urlBusqueda = contenedorAutocompletar.dataset.busquedaUrl;
    const urlSolicitud = contenedorAutocompletar.dataset.solicitudUrl;
    let temporizadorBusqueda;
    let solicitudActual;

    const ocultarResultados = () => {
        resultadosBusqueda.hidden = true;
        resultadosBusqueda.replaceChildren();
    };

    const seleccionarEscuela = (escuelaId) => {
        window.location.href = `${urlSolicitud}&escuela_id=${encodeURIComponent(escuelaId)}`;
    };

    const mostrarResultados = (escuelas) => {
        resultadosBusqueda.replaceChildren();
        if (!escuelas.length) {
            const vacio = document.createElement('div');
            vacio.className = 'autocomplete-empty';
            vacio.textContent = 'No se encontraron coincidencias.';
            resultadosBusqueda.append(vacio);
        }
        escuelas.forEach((escuela) => {
            const opcion = document.createElement('button');
            opcion.type = 'button';
            opcion.className = 'autocomplete-option';
            const principal = document.createElement('span');
            const cct = document.createElement('strong');
            const nombre = document.createElement('b');
            const ubicacion = document.createElement('small');
            cct.textContent = escuela.cct;
            nombre.textContent = escuela.nombre;
            ubicacion.textContent = `${escuela.nivel || ''} · ${escuela.municipio || ''} · ${escuela.localidad || ''}`;
            principal.append(cct, nombre, ubicacion);
            const regional = document.createElement('em');
            regional.textContent = escuela.servicio_regional ? `Servicio Regional: ${escuela.servicio_regional}` : 'Sin Servicio Regional oficial';
            if (!escuela.servicio_regional) {
                opcion.disabled = true;
            }
            opcion.append(principal, regional);
            opcion.addEventListener('click', () => seleccionarEscuela(escuela.id));
            resultadosBusqueda.append(opcion);
        });
        resultadosBusqueda.hidden = false;
    };

    campoBusqueda.addEventListener('input', () => {
        const termino = campoBusqueda.value.trim();
        clearTimeout(temporizadorBusqueda);
        if (solicitudActual) {
            solicitudActual.abort();
        }
        if (termino.length < 2) {
            ocultarResultados();
            return;
        }
        temporizadorBusqueda = window.setTimeout(async () => {
            solicitudActual = new AbortController();
            try {
                const respuesta = await fetch(`${urlBusqueda}&q=${encodeURIComponent(termino)}`, { signal: solicitudActual.signal });
                if (!respuesta.ok) {
                    throw new Error('No fue posible consultar las escuelas.');
                }
                mostrarResultados(await respuesta.json());
            } catch (error) {
                if (error.name !== 'AbortError') {
                    ocultarResultados();
                }
            }
        }, 220);
    });

    document.addEventListener('click', (evento) => {
        if (!contenedorAutocompletar.contains(evento.target)) {
            ocultarResultados();
        }
    });
}

const formularioSolicitud = document.querySelector('.request-form');

if (formularioSolicitud) {
    const entradasCantidad = formularioSolicitud.querySelectorAll('input[data-disponible]');
    const avisoDisponibilidad = formularioSolicitud.querySelector('#request-availability');
    const mensajeDisponibilidad = formularioSolicitud.querySelector('#request-availability-message');

    const revisarDisponibilidad = () => {
        let totalSolicitado = 0;
        let totalFaltante = 0;
        let combinacionesConFaltante = 0;
        entradasCantidad.forEach((entrada) => {
            const solicitado = Math.max(0, Number.parseInt(entrada.value, 10) || 0);
            const disponible = Number.parseInt(entrada.dataset.disponible, 10) || 0;
            const faltante = Math.max(0, solicitado - disponible);
            const etiqueta = entrada.closest('label');
            totalSolicitado += solicitado;
            totalFaltante += faltante;
            etiqueta.classList.toggle('quantity-shortage', faltante > 0);
            if (faltante > 0) {
                combinacionesConFaltante += 1;
                etiqueta.querySelector('.quantity-stock').textContent = `Disponibles: ${disponible} · Faltan: ${faltante}`;
            } else {
                etiqueta.querySelector('.quantity-stock').textContent = `Disponibles: ${disponible}`;
            }
        });
        avisoDisponibilidad.classList.toggle('availability-warning', totalFaltante > 0);
        avisoDisponibilidad.classList.toggle('availability-ready', totalSolicitado > 0 && totalFaltante === 0);
        if (totalSolicitado === 0) {
            mensajeDisponibilidad.textContent = 'Capture cantidades para revisar si existen uniformes disponibles.';
        } else if (totalFaltante > 0) {
            mensajeDisponibilidad.textContent = `Hay ${totalFaltante} uniformes faltantes en ${combinacionesConFaltante} talla(s) o género(s). La solicitud se puede guardar para registrar la necesidad real.`;
        } else {
            mensajeDisponibilidad.textContent = 'La solicitud tiene existencia general suficiente en los almacenes.';
        }
    };

    entradasCantidad.forEach((entrada) => entrada.addEventListener('input', revisarDisponibilidad));
    revisarDisponibilidad();
}
