# Cart to Budget (`carttobudget`)

Módulo para PrestaShop 1.7, 8 y 9 que combina dos cosas:

1. **Carritos guardados para clientes**: el cliente guarda su carrito y lo recupera más tarde desde *Mi cuenta*. La función se puede activar o desactivar **cliente a cliente**.
2. **Presupuestos desde el back office**: la tienda crea un carrito/presupuesto con los productos que quiera, lo comparte con un **enlace privado con token** y el destinatario lo ve completo en la web (líneas, importes, unidades, subtotales y desglose de IVA), lo descarga en **PDF** con el logo de la tienda (varias plantillas) y lo añade a su carrito con un clic.

## Instalación

1. Copia esta carpeta en `modules/carttobudget/` (o comprime la carpeta `carttobudget/` en un zip y súbela desde *Módulos > Gestor de módulos > Subir un módulo*).
2. Instala **Cart to Budget**.
3. Configura el módulo desde el botón *Configurar*.

La instalación crea tres tablas (`carttobudget_cart`, `carttobudget_cart_line`, `carttobudget_customer`), dos pestañas en el menú **Clientes** y registra los hooks necesarios.

### Migración desde el módulo anterior de carritos guardados

Si existen las tablas `saved_carts` y `saved_cart_rule` del módulo anterior, la página de configuración muestra el botón **Importar carritos**. Copia los carritos (con sus líneas, convirtiendo los precios guardados con IVA a precio sin IVA + tipo) sin tocar las tablas antiguas. Después puedes desinstalar el módulo antiguo.

## Funcionamiento

### Clientes

- En la página del carrito aparece el bloque **Guardar carrito**, solo para clientes con la función activa. Usa el hook estándar `displayShoppingCart` (temas Classic y Hummingbird) y también `displayShoppingCartAfterSummary` para temas personalizados que lo tengan. Si el tema llama a los dos, el bloque se muestra una sola vez.
- En *Mi cuenta > Carritos guardados y presupuestos* el cliente ve sus carritos y los presupuestos que la tienda le haya asignado, con acciones para restaurar, eliminar (solo los suyos), copiar el enlace para compartir, ver el presupuesto y descargar el PDF.
- Los carritos guardados por clientes caducan pasados *N* días (configurable, 0 = nunca) y hay un máximo por cliente (configurable, 0 = sin límite). Los presupuestos de back office no caducan solos: tienen una fecha de validez informativa.

### Activar / desactivar por cliente

- **Configuración > Activado por defecto para todos los clientes**: valor general.
- **Clientes > Acceso a carritos guardados**: listado de clientes con interruptor individual, acciones en lote (activar / desactivar / volver al valor de la tienda) y filtro.
- En la **ficha del cliente** (hook `displayAdminCustomers`) hay un panel con el estado, el botón para cambiarlo, sus carritos y un acceso directo para crearle un presupuesto.

Un cliente con la función desactivada no puede guardar carritos, pero sigue viendo los presupuestos que la tienda le asigne.

### Presupuestos (Clientes > Carritos y presupuestos)

- **Nuevo presupuesto**: nombre, cliente (opcional, buscador), datos del destinatario (nombre, empresa, NIF, email, dirección), fecha de validez, plantilla PDF, idioma y observaciones.
- **Líneas**:
  - Productos del catálogo con buscador por nombre/referencia/EAN, incluidas combinaciones. Muestra precio y stock. El precio sale del catálogo para ese cliente (precios específicos, grupos…) y se puede sobrescribir.
  - **Líneas libres** (portes, instalación, servicios…) con su descripción, referencia, precio y tipo de IVA.
  - Por línea se edita la cantidad, el precio sin IVA, el **% de descuento** y el **tipo de IVA**. Las líneas se pueden reordenar y borrar.
  - **Usar precios del catálogo**: recalcula precios e IVA con los valores actuales del catálogo y mantiene los descuentos.
- **Compartir**: enlace público con token (32 caracteres aleatorios) con botón de copiar, desactivar enlace o generar uno nuevo (el anterior deja de funcionar).
- **PDF**: vista previa o descarga con la plantilla que elijas.
- **Enviar por email**: envía el enlace y, opcionalmente, el PDF adjunto (plantillas en `mails/es` y `mails/en`).
- Desde el listado: duplicar, descargar PDF, eliminar y filtrar por origen (cliente / back office), cliente, estado del enlace, etc.

### Página pública del presupuesto

`index.php?fc=module&module=carttobudget&controller=budget&token=XXXXXXXX` (o la URL amigable equivalente).

- Datos de la tienda (logo, dirección), número, fecha, validez y destinatario.
- Tabla de líneas: producto, combinación, referencia, precio unitario, descuento, unidades, tipo de IVA, total sin IVA y total con IVA.
- Importe bruto y descuentos (si los hay), subtotal sin IVA, **desglose de IVA por tipo** (base y cuota), total de IVA y total.
- **Descargar PDF** (con selector de plantilla si se activa en la configuración).
- **Añadir al carrito**, opcionalmente vaciando antes el carrito actual. Si la opción *Mantener los precios del presupuesto* está activa, los precios y descuentos personalizados de un presupuesto de back office se aplican **solo a ese carrito**, igual que hace PrestaShop al editar precios de un carrito desde el back office (precio específico ligado al carrito).
- La página lleva `noindex` y responde 404 si el token no existe o el enlace está desactivado.

## Plantillas PDF

El PDF se genera con el motor propio de PrestaShop (TCPDF) e incluye el logo de facturas de la tienda o, si no hay, el logo general. Plantillas incluidas:

| Plantilla | Estilo |
|-----------|--------|
| `classic` | Logo a la izquierda, bloques de emisor y cliente, cabecera de tabla gris. |
| `modern` | Banda de color en la cabecera, tabla con filas alternas y total destacado. |
| `minimal` | En blanco y negro, pensada para imprimir. |

El color de acento, el pie o las condiciones (multidioma), el prefijo de numeración (`PRE-000123`) y si se muestran las imágenes de producto se configuran en el módulo.

### Crear tu propia plantilla

Crea una carpeta con `header.tpl`, `content.tpl` y `footer.tpl` en:

- `modules/carttobudget/views/templates/pdf/<nombre>/`, o
- `themes/<tu_tema>/modules/carttobudget/views/templates/pdf/<nombre>/` (también sirve para sobrescribir una plantilla existente).

Aparecerá automáticamente en los selectores. Puedes reutilizar las piezas comunes de `views/templates/pdf/_common/` (`lines.tpl`, `taxes.tpl`, `notes.tpl`, `footer.tpl`) con `{include file=$ctb_pdf_common|cat:'lines.tpl'}`. La variable `$budget` contiene todos los datos ya formateados; consulta `classes/CartToBudgetPresenter.php`.

## Estructura

```
carttobudget/
├── carttobudget.php                         Clase principal: instalación, configuración, hooks, lógica de cliente y PDF
├── classes/
│   ├── CartToBudgetCart.php                 ObjectModel del carrito/presupuesto, líneas y cálculo de totales e IVA
│   ├── CartToBudgetPresenter.php            Datos formateados para la web, el back office y el PDF
│   └── HTMLTemplateCartToBudget.php         Plantilla PDF para la clase PDF de PrestaShop
├── controllers/
│   ├── admin/AdminCartToBudgetController.php           Listado y editor de presupuestos
│   ├── admin/AdminCartToBudgetCustomersController.php  Acceso a carritos guardados por cliente
│   ├── front/ajax.php                       Guardar / restaurar / eliminar (cliente)
│   ├── front/carts.php                      Mi cuenta > Carritos guardados y presupuestos
│   └── front/budget.php                     Página pública por token y descarga del PDF
├── mails/{es,en}/carttobudget_share.*       Email de envío del presupuesto
├── sql/                                     Instalación y desinstalación de tablas
├── translations/es.php                      Traducción al español
└── views/
    ├── css/, js/                            Recursos de front y back office
    └── templates/{admin,front,hook,pdf}/
```

## Notas técnicas

- Los precios se guardan **sin IVA**, junto con el tipo de IVA y el descuento de cada línea. El total por línea se redondea a 2 decimales y el IVA se calcula sobre la base agrupada por tipo, como en una factura.
- Las llamadas AJAX del cliente usan el token estático de PrestaShop (protección CSRF) y comprueban que el carrito pertenece al cliente.
- Las cadenas fuente están en inglés y la traducción al español va en `translations/es.php`. Para otros idiomas usa *Internacional > Traducciones > Traducciones de módulos instalados*.
- Compatibilidad: ver la sección siguiente.

## Compatibilidad

| PrestaShop | PHP | Estado |
|------------|-----|--------|
| 1.7.0 – 1.7.8 | 7.1 – 7.4 | Revisado contra el código fuente de 1.7.8 |
| 8.0 – 8.2 | 7.2 – 8.1 | Revisado contra el código fuente de 8.0.0 y 8.2.8 |
| 9.0 – 9.2 | 8.1 – 8.4 | Revisado contra el código fuente de 9.0.0 y 9.2.0 |

Qué se ha comprobado en cada versión:

- Que existen todas las clases y métodos estáticos de PrestaShop que usa el módulo, con firmas compatibles (`Product::getPriceStatic`, `Cart::updateQty`, `Mail::send`, `PDF`/`HTMLTemplate`, etc.).
- Que las propiedades y métodos de controlador que el módulo redeclara no tienen tipos en PS 8/9, para evitar errores fatales de herencia en PHP 8.
- Que los hooks existen: `displayShoppingCart` en Classic y Hummingbird, y `displayCustomerAccount` y `displayAdminCustomers` en la ficha de cliente (Symfony).
- Que las plantillas compilan y se renderizan sin avisos con Smarty 4.3 (PS 8) y Smarty 4.5 (PS 9). Smarty 4.5 marca como obsoletas las funciones PHP no registradas y las plantillas no usan ninguna.
- Que los PDF se generan con TCPDF 6.4 (PS 8) y 6.8 (PS 9).
- Que no se usa `Tools::displayPrice()` (eliminado en PS 9) ni parámetros con nulo implícito (obsoletos en PHP 8.4).

Todavía no se ha probado instalado en una tienda real de ninguna de estas versiones. Antes de llevarlo a producción conviene instalarlo en una tienda de pruebas y probar el flujo completo.

