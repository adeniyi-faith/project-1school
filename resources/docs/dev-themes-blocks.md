## A new section

```bash
php artisan site:make-block "Price list"
```

This makes `resources/website-blocks/price_list/` with two files:

- `block.json` names the section and its boxes. Kinds: `text`, `textarea`, `select`, `number`, `image` and `list` (rows of boxes).
- `view.blade.php` draws it. `$b` holds the boxes; `$school`, `$site` and `$theme` are also available. Use the site's own classes (`sec`, `wrap`, `sec-head`, `card`, `btn`) and colour variables (`var(--brand)`, `var(--brand2)`) so every theme suits it.

The section appears at once in every school's page editor. Invalid files are ignored, so a typo never breaks a school's site.

## A new theme

```bash
php artisan site:make-theme "Sunrise" --base=modern
```

This makes `resources/website-themes/sunrise/` with:

- `theme.json`: label, note, `base` (the built-in theme whose layout is used), colours (`brand`, `brand2`, `bg`, `soft`, `text`), and optional `head` and `body` font stacks and `fonts` (Google Fonts families).
- `style.css`: extra CSS added after the base styles. Scope rules with `body[data-site-theme="sunrise"]`.

The theme appears in **Website > Themes** for every school. Schools can still change the two main colours. The CSS goes through the same cleaner as the school's own CSS.

## Notes

- Commit these folders with your code; they deploy like any other file.
- A custom theme reuses the layout of its base. To change markup, add views to the base theme's folders in `resources/views/website`.
