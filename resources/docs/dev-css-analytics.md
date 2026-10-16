At **Website > Developer** a school can add small changes and tracking tags. Everything is checked when saved and again when drawn.

## Extra CSS

You can restyle anything: colours, spacing, fonts, shapes. These are removed:

- anything that looks like a tag (`<...>`)
- `@import`, `@charset`, `@namespace`
- `expression`, `behavior`, `-moz-binding`, `javascript:`
- backslash escapes (they can spell forbidden words)
- `url()` addresses that are not `https://` or a small inline picture

Use the theme's variables so your changes follow the school's colours: `--brand`, `--brand2`, `--bg`, `--text`, `--muted`, `--soft`, `--rule`, `--brand-ink`, `--brand-t` (main colour as text) and `--brand2-t` (second colour as text).

## Restyling a whole theme

Extra CSS is ordinary CSS, so any web developer can read it. Three things are specific to this platform:

1. **Scope it to the theme.** Start each rule with `body[data-theme="modern"]` (or the theme's key), so it only applies to that theme.
2. **Set the variables first.** Changing `--bg`, `--text`, `--brand` and `--brand2` on `body[data-theme="modern"]` recolours most of the site at once. Fix the few places that still look wrong afterwards.
3. **Reordering sections.** The page sections are direct children of `#main`. Make it a flex column and give each section an `order`: `#main{display:flex;flex-direction:column}` then `#main section:nth-of-type(6){order:2}`. Sections are counted from the top of the page. The `>` character is removed from CSS when saved, so use `section:nth-of-type(n)` instead of `#main > :nth-child(n)`.

CSS can restyle and reorder what is already on the page. It cannot add new sections or text. For that, add a section type or a theme folder (see Themes and sections).

## Analytics and verification

Arbitrary scripts are **not** allowed, because they would run on the same domain as the school's dashboard. Instead:

- **Google Analytics**: enter a measurement ID such as `G-ABC123XYZ`. The standard tag is added to public pages (not to previews).
- **Google Search Console** and **Bing Webmaster**: paste the code from the verification tag. We add the meta tag.

If you need a tracker that is not listed, ask the platform owner to add it as a built-in option.
