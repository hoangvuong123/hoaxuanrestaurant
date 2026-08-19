# 3D Restaurant Menu Book v1.2.0

- 3D flip-book renderer using bundled PDF artwork textures.
- 28 original PDF pages converted to optimized WebP page textures.
- Full viewport layout.
- Drag right/left page edge to turn pages.
- Previous/next, chapter navigation, thumbnails and zoom.
- UI inherits the active WordPress theme font; the page artwork itself keeps the exact PDF typography.

Shortcode:

```
[restaurant_3d_menu_book]
```

The renderer module is loaded from jsDelivr at runtime. The 28 menu page images are bundled locally inside the plugin.

- Ultra-wide layout up to 2400–2600px with camera scaling for large desktop displays.


## 1.2.3
- Ultra-wide wrapper retained, but camera now auto-fits the whole book to both viewport width and height.
- Compact low-height desktop mode.
- Controls moved outside the canvas so they never sit behind the book.
- Thumbnails hidden automatically on short desktop viewports.
