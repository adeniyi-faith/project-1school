Adino School Systems is a Laravel and React application. A school's public website is drawn by the server, and you can build on it without touching the dashboard code.

| You want to | Use |
|---|---|
| Show school data in another site or app | [Site data (JSON)](/docs/dev-site-data) |
| React to an inquiry, enrolment or payment | [Webhooks](/docs/dev-webhooks) |
| Read inquiries from a script | [Read-only key](/docs/dev-api) |
| Add a new look or a new section | [Themes and sections](/docs/dev-themes-blocks) |
| Small design tweaks or analytics | [Extra CSS and analytics](/docs/dev-css-analytics) |
| Back up or copy a website | [Export and import](/docs/dev-export-import) |

School administrators find the settings for these at **Website > Developer**.

## Safety rules that apply everywhere

- Data links are public and contain only what the website already shows.
- Keys and webhook secrets are shown once and stored as a fingerprint or encrypted.
- Webhooks only go to public `https` addresses.
- CSS is cleaned so it cannot run code or load other files.
- Everything is limited to one school; there is no way to read another school's data.
