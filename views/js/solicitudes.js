document.addEventListener('DOMContentLoaded', () => {
    const contenedorAutocompletar = document.querySelector('.autocomplete-wrap');
    const formularioMulti = document.getElementById('form-solicitud-multi');
    const listaEscuelas = document.getElementById('schools-cards-list');
    const estadoVacio = document.getElementById('multi-school-empty');
    const badgeSchoolsCount = document.getElementById('schools-count');
    const summarySchoolsCount = document.getElementById('summary-schools-count');
    const summaryRegionalsCount = document.getElementById('summary-regionals-count');
    const summaryTotalUniforms = document.getElementById('summary-total-uniforms');
    const submitSchoolsLabel = document.getElementById('submit-schools-label');
    const btnSubmit = document.getElementById('btn-submit-solicitud');
    const avisoDisponibilidad = document.getElementById('request-availability');
    const mensajeDisponibilidad = document.getElementById('request-availability-message');

    // Catálogos desde los scripts embebidos
    const elemTallas = document.getElementById('catalog-tallas');
    const elemDisponibilidad = document.getElementById('catalog-disponibilidad');
    const elemInitialSchool = document.getElementById('initial-school');
    const elemExistingSchools = document.getElementById('existing-schools');

    const tallasCatalogo = elemTallas ? JSON.parse(elemTallas.textContent || '[]') : [];
    const disponibilidadCatalogo = elemDisponibilidad ? JSON.parse(elemDisponibilidad.textContent || '{}') : {};

    const escuelasMap = new Map();

    const formatearNumero = (num) => new Intl.NumberFormat('es-MX').format(num);

    const recalcularTotales = () => {
        let totalGeneral = 0;
        let totalFaltante = 0;
        let combinacionesFaltantes = 0;
        const totalPorTallaSexo = { NINA: {}, NINO: {} };
        const serviciosSet = new Set();

        tallasCatalogo.forEach((t) => {
            totalPorTallaSexo.NINA[t.id] = 0;
            totalPorTallaSexo.NINO[t.id] = 0;
        });

        // Calcular subtotales por escuela y acumular
        escuelasMap.forEach((datos, escuelaId) => {
            const card = document.getElementById(`school-card-${escuelaId}`);
            if (!card) return;

            let subtotalEscuela = 0;
            if (datos.servicio_regional) {
                serviciosSet.add(datos.servicio_regional);
            }

            const entradas = card.querySelectorAll('input[type="number"]');
            entradas.forEach((input) => {
                const valor = Math.max(0, parseInt(input.value, 10) || 0);
                const sexo = input.dataset.sexo;
                const tallaId = parseInt(input.dataset.tallaId, 10);
                subtotalEscuela += valor;
                if (totalPorTallaSexo[sexo] && totalPorTallaSexo[sexo][tallaId] !== undefined) {
                    totalPorTallaSexo[sexo][tallaId] += valor;
                }
            });

            totalGeneral += subtotalEscuela;
            const subtotalElem = card.querySelector('.school-subtotal-val');
            if (subtotalElem) {
                subtotalElem.textContent = `${formatearNumero(subtotalEscuela)} piezas`;
            }
        });

        // Validar existencias acumuladas contra el inventario
        tallasCatalogo.forEach((t) => {
            ['NINA', 'NINO'].forEach((sexo) => {
                const solicitado = totalPorTallaSexo[sexo][t.id] || 0;
                const disponible = (disponibilidadCatalogo[sexo] && disponibilidadCatalogo[sexo][t.id]) || 0;
                const faltante = Math.max(0, solicitado - disponible);
                if (faltante > 0) {
                    totalFaltante += faltante;
                    combinacionesFaltantes += 1;
                }
            });
        });

        // Actualizar advertencias individuales en inputs
        escuelasMap.forEach((_, escuelaId) => {
            const card = document.getElementById(`school-card-${escuelaId}`);
            if (!card) return;
            card.querySelectorAll('input[type="number"]').forEach((input) => {
                const sexo = input.dataset.sexo;
                const tallaId = parseInt(input.dataset.tallaId, 10);
                const solicitadoGlobal = totalPorTallaSexo[sexo][tallaId] || 0;
                const disponible = (disponibilidadCatalogo[sexo] && disponibilidadCatalogo[sexo][tallaId]) || 0;
                const hayFaltante = solicitadoGlobal > disponible;
                const label = input.closest('label');
                if (label) {
                    label.classList.toggle('quantity-shortage', hayFaltante);
                    const stockTag = label.querySelector('.quantity-stock');
                    if (stockTag) {
                        if (hayFaltante) {
                            stockTag.textContent = `Disp: ${formatearNumero(disponible)} · Faltan: ${formatearNumero(solicitadoGlobal - disponible)}`;
                        } else {
                            stockTag.textContent = `Disponibles: ${formatearNumero(disponible)}`;
                        }
                    }
                }
            });
        });

        // Actualizar contadores de cabecera y resumen
        const cantEscuelas = escuelasMap.size;
        if (badgeSchoolsCount) badgeSchoolsCount.textContent = formatearNumero(cantEscuelas);
        if (summarySchoolsCount) summarySchoolsCount.textContent = formatearNumero(cantEscuelas);
        if (summaryRegionalsCount) summaryRegionalsCount.textContent = formatearNumero(serviciosSet.size);
        if (summaryTotalUniforms) summaryTotalUniforms.textContent = formatearNumero(totalGeneral);
        if (submitSchoolsLabel) {
            submitSchoolsLabel.textContent = `${cantEscuelas} ${cantEscuelas === 1 ? 'escuela' : 'escuelas'}`;
        }

        if (estadoVacio) {
            estadoVacio.hidden = cantEscuelas > 0;
        }

        if (btnSubmit) {
            btnSubmit.disabled = cantEscuelas === 0 || totalGeneral === 0;
        }

        // Mensajes de disponibilidad
        if (avisoDisponibilidad && mensajeDisponibilidad) {
            avisoDisponibilidad.classList.toggle('availability-warning', totalFaltante > 0);
            avisoDisponibilidad.classList.toggle('availability-ready', totalGeneral > 0 && totalFaltante === 0);

            if (totalGeneral === 0) {
                mensajeDisponibilidad.textContent = 'Agrega escuelas y captura cantidades para verificar existencias globales.';
            } else if (totalFaltante > 0) {
                mensajeDisponibilidad.textContent = `Hay ${formatearNumero(totalFaltante)} uniformes faltantes en almacén para cubrir el total de las escuelas. La solicitud puede registrarse para formalizar la necesidad.`;
            } else {
                mensajeDisponibilidad.textContent = '✓ La solicitud consolidada tiene existencia general suficiente en los almacenes.';
            }
        }
    };

    const agregarEscuela = (escuela, cantidadesExistentes = null) => {
        const escuelaId = parseInt(escuela.id, 10);
        if (!escuelaId || escuelasMap.has(escuelaId)) {
            return;
        }

        escuelasMap.set(escuelaId, escuela);

        const card = document.createElement('article');
        card.className = 'school-request-card';
        card.id = `school-card-${escuelaId}`;

        // Construir cuadrícula de tallas
        let tallasHtml = '';
        tallasCatalogo.forEach((t) => {
            const cantNina = cantidadesExistentes && cantidadesExistentes.NINA ? (cantidadesExistentes.NINA[t.id] || 0) : 0;
            const cantNino = cantidadesExistentes && cantidadesExistentes.NINO ? (cantidadesExistentes.NINO[t.id] || 0) : 0;
            const dispNina = (disponibilidadCatalogo.NINA && disponibilidadCatalogo.NINA[t.id]) || 0;
            const dispNino = (disponibilidadCatalogo.NINO && disponibilidadCatalogo.NINO[t.id]) || 0;

            tallasHtml += `
                <div class="quantity-card">
                    <h4>Talla ${escuelaEscape(t.talla)}</h4>
                    <label class="girl-input">
                        ♀ Niña
                        <input type="number" name="cantidad[${escuelaId}][NINA][${t.id}]" value="${cantNina}" min="0" step="1" inputmode="numeric" data-talla-id="${t.id}" data-sexo="NINA">
                        <small class="quantity-stock">Disponibles: ${formatearNumero(dispNina)}</small>
                    </label>
                    <label class="boy-input">
                        ♂ Niño
                        <input type="number" name="cantidad[${escuelaId}][NINO][${t.id}]" value="${cantNino}" min="0" step="1" inputmode="numeric" data-talla-id="${t.id}" data-sexo="NINO">
                        <small class="quantity-stock">Disponibles: ${formatearNumero(dispNino)}</small>
                    </label>
                </div>
            `;
        });

        card.innerHTML = `
            <input type="hidden" name="escuelas[]" value="${escuelaId}">
            <div class="school-card-header">
                <div class="school-card-info">
                    <span class="selected-cct">${escuelaEscape(escuela.cct)}</span>
                    <h4>${escuelaEscape(escuela.nombre)}</h4>
                    <p>${escuelaEscape(escuela.nivel || '')} · ${escuelaEscape(escuela.municipio || '')} · ${escuelaEscape(escuela.localidad || '')}</p>
                </div>
                <div class="school-card-reg">
                    <span>Servicio Regional oficial</span>
                    <strong>${escuelaEscape(escuela.servicio_regional || 'Sin Servicio Regional')}</strong>
                    ${escuela.cct_servicio_regional ? `<small>CCT Ser. Reg: ${escuelaEscape(escuela.cct_servicio_regional)}</small>` : ''}
                </div>
                <div class="school-card-actions">
                    <span class="school-subtotal-val">0 piezas</span>
                    <button type="button" class="btn-remove-school" title="Quitar escuela de esta solicitud">✕ Quitar</button>
                </div>
            </div>
            <div class="quantity-grid">
                ${tallasHtml}
            </div>
        `;

        // Eventos
        card.querySelector('.btn-remove-school').addEventListener('click', () => {
            escuelasMap.delete(escuelaId);
            card.remove();
            recalcularTotales();
        });

        card.querySelectorAll('input[type="number"]').forEach((input) => {
            input.addEventListener('input', recalcularTotales);
        });

        listaEscuelas.append(card);
        recalcularTotales();
    };

    const escuelaEscape = (str) => {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    // Inicializar escuelas existentes si estamos en edición o precarga
    if (elemExistingSchools) {
        const existentes = JSON.parse(elemExistingSchools.textContent || '[]');
        existentes.forEach((item) => agregarEscuela(item, item.cantidades));
    } else if (elemInitialSchool) {
        const inicial = JSON.parse(elemInitialSchool.textContent || 'null');
        if (inicial && inicial.id) {
            agregarEscuela(inicial);
        }
    }

    // Buscador interactivo Autocomplete
    if (contenedorAutocompletar) {
        const campoBusqueda = contenedorAutocompletar.querySelector('input');
        const resultadosBusqueda = contenedorAutocompletar.querySelector('.autocomplete-results');
        const urlBusqueda = contenedorAutocompletar.dataset.busquedaUrl;
        let temporizadorBusqueda;
        let solicitudActual;

        const ocultarResultados = () => {
            resultadosBusqueda.hidden = true;
            resultadosBusqueda.replaceChildren();
        };

        const mostrarResultados = (escuelas) => {
            resultadosBusqueda.replaceChildren();
            if (!escuelas.length) {
                const vacio = document.createElement('div');
                vacio.className = 'autocomplete-empty';
                vacio.textContent = 'No se encontraron escuelas con esa búsqueda.';
                resultadosBusqueda.append(vacio);
            }

            escuelas.forEach((escuela) => {
                const yaAgregada = escuelasMap.has(escuela.id);
                const opcion = document.createElement('div');
                opcion.className = `autocomplete-option ${yaAgregada ? 'option-added' : ''}`;

                const principal = document.createElement('span');
                const cct = document.createElement('strong');
                const nombre = document.createElement('b');
                const ubicacion = document.createElement('small');

                cct.textContent = escuela.cct;
                nombre.textContent = escuela.nombre;
                ubicacion.textContent = `${escuela.nivel || ''} · ${escuela.municipio || ''} · ${escuela.localidad || ''}`;
                principal.append(cct, nombre, ubicacion);

                const regional = document.createElement('em');
                regional.textContent = escuela.servicio_regional ? `Región: ${escuela.servicio_regional}` : 'Sin Servicio Regional';

                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'button';

                if (!escuela.servicio_regional) {
                    boton.disabled = true;
                    boton.textContent = 'Sin relación';
                    boton.className = 'button secondary';
                } else if (yaAgregada) {
                    boton.disabled = true;
                    boton.textContent = '✓ Agregada';
                    boton.className = 'button secondary';
                } else {
                    boton.textContent = '+ Agregar';
                    boton.addEventListener('click', () => {
                        agregarEscuela(escuela);
                        campoBusqueda.value = '';
                        ocultarResultados();
                        campoBusqueda.focus();
                    });
                }

                opcion.append(principal, regional, boton);
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
                    if (!respuesta.ok) throw new Error('Error al consultar escuelas');
                    mostrarResultados(await respuesta.json());
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        ocultarResultados();
                    }
                }
            }, 200);
        });

        document.addEventListener('click', (evento) => {
            if (!contenedorAutocompletar.contains(evento.target)) {
                ocultarResultados();
            }
        });
    }

    // Inicializar cálculo inicial
    recalcularTotales();

    // Modal de entrega rápida
    const modalEntrega = document.getElementById('modal-entrega-rapida');
    if (modalEntrega) {
        const inputSolicitudId = document.getElementById('modal-solicitud-id');
        const labelFolio = document.getElementById('modal-folio');
        const labelTotal = document.getElementById('modal-total');
        const labelServicios = document.getElementById('modal-servicios');
        const btnCerrar = document.getElementById('btn-close-modal');
        const btnCancelar = document.getElementById('btn-cancel-modal');

        const containerPlan = document.getElementById('modal-plan-container');

        const cerrarModal = () => {
            modalEntrega.hidden = true;
        };

        if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
        if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);

        modalEntrega.addEventListener('click', (evento) => {
            if (evento.target === modalEntrega) {
                cerrarModal();
            }
        });

        document.querySelectorAll('.btn-direct-delivery').forEach((boton) => {
            boton.addEventListener('click', () => {
                const solId = boton.dataset.solicitudId;
                const folio = boton.dataset.folio;
                const total = boton.dataset.total;

                if (inputSolicitudId) inputSolicitudId.value = solId;
                if (labelFolio) labelFolio.textContent = folio;
                if (labelTotal) labelTotal.textContent = `${total} uniformes`;

                let plan = [];
                try {
                    plan = JSON.parse(boton.dataset.plan || '[]');
                } catch (e) {}

                if (containerPlan && plan.length > 0) {
                    containerPlan.replaceChildren();
                    plan.forEach((item) => {
                        const row = document.createElement('div');
                        row.className = 'modal-plan-item';
                        const escCount = item.escuelas ? item.escuelas.length : 1;
                        const escText = escCount === 1 ? '1 escuela' : `${escCount} escuelas`;

                        row.innerHTML = `
                            <div class="modal-plan-reg">
                                <strong>${escuelaEscape(item.servicio_nombre)}</strong>
                                <span>${escText} · ${formatearNumero(item.total_piezas)} uniformes</span>
                            </div>
                            <div class="modal-plan-alm">
                                <span>Almacén oficial correspondiente:</span>
                                <b>🏢 ${escuelaEscape(item.almacen_nombre)}</b>
                            </div>
                        `;
                        containerPlan.appendChild(row);
                    });
                }

                modalEntrega.hidden = false;
            });
        });
    }
});
