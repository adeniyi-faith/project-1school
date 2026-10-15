Two public links are available for every school with a live website. They need no key, allow any website to read them (CORS), and are cached for five minutes.

## Whole site

`GET /s/<slug>/site.json`

```json
{
  "school": { "name": "Bright Stars", "slug": "bright-stars", "tagline": "...", "logo": "https://...", "address": "...", "phone": "...", "email": "...", "social": {}, "url": "https://.../s/bright-stars" },
  "theme": { "key": "modern", "label": "Modern", "brand": "#002855", "brand2": "#c10230" },
  "pages": [ { "title": "Home", "slug": "home", "url": "...", "is_home": true, "in_menu": true, "description": "...", "blocks": [] } ],
  "live": {
    "fees": [ { "class": "Primary 4", "lines": [ { "name": "Tuition", "amount": 85000, "when": "each term" } ], "term_total": 85000 } ],
    "terms": [ { "name": "Term 1", "year": "2026/2027", "start": "2026-09-07", "end": "2026-12-18" } ],
    "holidays": [ { "name": "Founders Day", "date": "2026-11-05" } ],
    "team": [ { "name": "Ngozi Eze", "role": "Head Teacher", "photo": null } ],
    "events": [ { "title": "Sports Day", "date": "2026-11-20", "place": "School field", "url": "..." } ]
  }
}
```

## News and events

`GET /s/<slug>/news.json?category=events`

Returns up to 50 live posts, newest first: `title`, `slug`, `url`, `category`, `summary`, `cover`, `published_at`, `event_date`, `event_place`.

## Example

```js
const res = await fetch('https://your-platform/s/bright-stars/site.json');
const { live } = await res.json();
console.log(live.fees);
```
