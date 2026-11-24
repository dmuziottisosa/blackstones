# La API de Instagram para mostrar los posts propios en un sitio

> **Status:** `layer-2-landscape` — documentación oficial de Meta, leída el 23 y 24 de noviembre de 2026.
> **Validez:** alta en lo que va entre comillas (es cita de la documentación). Es documentación, no algo visto andar: hasta que se cargue una llave de verdad, ningún punto de acá está comprobado contra Instagram.
> **Dónde se aplicó:** la sección Instagram de la landing. `04-operations/instagram-en-la-landing.md` y `01-strategy/decision-log/2026-11-23-instagram-en-la-landing.md`.

## Lo que dice Meta

**Qué API.** "Instagram API with Instagram Login", en `graph.instagram.com`. La anterior ("Basic Display API") dejó de funcionar el 4/12/2024.

**Qué cuenta.** Tiene que ser profesional (empresa o creador). No hace falta una página de Facebook: "This API setup does not require a Facebook Page to be linked to the Instagram professional account."

**Sin revisión de Meta.** "If your app only serves your Instagram professional account or an account you manage, Standard Access is all your app needs."

**Crear la app.** "Create App" → caso de uso "Other" → tipo "Business" ("Your app must be a business type app to be able to add the Instagram product") → nombre y mail → agregar el producto Instagram con "Set up". Queda "API setup with Instagram login" (en otra página lo llaman "API setup with Instagram business login").

**La llave sin programar un inicio de sesión.** Se agrega la cuenta de Instagram ("This account must be public") y se toca "Generate token" al lado. "Access tokens from the App Dashboard are long-lived and are valid for 60 days."

**Renovarla.** `GET https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=…`. La llave tiene que tener al menos 24 horas y no estar vencida. Devuelve `access_token`, `token_type` y `expires_in`: "Refreshed tokens are valid for 60 days from the date at which they are refreshed." No dice si la llave que devuelve es otra o la misma.

**De quién es la llave.** `GET /me?fields=user_id,username`.

**La lista de posts.** `GET /<IG_ID>/media`. `/me/media` no figura en ninguna página leída. Devuelve hasta los 10.000 más recientes; no trae historias.

**Los campos de un post.** `id`, `media_type` (`IMAGE`, `VIDEO` o `CAROUSEL_ALBUM`), `media_url`, `thumbnail_url` ("Only available on VIDEO media"), `permalink`, `timestamp`. El texto (`caption`) y `media_product_type` figuran como "Available for Instagram API with Facebook Login only".

**`media_url` puede faltar.** En un video con música con licencia, también de la biblioteca de Instagram: "Treat media_url as an optional field in your app and fall back to permalink or thumbnail_url when it is absent."

**Los álbumes.** Lo de adentro se pide con `/<id del post>/children`. La documentación no dice qué trae `media_url` en el álbum mismo.

**Los links de las fotos vencen.** "The CDN URL is privacy-aware and will not return the media when the content has been deleted or has expired." Por eso el sitio guarda su propia copia.

**Versión.** Los ejemplos usan `v27.0`. Sin versión en la dirección, vale la más nueva del día en que se creó la app.

**Límite de llamadas.** 4.800 por día por cada impresión que tuvo la cuenta en las últimas 24 horas. El sitio hace, como mucho, unas 30 por día.

**Errores.** Un objeto `error` con `message`, `type`, `code` y `error_subcode`. La página de códigos no lista el de llave vencida; el sitio trata como "llave rechazada" el 190, que es el de la API general de Meta.

## Fuentes

- `developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/` y `/get-started`
- `developers.facebook.com/docs/instagram-platform/create-an-instagram-app/`
- `developers.facebook.com/docs/instagram-platform/overview/`
- `developers.facebook.com/docs/instagram-platform/reference/refresh_access_token/`
- `developers.facebook.com/docs/instagram-platform/reference/instagram-media/`
- `developers.facebook.com/docs/instagram-platform/instagram-graph-api/reference/ig-user/media`
- `developers.facebook.com/docs/graph-api/overview/rate-limiting`

## Cuándo se revisa

Si el estado de la sección (`/ig-feed.php?estado=1`) empieza a dar un error que no es de la llave, lo primero es volver a leer estas páginas: Meta cambia esta API cada pocos meses.
