# Ayuda

Artículos en `/ayuda`. La lista va en `config/ayuda.php` y el texto en `views/ayuda/articulos/<slug>.php`. `pantallas` en `config/ayuda.php` une cada ruta con su artículo para el enlace Ayuda del menú: abre el artículo de la pantalla actual.

## Reglas

- Español STE. Una idea por frase. Máximo 20 palabras por frase.
- Trato de usted, como la app: "Haga clic".
- Un artículo trata una tarea o un tema.
- Tarea: el título es un verbo y la tarea. Lleva 1 frase de propósito y luego pasos numerados, máximo 7. Cada paso tiene una sola acción.
- Referencia: solo una tabla, sin prosa.
- Concepto: máximo 3 frases y 1 ejemplo.
- Botón o campo en `<strong>`, escrito igual que en la pantalla.
- Sin introducciones ni cierre.
- Captura solo si el elemento cuesta encontrarlo. El paso escrito debe estar completo sin la imagen.
- Captura en WebP con un rectángulo rojo. El `alt` empieza con "Captura de". Sin GIF.
- Diagrama solo para flujos de estado. Va en SVG inline con `currentColor`, así funciona en modo oscuro.
- Si la pantalla cambia, el artículo y la captura cambian en el mismo PR.

## Capturas

`docs/capturas_ayuda.py` entra con `gloria` del seed y genera `public/assets/ayuda/*.webp`. Una captura nueva necesita una línea en `CAPTURAS`.

```sh
pip install pillow playwright && playwright install chromium
php -S 127.0.0.1:8000 -t public public/index.php
python docs/capturas_ayuda.py
```
