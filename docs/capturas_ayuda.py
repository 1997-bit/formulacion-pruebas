# Capturas de /ayuda: login con el seed, rectángulo rojo y WebP. Reglas en docs/ayuda.md
# Requisitos: pip install pillow playwright && playwright install chromium
# Uso: php -S 127.0.0.1:8000 -t public public/index.php, luego python docs/capturas_ayuda.py
import io
from pathlib import Path

from PIL import Image, ImageDraw
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8000"
SALIDA = Path(__file__).resolve().parent.parent / "public/assets/ayuda"
ESCALA = 2
ANCHO, ALTO = 600, 280
ROJO = "#DC2626"
MARGEN = 6

# nombre, ruta, enlaces a probar o None, selector del elemento a marcar
CAPTURAS = [
    ("selector-proyecto", "/dashboard", None, ".sidebar-marca"),
    ("evidencias", "/casos/listar?estado=0", "main a.codigo", "main fieldset:has(> legend:text-is('Evidencias'))"),
    ("registrar-incidente", "/casos/listar?estado=2", "main a.codigo", "main a[href^='/formularios/incidentes/registrar']"),
]


def capturar(pagina, nombre, ruta, enlace, selector):
    pagina.goto(BASE + ruta)
    # Primer enlace cuya página tiene el elemento
    if enlace:
        for href in [a.get_attribute("href") for a in pagina.locator(enlace).all()]:
            pagina.goto(BASE + href)
            if pagina.locator(selector).count():
                break
    elemento = pagina.locator(selector).first
    elemento.scroll_into_view_if_needed()
    # Sin hover ni foco: sin tooltip en la captura
    pagina.mouse.move(0, pagina.viewport_size["height"] - 1)
    pagina.evaluate("document.activeElement?.blur()")
    caja = elemento.bounding_box()
    vista = pagina.viewport_size
    # Recorte de ANCHO x ALTO con el elemento adentro. Elemento más ancho: recorte más ancho
    ancho = min(max(ANCHO, caja["width"] + 80), vista["width"])
    x = min(max(caja["x"] - 40, 0), vista["width"] - ancho)
    y = min(max(caja["y"] - 40, 0), vista["height"] - ALTO)
    imagen = Image.open(io.BytesIO(pagina.screenshot(clip={"x": x, "y": y, "width": ancho, "height": ALTO})))
    # Marco dentro de la imagen, también con el elemento en el borde
    borde = 3 * ESCALA
    marco = [max((caja["x"] - x - MARGEN) * ESCALA, borde / 2), max((caja["y"] - y - MARGEN) * ESCALA, borde / 2),
             min((caja["x"] - x + caja["width"] + MARGEN) * ESCALA, imagen.width - borde / 2),
             min((caja["y"] - y + caja["height"] + MARGEN) * ESCALA, imagen.height - borde / 2)]
    ImageDraw.Draw(imagen).rounded_rectangle(marco, radius=6 * ESCALA, outline=ROJO, width=borde)
    imagen.convert("RGB").save(SALIDA / f"{nombre}.webp", "WEBP", quality=80)
    print(nombre, imagen.width // ESCALA, imagen.height // ESCALA)


with sync_playwright() as p:
    navegador = p.chromium.launch(args=["--hide-scrollbars"])
    pagina = navegador.new_page(viewport={"width": 1280, "height": 800}, device_scale_factor=ESCALA, color_scheme="light")
    pagina.goto(BASE + "/")
    pagina.fill("input[name=usuario]", "gloria")
    pagina.fill("input[name=clave]", "gloria1234")
    with pagina.expect_navigation():
        pagina.keyboard.press("Enter")
    SALIDA.mkdir(parents=True, exist_ok=True)
    for captura in CAPTURAS:
        capturar(pagina, *captura)
    navegador.close()
