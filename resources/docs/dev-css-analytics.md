At **Website > Developer** a school can add small changes and tracking tags. Everything is checked when saved and again when drawn.

## Extra CSS

You can restyle anything: colours, spacing, fonts, shapes. These are removed:

- anything that looks like a tag (`<...>`)
- `@import`, `@charset`, `@namespace`
- `expression`, `behavior`, `-moz-binding`, `javascript:`
- backslash escapes (they can spell forbidden words)
- `url()` addresses that are not `https://` or a small inline picture

Use the theme's variables so your changes follow the school's colours: `--brand`, `--brand2`, `--bg`, `--text`, `--line`, `--radius`.

## Analytics and verification

Arbitrary scripts are **not** allowed, because they would run on the same domain as the school's dashboard. Instead:

- **Google Analytics**: enter a measurement ID such as `G-ABC123XYZ`. The standard tag is added to public pages (not to previews).
- **Google Search Console** and **Bing Webmaster**: paste the code from the verification tag. We add the meta tag.

If you need a tracker that is not listed, ask the platform owner to add it as a built-in option.
