# TaskFlow — Handoff (updated 2026-09-19, real-time wiring)

Read this file first in a new conversation. It's the single entry point covering
both halves of the project. For deep backend-architecture rationale (why each
layer/file exists), also read `TaskFlow/TASKFLOW_CONTEXT.md` — it's accurate for
the backend but its frontend section is stale (still says "not yet scaffolded";
ignore that, this file supersedes it for frontend status).

Two **separate, independent folders**, neither is a git repo yet (no commits
exist anywhere in this project):

- `C:\Users\Win11\Desktop\Personal Project\TaskFlow` — Laravel 13 backend + static
  design mockups (`design/` folder — HTML/CSS references only, not real app code).
- `C:\Users\Win11\Desktop\Personal Project\TaskFlow-Frontend` — React 19 + Vite app,
  the real frontend.

## How to get all three servers running

Three processes now, all must be running for full functionality (the app still
works without Reverb — you just lose live sync, see below). Laragon (MySQL)
must also be started manually first — it has been observed stopped between
sessions more than once.

```bash
# 1. Start Laragon / MySQL manually first.

# 2. Backend — must be `localhost`, NOT 127.0.0.1 (cookie domain won't match otherwise)
cd "C:\Users\Win11\Desktop\Personal Project\TaskFlow"
php artisan serve --host=localhost --port=8000

# 3. Reverb (WebSocket server for real-time board sync) — see its own section below
cd "C:\Users\Win11\Desktop\Personal Project\TaskFlow"
php artisan reverb:start

# 4. Frontend — must land on port 5173 exactly (CORS/Sanctum are hard-locked to it)
cd "C:\Users\Win11\Desktop\Personal Project\TaskFlow-Frontend"
npm run dev
```

`vite.config.js` has `strictPort: true` so it'll fail loudly instead of silently
picking another port if 5173 is taken. `.claude/launch.json` exists in both
project roots for the `preview_start` browser tool (frontend config just attaches
to an already-running dev server at `http://localhost:5173` rather than starting
one itself — starting `npm run dev` directly via that tool failed on the path's
space, so it's launched manually and attached to instead). Reverb isn't in either
`launch.json` — it doesn't serve a meaningful browser page (`GET /` 404s; it's a
WebSocket server), so `preview_start` wouldn't add anything over running the
command directly.

**Test login:** `test@example.com` / `password`

**Existing seeded/live-tested data in the DB:** a "Product Team" workspace, a
"Sprint 24" board with lists "In Review" / "To Do" / "finished", and a few test
cards/labels/comments left over from manual verification during this project's
development — safe to ignore, delete, or keep using as scratch data.

## Backend — complete through Phase 6, all tests passing

42 tests / 182 assertions, `php artisan test` from `TaskFlow/`. Pragmatic Clean
Architecture: `Domain/` (entities, repository interfaces, exceptions) →
`Application/` (Use Cases, DTOs, access-check Services) →
`Infrastructure/` (Eloquent models + repository implementations, Broadcasting) →
`Http/` (thin Controllers, Resources). Every `*RepositoryInterface` is bound in
`AppServiceProvider`. Full narrative of why each phase's files exist is in
`TASKFLOW_CONTEXT.md`.

Phases done: Auth (Sanctum cookie/session SPA auth) → Workspace+Board →
BoardList+Card (with position/reordering) → Card detail sub-resources (labels,
assignees, checklist items, comments) → Workspace invitations → Real-time
broadcasting (Pusher-protocol, private per-board channels, Card CRUD/reorder
events, `ShouldBroadcastNow`). Plus two later additions not yet folded into
`TASKFLOW_CONTEXT.md`: `GET /api/workspaces/{workspace}/members` (any member can
list members+roles; backs the frontend's avatar rows and assignee/invite
pickers), and workspace rename/delete now have real frontend UI (Settings page).

**Load-bearing gotchas already fixed — do not relitigate these:**
- Guard must be named explicitly everywhere: `Auth::guard('web')->login()/logout()`.
  Unnamed `Auth::login()`/`logout()` crashes once `auth:sanctum` middleware has run.
- `belongsToMany` pivot tables needed explicit names (migration names don't match
  Eloquent's alphabetical-guess convention in a couple of places).
- `User::newFactory()` **and** `UserFactory::$model = User::class` are both
  required after moving models out of `app/Models` — only fixing one breaks the
  other direction silently.
- Tests: Sanctum caches the resolved user on its guard for the test's app
  lifetime, so a second `actingAs()` call in one test method is silently ignored.
  Fixed via `app('auth')->forgetGuards()` before each switch (see the `as()`
  helper used across flow tests).
- `nextPosition()` bug: `(int) null === 0`, so the first list/card in an empty
  board/list landed at position 1 instead of 0 — fixed by checking for `null`
  explicitly.
- `response()->json($resource)` (used everywhere here) bypasses
  `Resource::additional()` — where extra top-level fields were needed (e.g. the
  invite token), built the response as a plain array via `$resource->toArray()`
  spread instead.
- `WorkspaceInvitation::markAccepted()` sets `accepted_at` directly + `->save()`,
  not mass-assignment — that field is deliberately excluded from `#[Fillable]`.
- `routes/channels.php`'s `Broadcast::channel('board.{boardId}', ...)` closure
  must take exactly `(User $user, int $boardId)` — **not** a third typed
  parameter for `EnsureBoardIsAccessible`, resolve that with `app(...)` inside
  the body instead. A real Pusher-protocol broadcaster (Reverb, confirmed live)
  invokes this closure positionally with only `($user, ...routeParams)`; it
  does not do container injection for extra parameters. This was a genuine bug
  invisible until something actually drove `POST /api/broadcasting/auth` for
  real — `BoardChannelAuthorizationTest.php` passed the whole time because it
  calls the closure via `app()->call($callback, [...])`, which *does* support
  DI, masking the real invocation's behavior. If you ever add another
  broadcast channel, keep its closure to `(User $user, ...$routeParams)` only.
- The broadcasting-auth route (`POST /api/broadcasting/auth`, registered via
  `withBroadcasting()` in `bootstrap/app.php`) needs
  `Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful` listed
  explicitly in its own middleware array, not just `auth:sanctum`. Unlike
  routes in `routes/api.php`, it isn't part of the `api` middleware group, so
  `$middleware->statefulApi()` (which only prepends that middleware to the
  `api` group) never reaches it — without it, the session cookie is never
  recognized and every request 401s.

## Frontend — real app, live-verified against the real backend (not just code review)

`TaskFlow-Frontend/`, plain JavaScript (not TS), React 19 + Vite 8,
`react-router-dom` v6, axios (`withCredentials` + `withXSRFToken: true` — required
since :5173/:8000 are different origins), React Context for auth,
`@dnd-kit/core`+`sortable`+`utilities` for drag-and-drop. Automated tests now
exist (Vitest + React Testing Library — see "Automated frontend tests"
below) covering the highest-risk logic; everything else in this section was
verified by driving a real browser and inspecting real network requests
against the live backend/DB, and still hasn't been automated.

**Built and working:**
- `src/pages/Login.jsx`, `Register.jsx` — real forms, split-panel layout.
- `src/context/AuthContext.jsx` — session check via `GET /api/user` on mount.
- `src/pages/Dashboard.jsx` — workspace switching, boards grid, inline
  create-workspace/create-board forms, empty states.
- `src/pages/Board.jsx` — owns all list/card state (`lists`, `cardsByList`);
  renders `ListColumn`/`Card`; full drag-and-drop: cards within a list, cards
  across lists (including into an empty list), and list/column reordering, via
  one flat `DndContext` (sortable ids are prefixed `list-`/`card-`/`list-drop-`
  to share one id namespace — see `parseSortableId` in `Board.jsx`). The board
  name in the topbar and each list's name in `ListColumn.jsx` are always-editable
  inputs (save-on-blur, matching the `Settings.jsx` workspace-rename
  convention — not click-to-edit), each with an adjacent delete button
  (`window.confirm`, cascades to lists/cards or just cards server-side). No
  role gating needed here — unlike workspace rename/delete, board and list
  rename/delete are "any workspace member" on the backend (`EnsureBoardIsAccessible`/
  `EnsureListIsAccessible`), same as create.
- `src/components/CardDetailModal.jsx` — title/description/due-date editing,
  labels (with inline create + 6 preset colors), assignees (workspace-member
  picker), checklist (progress bar, add/toggle/delete), comments (add/delete
  own), delete card. Calls `onUpdated(updatedCard)` / `onDeleted()` back up to
  `Board.jsx` so it can patch `cardsByList` without a full refetch.
- `src/components/Sidebar.jsx`, `Topbar.jsx` — workspace nav, real member
  avatars, real logout.
- `src/components/InviteModal.jsx` + `src/api/invitations.js` +
  `src/pages/AcceptInvitation.jsx` — full workspace invite flow, live-verified
  against the real backend (send, admin-gating, copy-link, revoke, and both
  accept paths). The "Invite" button in `Topbar.jsx` and `Board.jsx`'s own
  topbar is enabled only for the current user's `admin` role in the active
  workspace (checked via the `/members` list). Since no mail transport is
  configured, `InviteToWorkspaceUseCase` returns the raw token in its response
  and the modal builds a shareable accept link
  (`/invitations/{token}/accept`) instead of emailing it — copy-to-clipboard
  via `navigator.clipboard`. `AcceptInvitation.jsx` is a `ProtectedRoute`; an
  unauthenticated visitor is bounced to `/login` (or `/register`) with the
  original location preserved in router state, then returned straight to the
  accept page after auth succeeds (see gotchas below).

- `src/pages/Members.jsx` — workspace roster (name/email/role badge) plus,
  admin-only, the pending-invitations list with revoke (same data InviteModal
  shows, fetched independently since this page doesn't open the modal to see
  it — pending invitations are only fetched at all once `members` has loaded
  and confirmed the viewer is an admin, since the backend 403s that endpoint
  for non-admins and there's no reason to make a call that's known to fail).
  Its own "Invite" button in `Topbar.jsx` opens the same `InviteModal`.
- `src/pages/Settings.jsx` — rename the active workspace (admin-only, saves
  on blur, matching the card-title editing convention in `CardDetailModal`)
  and a danger-zone delete (owner-only — stricter than admin, matching the
  backend's `DeleteWorkspaceUseCase` rule). Both controls disable themselves
  with an explanatory `title` for viewers who lack the role, rather than
  hiding — consistent with how disabled affordances are handled everywhere
  else in this app (see the "deliberately stubbed" note above).
- `src/components/Sidebar.jsx` — "Members"/"Settings" nav items are now real
  navigation (previously `disabled` stubs), and the whole sidebar (including
  the workspace switcher) now carries the active workspace through page
  changes via a `?workspace={id}` query param, so switching from Boards to
  Members to Settings doesn't reset back to whichever workspace happens to
  be first in the list. `Dashboard.jsx`/`Members.jsx`/`Settings.jsx` each
  read that param on mount (falling back to the first workspace if absent).

- `src/echo.js` + real-time wiring in `src/pages/Board.jsx` — live board sync
  across everyone viewing the same board. See its own section below.
- Board rename/delete (`Board.jsx`'s topbar) and list rename/delete
  (`ListColumn.jsx`'s head) — see the drag-and-drop-adjacent gotcha below for
  the one wrinkle in adding editable inputs to an already-draggable list head.
- **Mobile responsiveness**, live-verified at 375px (phone), 768px (tablet,
  exactly on the breakpoint), and full desktop width — nothing was
  desktop-only before this pass. Two breakpoints in `index.css`: 768px turns
  `Sidebar.jsx` into an off-canvas drawer (a `.mobile-menu-btn` hamburger
  rendered in `Topbar.jsx` opens it; `isSidebarOpen` state lives in each of
  `Dashboard.jsx`/`Members.jsx`/`Settings.jsx`, same duplication-over-shared-hook
  convention as everywhere else in this app), and 640px tightens content
  density (board topbar, `CardDetailModal`'s `.rail` switches from a vertical
  sidebar to a horizontal wrapped button row and the modal itself goes
  full-screen, the Login split-panel's brand illustration hides). Also fixed
  four pre-existing fixed-pixel-width forms (`Login.jsx`, `Register.jsx`,
  `Dashboard.jsx`'s first-workspace prompt, `AcceptInvitation.jsx`) that would
  have overflowed horizontally below ~400px — none of them were ever tested
  below desktop width before now. `.page-content` is a new shared class
  replacing three copies of the same inline padding style specifically so
  the mobile media query has a plain CSS rule to override (an inline style
  can't be beaten by a non-`!important` stylesheet rule — the same reasoning
  came up again for `.auth-form-panel`/`.board-actions`, both pulled out of
  inline styles for the same reason rather than reached for `!important`).

**Deliberately stubbed (visible but disabled, not faked as working):**
Attachment button in the card rail — `disabled` with `title="Not built yet"`,
since no backend support exists for attachments at all.

**Not started:** changing a member's role or removing a member (the backend
has no endpoint for either — only listing members and workspace-level
invite/rename/delete exist), standalone label management, and automated
tests beyond what's listed below. Real-time updates only cover
cards (create/update/delete/reorder) — the backend has never broadcast
anything for labels/assignees/checklist items/comments, so
`CardDetailModal.jsx` doesn't live-update if someone else edits the same
card's sub-resources while you have it open; you'll see the change next time
you close and reopen it. Board/list rename and delete also aren't
broadcast — a second viewer only sees a rename/delete after their own next
fetch (reloading the page, or navigating back to the board).

**Frontend gotchas found while building the invite flow:**
- Non-idempotent effects (an API call that fails differently the 2nd time,
  like accepting an invitation) can't rely on the usual `isCurrent` cleanup-flag
  pattern to survive React 18 StrictMode's dev-only double-invoke — cleanup
  doesn't run before the first call's promise settles, so both calls fire for
  real. `AcceptInvitation.jsx` guards the actual `acceptInvitation()` call with
  a `useRef` flag instead (a ref survives the remount within the same
  component instance), so it only ever fires once. This bug was real and
  observable: the first (harmless, from React's perspective) extra call
  succeeded silently, and the second one 404'd with "already used," which the
  UI then wrongly showed to the user as a failure even though they'd actually
  been added to the workspace.
- Don't call `navigate()` manually after `login()`/`register()` succeeds
  *and* have a route guard (`GuestOnlyRoute` in `App.jsx`) also redirect on
  the same auth-context change — they race, and empirically the route guard's
  redirect wins, silently discarding the manual one. Fixed by making
  `GuestOnlyRoute` the single place that decides where a freshly-authenticated
  visitor lands (it reads `location.state?.from`, the same mechanism
  `ProtectedRoute` uses to remember where an unauthenticated visitor was
  trying to go); `Login.jsx`/`Register.jsx` no longer call `navigate()` at all
  after success.

**Frontend gotchas worth knowing before touching drag-and-drop again:**
- `ListColumn.jsx`'s list-name `<input>` and delete `<button>` both need
  `onPointerDown={(e) => e.stopPropagation()}` — they sit inside `.list-head`,
  which has dnd-kit's `{...listeners}` spread on it for list-reordering drags.
  Without stopping propagation there, dnd-kit's `PointerSensor` still sees
  every pointerdown on them; it doesn't misfire on a plain click (drags need
  8px of movement first — see the `activationConstraint` below), but stopping
  it there removes any doubt rather than relying on that threshold.
- `handleDragEnd` in `Board.jsx` deliberately reads `lists`/`cardsByList` from
  the render closure and fires the `reorderLists`/`reorderCards` API call as a
  plain statement *outside* any `setState` updater. Putting the API call inside
  a `setState(prev => ...)` updater causes it to fire twice in dev because React
  18 StrictMode double-invokes updaters intentionally — this was a real bug,
  found and fixed once already.
- If you ever need to simulate drag-and-drop by dispatching synthetic
  `PointerEvent`s (e.g. for a test), they need `isPrimary: true` in the init
  dict or dnd-kit's `PointerSensor` never activates, and delays between dispatched
  events must use `setTimeout`, not `requestAnimationFrame` — rAF never fires on
  a backgrounded/hidden tab, which silently breaks dnd-kit's collision detection.

## Automated frontend tests

Vitest + React Testing Library, set up from scratch this session (there were
zero frontend tests before). Run via `npm test` (single run) or
`npm run test:watch` from `TaskFlow-Frontend/`. Config lives in the `test` key
of `vite.config.js` (jsdom environment, `src/test/setup.js` loads
`@testing-library/jest-dom`'s matchers, `globals: true` so `describe`/`it`/
`expect`/`vi` don't need per-file imports).

**28 tests across 3 files** — this is deliberately *not* attempting broad
coverage of every component (there are dozens); it targets the two things
most worth protecting against regressions in a project this size:

- `src/pages/boardCardState.test.js` (16 tests) — the highest-value tests in
  the suite. `Board.jsx`'s four card-state-transition functions
  (create/update/delete/reorder) used to be inline closures inside the
  component; they're now extracted to the pure, dependency-free
  `src/pages/boardCardState.js` specifically so they're unit-testable without
  rendering anything. This is exactly the logic that produced the real
  duplicate-card bug during the real-time work (see "Real-time board sync"
  below) — the tests explicitly cover the upsert-vs-append distinction, both
  possible arrival orders of a local action vs. its own Echo broadcast, and
  idempotency of every operation when applied twice. `Board.jsx` itself now
  just calls these functions from inside its `setCardsByList` updaters, so
  its own behavior is unchanged — this was a pure extraction, not a rewrite
  (confirmed with a live create/delete smoke test after the refactor, on top
  of the new unit tests passing).
- `src/pages/Login.test.jsx` (4 tests) and `src/components/InviteModal.test.jsx`
  (8 tests) — component-level coverage via `@testing-library/react` +
  `@testing-library/user-event`, mocking `../context/AuthContext` and
  `../api/invitations` respectively (`vi.mock(...)`, no real network calls).
  Chosen as representative examples: `Login` is a simple form with
  request/error handling, `InviteModal` is the most complex recently-built
  component (async submission, role selection, conditional share-link UI,
  pending-list fetch/revoke, clipboard copy).

**One real testing-environment gotcha found:** `userEvent.setup()` installs
its *own* clipboard stub on `navigator.clipboard` (visible as a
`Clipboard [EventTarget]` object with internal management symbols if you log
it). Defining a custom `navigator.clipboard` mock *before* calling
`userEvent.setup()` gets silently clobbered — the mock's `writeText` is never
called and there's no error, just a confusing "0 calls" assertion failure.
Fix: define the clipboard mock *after* `userEvent.setup()`, not before (see
`InviteModal.test.jsx`'s clipboard test for the working order).

If you add more tests, the two other candidates most worth it before
reaching for exhaustive coverage: `Board.jsx`'s Echo effect (mock `../echo`
and assert the right handler runs for each event name — this would have
caught a missing `.` prefix on a broadcast event name, a real category of
mistake documented in the real-time section below) and `CardDetailModal.jsx`
(large, many sub-resources, currently zero coverage).

## Real-time board sync (Echo + Reverb)

Everyone viewing the same board's card creates/updates/deletes/reorders now
appear live for everyone else on it, with no reload — verified with two
browser tabs logged in as two different accounts simultaneously.

**Reverb, not Soketi.** The backend's broadcasting config originally
anticipated Soketi (a self-hosted, free, Node-based implementation of the
Pusher protocol) — that's what `TASKFLOW_CONTEXT.md` and earlier `.env`
comments still describe if you find old references. It doesn't work on this
dev machine: Soketi's native `uWebSockets.js` dependency only supports
Node 14/16/18 on Windows, and this machine runs Node 22. Rather than
downgrading Node globally to run one dev dependency, this project uses
**Laravel Reverb** instead — Laravel's own first-party WebSocket server, pure
PHP, no Node involved at all, and speaks the exact same Pusher protocol, so
nothing on the frontend (`laravel-echo` + `pusher-js`) cares which one is
actually running underneath. `composer.json` now depends on `laravel/reverb`;
`config/reverb.php` (server-side: which port Reverb itself listens on) and
`config/broadcasting.php`'s `'reverb'` connection (client-side: how Laravel's
`Broadcast`/`event()` calls reach it) are both configured. Env vars: backend's
`.env` has `REVERB_APP_ID/KEY/SECRET/HOST/PORT/SCHEME`; frontend's `.env` has
the matching `VITE_REVERB_*` — if you ever regenerate the backend's Reverb
credentials (`php artisan reverb:install` again), copy the new key into the
frontend's `.env` too, since Echo connects to Reverb directly, not through the
Laravel backend's own port.

**Two real backend bugs found and fixed getting this working** (both were
invisible until an actual Pusher-protocol broadcaster was live — see the
backend gotchas list above for the full explanation of each):
1. `routes/channels.php`'s channel-authorization closure had a third,
   container-injected parameter that a real broadcaster's invocation doesn't
   support (only the existing tests' own more-lenient invocation style did).
2. The broadcasting-auth route was missing `EnsureFrontendRequestsAreStateful`
   from its own middleware list, since it isn't part of `routes/api.php`'s
   `api` middleware group where `statefulApi()` normally adds it.

**One real frontend bug found and fixed:** `ShouldBroadcastNow` broadcasts
synchronously *during* the original request — so the WebSocket echo of your
own card-create can reach your own browser *before* that same create
request's own HTTP response does. `Board.jsx`'s local "just created this
card" handler and its "received a card.created echo" handler used to be two
different functions (a blind append vs. an upsert), which broke exactly when
the echo won that race, duplicating the card in whichever tab created it.
Fixed by unifying both into one upsert-by-id `handleCardCreated`, used for
both paths — see its comment in `Board.jsx`. `handleCardUpdated`/
`handleCardDeleted`/the reorder handler didn't have this problem: replace-by-id,
filter-by-id, and rebuild-from-authoritative-list are all naturally idempotent
regardless of which order two calls for the same underlying change arrive in;
a blind *append* is the only shape of update that isn't.

`src/echo.js` uses a custom `authorizer` (not Echo's built-in
`authEndpoint`/http options) that posts through the app's own `client.js`
axios instance — needed so the private-channel auth request carries the same
session cookie and `X-XSRF-TOKEN` header every other API call already gets;
Echo's own auth transport doesn't know about either.

## Design mockups (`TaskFlow/design/`)

Static HTML/CSS only, not wired to any app — a visual reference the real
frontend's `src/index.css` was built to match (tokens: `--blue:#2F6FED`,
`--tint:#EEF3FF`, `--ink:#1B1F3B`, Poppins headings + Inter body, pill buttons).
Kept intentionally separate from both the backend and the real frontend so it's
never confused with application code.

## Suggested next steps (not yet decided by the user — offer, don't assume)

Roughly in order of value: (1) more automated frontend test coverage
(`Board.jsx`'s Echo effect and `CardDetailModal.jsx` are the two biggest
gaps — see "Automated frontend tests" above); (2) broadcasting card
sub-resource changes (labels/assignees/checklist/comments), and board/list
rename/delete, if live-updating an open `CardDetailModal` or another
viewer's board view for those ever matters enough to justify new backend
events for them; (3) changing a member's role or removing a member, if that
ever matters enough to justify new backend endpoints for it (see "Not
started" above).
