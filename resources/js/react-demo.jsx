import { createRoot } from 'react-dom/client';
import { useState } from 'react';
import './react-demo.css';

function ReactDemo() {
    const [pendientes, setPendientes] = useState(0);

    return (
        <main className="react-demo-page">
            <section className="react-demo-card">
                <p className="react-demo-eyebrow">Ejemplo académico</p>
                <h1>Demostración React - Valle de San Remo</h1>
                <p className="react-demo-label">Paquetes pendientes</p>
                <output className="react-demo-counter" aria-live="polite">{pendientes}</output>
                <div className="react-demo-actions">
                    <button type="button" onClick={() => setPendientes((actual) => actual + 1)}>
                        Agregar paquete
                    </button>
                    <button type="button" className="react-demo-reset" onClick={() => setPendientes(0)}>
                        Reiniciar
                    </button>
                </div>
            </section>
        </main>
    );
}

const contenedor = document.getElementById('react-demo');

if (contenedor) {
    createRoot(contenedor).render(<ReactDemo />);
}
