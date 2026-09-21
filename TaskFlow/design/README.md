# TaskFlow design mockups

Static HTML/CSS design mockups for the TaskFlow frontend. **Not application
code** — nothing here is consumed by the Laravel backend or will be consumed
directly by the eventual React frontend. This is a visual reference only,
kept separate from `app/`, `resources/`, and `routes/` so it's never
confused with real backend code.

Style extended from a reference file the user provided (an Any.do-style
landing page): Poppins + Inter, blue `#2F6FED` accent, soft blue tint
backgrounds, pill buttons, rounded cards. Tokens live in `shared.css` and
are reused across every screen so the product feels like one system, not
six unrelated mockups.

## Files

- `shared.css` — design tokens (colors, radii, shadows) and shared component
  styles (buttons, nav, sidebar, avatars, chips) used by every screen below.
- `landing.html` — marketing landing page.
- `login.html` — sign-in screen.
- `dashboard.html` — workspace dashboard (boards grid).
- `board.html` — the kanban board view (lists + cards).
- `card-detail.html` — the card detail modal, shown over a dimmed board.
- `invite-modal.html` — the invite-teammate modal, shown over a dimmed
  dashboard. Deliberately mirrors the real token-based invite flow already
  built in the backend (copyable link, pending-invitations list with revoke)
  rather than a generic "enter an email" form.

## Viewing

Open any file directly in a browser, or serve the folder (e.g.
`php -S 127.0.0.1:4517 -t design`) so the shared stylesheet and Google
Fonts links resolve — some browsers restrict `file://` pages from loading
external resources.
