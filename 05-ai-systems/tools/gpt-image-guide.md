# GPT Image — guía de prompts (síntesis de la documentación oficial)

> **Status:** `layer-2` aplicado a herramienta. Síntesis de la guía oficial de OpenAI, leída el 2026-11-15.
> **Fuentes:** [Image prompting (docs oficiales)](https://developers.openai.com/api/docs/guides/image-prompting) · [Cookbook: image prompting](https://developers.openai.com/cookbook/examples/multimodal/image-gen-models-prompting-guide) · [GPT Image 2 en Higgsfield](https://higgsfield.ai/gpt-2)
> **Alcance:** la guía oficial es una sola para toda la familia (GPT-Image-2, 2.5 y 2.6). No hay una página aparte "solo para GPT Image 2".
> **Brújula, no mapa:** los modelos cambian cada pocos meses. Releer la fuente antes de una tanda grande.

---

## Lo que dice la guía oficial

### 1. Cómo armar el prompt
- **Primero el resultado:** qué es la pieza y para qué se usa (foto de producto, aviso, diagrama), con su composición, proporción y dónde va cada cosa.
- **Pedidos complejos, por secciones con rótulo:** escena, sujeto, detalles y restricciones.
- **El formato es libre** (párrafo, lista, tipo JSON): se elige el que sea más fácil de leer y de corregir. No hay sintaxis mágica.
- **Describir lo que se ve:** materiales, luz, colores, textura, encuadre. Si se quiere una foto real, pedir "fotorrealista" de forma explícita.
- **Las palabras de clima solas no alcanzan:** decir escala, atmósfera y color.

### 2. Texto dentro de la imagen
- El texto que tiene que aparecer va **entre comillas**, con su **posición y tipografía**.
- Palabras raras o nombres de marca: **deletrearlos** si salen mal.
- Pedir **"ningún otro texto"** y después revisar ortografía y legibilidad.
- Texto chico, mucha información o varias tipografías: probar calidad **media o alta**.
- Se puede pedir que un texto aparezca **"exactamente una vez"**.

### 3. Imágenes de referencia
- **Numerar cada imagen y decir su papel:** sujeto, estilo, ropa o fondo.
- **Para estilo:** asignarle a la referencia su paleta, textura o medio, y describir aparte el sujeto nuevo.
- **Decir cómo se combinan:** qué elemento va adónde.
- **Para una serie:** reutilizar la imagen generada y **repetir las restricciones** en cada prompt. "Mismo estilo que antes" ayuda, pero si el resultado se desvía hay que volver a escribir lo crítico.

### 4. Ediciones
- Separar lo que cambia de lo que se conserva: "cambiá solo X" y listar lo que queda igual.
- Excluir lo que no se quiere: texto, logos, marcas de agua.
- Iterar de a un cambio: la salida anterior entra como referencia, se pide una sola cosa y se repite lo que hay que conservar.
- Si algo tiene que quedar idéntico al píxel, se compone a mano sobre el original: el prompt no lo garantiza.

### 5. Tamaños y calidad (API)
- GPT-Image-2: calidad baja, media, alta o automática. Tamaños de ejemplo: 1024x1536 (vertical) y 1536x1024 (apaisado).
- No conviene asumir que más calidad siempre da mejor resultado: se compara.

### 6. Revisión antes de dar por buena una imagen
- ¿Cada palabra se lee y coincide con lo pedido?
- ¿Se conservaron formas, productos y rótulos?
- ¿La edición cambió solo lo pedido?

---

## Lo que NO está confirmado (no asumir)

- **Cuántas referencias acepta Higgsfield y qué proporciones ofrece.** La página oficial de Higgsfield solo dice que subir una referencia es opcional y sirve para guiar el aspecto. Guías de terceros hablan de "hasta 16", sin fuente oficial. Verificar en la pantalla al generar.
- **"Más de 95% de precisión en texto"** es una afirmación de marketing de Higgsfield, no un dato de OpenAI. Revisar siempre cada palabra.

## Cómo lo usamos en BlackStones

1. Bloque **ESTILO** fijo, repetido entero en cada prompt de una serie (paleta, tipografías, reglas de `00-brand/visual-identity/visual-system.md`).
2. Texto siempre entre comillas, con posición, y "ningún otro texto".
3. La primera placa aprobada se adjunta como **Imagen 1 · referencia de estilo** en todas las demás.
4. Las fotos reales de piedra entran como **sujeto** con la orden "no rediseñar la piedra".
5. El logo no se le pide al modelo: se agrega después con el archivo real (regla del sistema visual).
