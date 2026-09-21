# TaskFlow — Project Context

A solo full-stack CV project: a Trello-style Kanban board manager. Built to demonstrate real backend + frontend skills (not a tutorial clone) — auth, roles, drag-and-drop, and eventually real-time sync.

> **Frontend status note (2026-09-19):** the "not yet scaffolded" line below is
> stale. A real React app now exists at `../TaskFlow-Frontend` with working
> Auth, Dashboard, Board view, Card detail modal, and full drag-and-drop. See
> `../PROJECT_HANDOFF.md` for current full-stack status — read that file first.

## Stack

- **Backend:** Laravel 13, MySQL, Sanctum (cookie/session-based SPA auth, not Bearer tokens)
- **Frontend:** React (Vite) — not yet scaffolded. Designs already exported from Stitch (login, dashboard, board view, card detail modal, invite modal).
- **Real-time:** Backend broadcasting done (Phase 6), Pusher-protocol, private per-board channels, Card CRUD/reorder events — Echo now wired up on the frontend too, and live-verified. **Update (2026-09-19):** this line and the Phase 6 section below describe the original Soketi-or-pusher.com plan; that changed to **Laravel Reverb** once Echo was actually wired up (Soketi's native Node dependency doesn't run on this dev machine's Node 22). See `../PROJECT_HANDOFF.md`'s "Real-time board sync" section for the current setup and two real backend bugs that surfaced only once a real broadcaster was live.

## Database schema (migrated and confirmed working)

Tables: `users`, `workspaces`, `workspace_user` (pivot, role: admin/member), `boards`, `board_lists`, `cards`, `card_user` (pivot, assignees), `labels`, `card_label` (pivot), `checklist_items`, `comments`, `workspace_invitations`.

Relationship chain: `workspaces` → `boards` → `board_lists` → `cards` → (assignees, labels, checklist_items, comments).

## Models

Now living in `app/Infrastructure/Persistence/Models/` (moved there as each feature got refactored — see Architecture section): `User`, `Workspace`, `Board`, `BoardList`, `Card`, `Label`, `ChecklistItem`, `Comment`, `WorkspaceInvitation`.
`app/Models/` is now empty of feature models — every table has a home in the Clean Architecture structure.

## Auth — done, refactored into Clean Architecture, has a passing feature test

- `App\Http\Controllers\Api\AuthController` with `register`, `login`, `logout`, `user` — thin, delegates to `RegisterUserUseCase`/`LoginUserUseCase`.
- Routes: `POST /api/register`, `POST /api/login`, `POST /api/logout` (auth:sanctum), `GET /api/user` (auth:sanctum).
- `config/cors.php` published: `supports_credentials: true`, origin locked to `http://localhost:5173` (not wildcard).
- `bootstrap/app.php` has `$middleware->statefulApi();` — required for cookie-based Sanctum auth to actually authenticate.
- `.env`: `SANCTUM_STATEFUL_DOMAINS=localhost:5173`, `SESSION_DOMAIN=localhost`, `FRONTEND_URL=http://localhost:5173`.
- Known gotcha (resolved): must test consistently against `http://localhost:8000`, never `127.0.0.1:8000` — cookie domain won't match otherwise. Postman needs a `Referer: http://localhost:5173` header (via pre-request script) and the `XSRF-TOKEN` cookie decoded into an `X-XSRF-TOKEN` header, since Postman doesn't behave like a real browser.
- Found and fixed while refactoring: `Auth::login()`/`Auth::logout()` with no guard named crash once `auth:sanctum` middleware has run (it calls `Auth::shouldUse('sanctum')`, and Sanctum's guard has no login/logout methods). Every Auth call in `AuthController` now names `Auth::guard('web')` explicitly.
- React login screen not built yet.
- Test: `tests/Feature/AuthFlowTest.php`.

## Workspace + Board (Phase 2) — done, has a passing feature test

- `App\Http\Controllers\Api\WorkspaceController` (full `apiResource`) and `App\Http\Controllers\Api\BoardController` (`apiResource('workspaces.boards')->shallow()` — index/store carry `{workspace}`, show/update/destroy carry only `{board}`).
- Business rules: creating a workspace makes the creator an `admin` member (`CreateWorkspaceUseCase`); viewing requires membership; renaming requires `admin` role; deleting requires being the `owner_id`; board access is workspace membership (any member can create/view/update/delete boards — no separate board-level roles yet).
- `App\Domain\Shared\DomainException` — every Domain exception across every feature extends this and declares its own `httpStatus()`; one handler in `bootstrap/app.php` maps all of them to JSON, so controllers never catch Domain exceptions themselves.
- No route-model-binding on Workspace/Board routes — the raw id is passed to the Use Case, which does its own lookup and throws `WorkspaceNotFoundException`/`BoardNotFoundException` (not Laravel's generic 404).
- Gotchas found and fixed while building this (all pre-existing, just never previously exercised by a real request):
  - `Workspace`/`Board` had no `$fillable` — added `#[Fillable([...])]` to both.
  - `belongsToMany` on `User::workspaces()`/`Workspace::members()` didn't name the pivot table, so Eloquent guessed `user_workspace` (alphabetical) instead of the real `workspace_user` — now passed explicitly.
  - `UserFactory` needs *two* separate overrides to work from its new namespace: `User::newFactory()` (model → factory direction) **and** `UserFactory::$model` (factory → model direction, which `Factory::create()` resolves independently). Only having one silently breaks the other.
  - Test-only gotcha: Sanctum's guard caches the resolved user on its `RequestGuard` instance for the life of the test's app instance, so a second `actingAs()` call in the same test method is silently ignored on `auth:sanctum` routes unless `app('auth')->forgetGuards()` runs first.
- Test: `tests/Feature/WorkspaceBoardFlowTest.php`.

## Kanban board core: BoardList + Card (Phase 3) — done, has a passing feature test

- `App\Http\Controllers\Api\BoardListController` (`apiResource('boards.lists')->shallow()` + `PATCH boards/{board}/lists/reorder`) and `App\Http\Controllers\Api\CardController` (`apiResource('lists.cards')->shallow()` + `PATCH lists/{list}/cards/reorder`).
- Access chain composes rather than repeats: `EnsureCardIsAccessible` calls `EnsureListIsAccessible` calls `EnsureBoardIsAccessible` calls workspace-membership. Each level only knows about the one directly beneath it — Card access is "is this card's list's board's workspace accessible", built from three small pieces, not one long check written out.
- Reordering, the new problem this phase introduced, ended up as two different rules once actually thought through:
  - **Lists on a board are a closed set.** `ReorderBoardListsUseCase` requires the given `list_ids` to be *exactly* the board's current lists (`array_diff` both directions) — same set, new order. Anything else (missing id, foreign id) is `422 InvalidListReorderException`.
  - **Cards in a list are an open set** — a drag can bring in a card that used to live in a different list. So `ReorderCardsUseCase` doesn't check "same set as before"; it checks every given card exists and currently belongs to a list on the *same board* as the target list, then unconditionally sets each card's `board_list_id` + `position` from the given array. One endpoint (`PATCH /lists/{list}/cards/reorder`) handles both a same-list reorder and a cross-list drag this way — the target list's array is authoritative for "what's in this list now, in what order"; the source list needs no separate update because a moved card just stops matching its old list's `where('board_list_id', ...)` query.
  - Position values don't need to stay contiguous for this to work (a list a card was dragged out of is left with gaps) — only relative order matters, so the source list is never touched.
- Gotchas found and fixed this phase:
  - `nextPosition()` in both new repositories had an off-by-one: `max('position')` returns `null` for an empty board/list, and casting `null` to `(int)` gives `0`, so `0 + 1 = 1` — the very first list/card landed at position 1 instead of 0. Fixed by checking for `null` explicitly rather than casting through it.
  - Same "moved model needs `#[Fillable]`" and "run `composer dump-autoload` after moving a model" gotchas as Phase 2, same fixes.
- Test: `tests/Feature/BoardListCardFlowTest.php`.

## Card detail modal: checklist items, comments, labels, assignees (Phase 4) — done, has a passing feature test

- Four sub-features, each scoped to what it actually needed rather than a uniform template:
  - **Checklist items** (`App\Http\Controllers\Api\ChecklistItemController`, `apiResource('cards.checklist-items')->shallow()->except(['show'])->parameters(['checklist-items' => 'item'])` + `PATCH cards/{card}/checklist-items/reorder` + `PATCH checklist-items/{item}/toggle`): full CRUD (no standalone `show` — only ever viewed as part of the card's list), closed-set reorder like `BoardList`'s, and a dedicated `ToggleChecklistItemUseCase` rather than folding the checkbox into the title-update endpoint — checking an item off is its own business action, not a generic field write.
  - **Comments** (`App\Http\Controllers\Api\CommentController`, `apiResource('cards.comments')->shallow()->only(['index','store','destroy'])`): no update at all — comments aren't editable, by design, to keep scope proportionate. Delete is **author-only** (`CommentAuthorMismatchException`, 403) — narrower than "any workspace member", and deliberately its own exception rather than reusing `WorkspaceAccessDeniedException`, since it's a genuinely different rule that just happens to also map to 403.
  - **Labels** (`App\Http\Controllers\Api\LabelController`, workspace-scoped CRUD via `apiResource('workspaces.labels')->shallow()->except(['show'])`) plus a separate `App\Http\Controllers\Api\CardLabelController` for attach/detach on a card. Attaching validates the label's `workspace_id` matches the card's board's workspace (`LabelWorkspaceMismatchException`, 422) — a workspace's label palette can't leak onto another workspace's card.
  - **Assignees**: no new Domain feature — this is pivot management on the Card aggregate, so `attachLabel`/`detachLabel`/`labelsFor` and `assignUser`/`unassignUser`/`assigneesFor` were added directly to `CardRepositoryInterface`, the same way `WorkspaceRepositoryInterface` owns its own membership pivot methods rather than a separate "Membership" feature. `App\Http\Controllers\Api\CardAssigneeController` handles list/assign/unassign. Assigning validates the target user is a workspace member (`AssigneeNotWorkspaceMemberException`, 422) via the same `WorkspaceRepositoryInterface::isMember()` Phase 2 built; unassigning has no such check — always safe to remove.
- Retroactive cleanup done at the start of this phase: `EnsureWorkspaceIsAccessible` (Application/Workspace/Services) was extracted once "find workspace, check membership" was about to become a 4th duplicate (it already appeared identically in `ViewWorkspaceUseCase`, `CreateBoardUseCase`, `ListWorkspaceBoardsUseCase`). `EnsureBoardIsAccessible` was also refactored to build on it. Good example of the "extract on the 3rd+ real occurrence, not preemptively" rule actually firing.
- No bugs surfaced this phase's first test run — the `(int)` cast discipline (workspace/label/assignee comparisons) and the `null`-safe position pattern from Phase 3 held up without needing to be relearned.
- Test: `tests/Feature/CardDetailFlowTest.php`.

## Invite teammates (Phase 5) — done, has a passing feature test

- `App\Http\Controllers\Api\WorkspaceInvitationController`: `apiResource('workspaces.invitations')->shallow()->only(['index','store','destroy'])` (admin-only: list/create/revoke pending invitations) plus a standalone `POST invitations/{token}/accept`.
- Flow: an admin invites an email + role → a random 40-char token is generated and stored on `workspace_invitations` → the invitee (already an authenticated TaskFlow user — no "invite an email with no account yet" flow in this phase, kept out of scope deliberately) hits accept with the token → their `email` must case-insensitively match the invitation's `email` (`InvitationEmailMismatchException`, 403 — the token alone isn't enough, you have to be logged in as the address it was sent to) → a `workspace_user` row is created with the invitation's `role` (reusing `WorkspaceRepositoryInterface::addMember()`, the same method `CreateWorkspaceUseCase` from Phase 2 uses) → the invitation is marked accepted.
- No actual email sending — the token comes back directly in the `store` response for now (frontend isn't built yet either). That's a deliberate one-time reveal: `WorkspaceInvitationResource` never includes `token`in its normal `toArray()`; the controller appends it manually onto the create response only, the same way an API key is shown once at creation and never again in a listing.
- Business rules, each with its own exception even though several map to the same HTTP status (a reused exception should mean a reused *rule*, not just a matching status code):
  - Inviting requires being a workspace `admin` (`WorkspaceAccessDeniedException`, reused from Phase 2 — this really is the same rule as `UpdateWorkspaceUseCase`'s).
  - Can't invite an email that's already a member (`AlreadyAMemberException`, 422).
  - Can't have two pending invitations to the same email in one workspace at once (`PendingInvitationAlreadyExistsException`, 422).
  - Accepting requires the logged-in email to match (`InvitationEmailMismatchException`, 403 — narrower than "any authenticated user", so its own exception, same reasoning as Phase 4's `CommentAuthorMismatchException`).
  - Revoking only applies to a still-pending invitation — an already-accepted one is treated as `InvitationNotFoundException` (404), same as if it never existed, since there's nothing left to revoke.
- Retroactive cleanup at the start of this phase (same instinct as Phase 4's `EnsureWorkspaceIsAccessible` extraction): "must be a workspace admin" was about to appear a 4th time (rename, invite, list-invitations, revoke-invitation), so `EnsureActingUserIsWorkspaceAdmin` (Application/Workspace/Services) was extracted, layered on top of `EnsureWorkspaceIsAccessible` the same way `EnsureCardIsAccessible` layers on `EnsureListIsAccessible`. `UpdateWorkspaceUseCase` was refactored to use it too.
- Gotchas found and fixed this phase:
  - `Resource::additional(['token' => ...])` looked like the obvious way to add the one-time token to the create response, but `additional()` only merges through Laravel's `toResponse()` pipeline — the exact pathway this codebase's `response()->json(...)` convention deliberately bypasses (see Phase 2's wrapping-consistency note below). Going through `response()->json($resource)` would have silently dropped the token. Fixed by building the response as a plain array instead: `[...$resource->toArray($request), 'token' => $invitation->token]`.
  - `accepted_at` wasn't in `WorkspaceInvitation`'s `#[Fillable]` list (deliberately — it's an internal state transition, not something that should be mass-assignable), so `markAccepted()`'s `update(['accepted_at' => now()])` was silently discarded with no error, leaving invitations permanently "pending" and re-acceptable forever. Fixed by setting the attribute directly (`$invitation->accepted_at = now(); $invitation->save();`) instead of mass-assigning it — the correct fix here is bypassing mass assignment for this one controlled internal write, not loosening `$fillable`.
- Test: `tests/Feature/WorkspaceInvitationFlowTest.php`.

## Real-time sync (Phase 6) — backend done, has passing tests; frontend Echo wiring also done (2026-09-19)

> **This section describes the original plan (Soketi or pusher.com). The
> actual broadcaster ended up being Laravel Reverb instead — see
> `../PROJECT_HANDOFF.md`'s "Real-time board sync" section for why and for
> two real bugs (one in `routes/channels.php`, one in `bootstrap/app.php`'s
> broadcasting middleware) that only surfaced once a real broadcaster was
> actually running end-to-end.** The architecture described below (event
> classes, channel structure, `ShouldBroadcastNow`) is still accurate —
> only the broadcaster driver and two small fixes changed.

- `composer require pusher/pusher-php-server` installed. `config/broadcasting.php` created from scratch (didn't exist before — this is a minimal Laravel 13 install, no `install:broadcasting` scaffolding had been run). One `pusher` connection, env-driven, works against either a real Pusher app or a self-hosted Soketi instance (same protocol) — just point `PUSHER_HOST`/`PORT`/`SCHEME` at Soketi instead of pusher.com. Default `BROADCAST_CONNECTION=log` until one is actually running; testing uses `null` (see `phpunit.xml`).
- `bootstrap/app.php` gained `->withBroadcasting(channels: routes/channels.php, attributes: ['prefix' => 'api', 'middleware' => ['auth:sanctum']])` — registers `POST /api/broadcasting/auth` under the same `auth:sanctum` guard as every other endpoint, so Echo's auth request authenticates via the existing session cookie.
- `routes/channels.php`: one private channel, `board.{boardId}`, authorized by directly reusing `EnsureBoardIsAccessible` from Phase 3 (catching `DomainException` → `false`) — who can *listen* on a board is exactly who can *fetch* it over the API, so the same check governs both rather than being re-derived.
- Broadcasting events live in `App\Infrastructure\Broadcasting\Events\Card\` (not Laravel's default `App\Events\`) — continuing this project's own taxonomy where `Infrastructure/` holds framework/technology-specific code, the same reasoning that put Eloquent models under `Infrastructure/Persistence/Models`. Four events, all `ShouldBroadcastNow` (synchronous — see below): `CardCreated`, `CardUpdated`, `CardDeleted` (carries plain ids, not the model — the row is already gone by the time it fires), `CardsReordered` (one event for the whole target list's new order, not one per card — matches how `ReorderCardsUseCase` already models the operation: the target list's array is authoritative for "what's in this list now, in what order," whether that's a same-list reorder or a cross-list drag).
- Events are fired with the plain `event()` helper from inside `CreateCardUseCase`/`UpdateCardUseCase`/`DeleteCardUseCase`/`ReorderCardsUseCase`, at the end of `execute()` — not from the HTTP layer. Consistent with this codebase's existing (if light) use of Laravel facades directly inside Use Cases (e.g. `Hash::make` in `RegisterUserUseCase`) rather than wrapping everything behind another interface.
- `ShouldBroadcastNow` (synchronous), not `ShouldBroadcast` (queued): a queued broadcast silently sends nowhere without a `queue:work` process running, which is more operational overhead than a solo dev-stage project needs right now. Worth revisiting if broadcaster latency ever needs to stop blocking the request.
- No bugs in the application code this phase — the only fix needed was in a test's own assertion (see below). The two things most likely to break here (mass-assignment silently no-op'ing, `response()->json()` vs `toResponse()` wrapping) were exactly the traps caught during Phase 5, so they were avoided by construction rather than caught after the fact.
- Test-worthy gotcha: the built-in `null`/`log` broadcasters (what tests and un-configured local dev use) make `auth()` a total no-op — they never call the registered channel callback at all, so hitting `POST /api/broadcasting/auth` in a test proves nothing about authorization logic. `tests/Feature/BoardChannelAuthorizationTest.php` instead retrieves the registered closure directly via the public `Broadcaster::getChannels()` registry (`Broadcast::getChannels()->get('board.{boardId}')`) and invokes it through `app()->call()` so the container still resolves `EnsureBoardIsAccessible`. A real Pusher/Ably/Redis broadcaster's `auth()` *would* call it for real — this test exists specifically because the ones used in dev/test don't.
- Test-only fix: a test assertion assumed `PrivateChannel`'s `->name` was the bare channel name given to its constructor — it's actually already prefixed (`'private-board.1'`, not `'board.1'`) by the constructor. App code was correct; the test assertion was wrong. Caught by the first run of `tests/Feature/CardBroadcastingTest.php`, not a pre-existing bug like every other phase's finding.
- Tests: `tests/Feature/CardBroadcastingTest.php` (event dispatch — `Event::fake()`, asserts channel + payload per action, and that a rejected action broadcasts nothing) and `tests/Feature/BoardChannelAuthorizationTest.php` (channel authorization).

## Architecture: pragmatic Clean Architecture (established, in use)

```
app/
├── Domain/{Feature}/Repositories/{Feature}RepositoryInterface.php, Exceptions/
├── Domain/Shared/DomainException.php — base class every Domain exception extends
├── Application/{Feature}/UseCases/, DTOs/, Services/ (only when logic is genuinely shared across Use Cases)
├── Infrastructure/Persistence/Models/ (Eloquent), Repositories/ (implements Domain interfaces)
└── Http/Controllers/Api/, Requests/, Resources/
```

Deliberately skipping pure Domain Entities (using Eloquent models as both persistence + entity) — full separation isn't worth the overhead solo. The real payoff is Use Cases depending on repository _interfaces_, bound to Eloquent implementations in `AppServiceProvider` — that's the dependency inversion worth talking about in interviews.

Conventions settled while building Auth and Workspace/Board, apply these to every new feature:
- DTOs for write operations (request payloads); plain scalar params for reads (a lookup by id doesn't need a class).
- Trivial actions with no real business rule (logout, a plain "fetch the authenticated user") stay as one-line controller code — no Use Case for its own sake.
- Authorization that's a genuine business rule (who can view/edit/delete) lives in the Use Case, not a Laravel Policy or middleware — it needs to hold regardless of caller (HTTP, console, queue).
- Every controller action wraps its Resource in `response()->json(...)` explicitly — a bare `return new SomeResource(...)` gets Laravel's automatic `{"data": ...}` wrapping, `response()->json(...)` doesn't; picking one everywhere avoids an API where wrapping is inconsistent for no reason a client could guess.
- A repeated check across 3+ Use Cases in the same feature (e.g. `EnsureBoardIsAccessible`) earns a small injectable service class. A check that's genuinely different each time (Workspace's view/rename/delete rules) does not — don't force those into one generic "canAccess" method.
- After moving any model out of `app/Models`, run `composer dump-autoload` — the optimized classmap can go stale and throw confusing "file not found" errors that look unrelated to the actual change.
- When a "next position" or similar aggregate needs a not-found default, check for `null` explicitly rather than casting through `(int)` — `(int) null` is `0`, which silently produces the wrong answer for "the first item" cases instead of erroring.
- A repeated *access* check across features (not just within one) also composes rather than duplicates: `EnsureCardIsAccessible` calls `EnsureListIsAccessible` calls `EnsureBoardIsAccessible` calls `EnsureWorkspaceIsAccessible` rather than each re-deriving "is this user a workspace member" from scratch.
- Not every relationship needs its own Domain/Application/Infrastructure/Http feature tree. A pivot that's really "this aggregate's own relation" (Card ↔ Label attach/detach, Card ↔ User assign/unassign) belongs as extra methods on the aggregate's existing repository interface, the same way Workspace owns its membership pivot. Reserve a full new feature tree for things that are genuinely their own entity with their own attributes (Label has `name`/`color` and its own CRUD — that part *did* get the full treatment).
- A rule that's simply *narrower* than an existing one (comment delete = author-only, vs. card access = any workspace member) deserves its own exception even when it maps to the same HTTP status — don't reuse an exception just because the status code matches; reuse it when the underlying rule is actually the same.
- A field that a repository method sets as an internal state transition (an `accepted_at`, a `completed_at`) should generally NOT be in `$fillable` — set it directly on the model and `save()` rather than mass-assign it, so it can never be set some other way through arbitrary input. Mass-assignment protection silently discards unlisted fields rather than erroring, so this class of bug produces no exception, no log — just a write that quietly didn't happen. Worth double-checking whenever a repository's `update()`/mass-assignment call targets a field the model didn't originate from `create()`.
- `Resource::additional()` only takes effect through Laravel's `toResponse()` pipeline — it's silently ignored under `response()->json($resource)`, which is what this codebase's "always wrap explicitly" convention uses everywhere. Anything that needs to ride alongside a Resource's normal fields (a one-time token, etc.) has to be merged into a plain array by hand instead.
- Broadcasting events are framework-facing artifacts (implement `ShouldBroadcastNow`/`ShouldBroadcast`, use `Illuminate\Broadcasting\*`), so they live under `Infrastructure/Broadcasting/Events/{Feature}/`, not Laravel's default `App\Events\`. Fired from inside Use Cases with the plain `event()` helper at the end of `execute()`, matching the existing light-Facade-usage precedent (`Hash::make`) rather than introducing a new DI wrapper just for this.
- The `null`/`log` broadcaster drivers (used in tests and un-configured local dev) make `Broadcaster::auth()` a complete no-op — they never invoke a registered `Broadcast::channel()` callback. Testing channel authorization logic against them via the real `/broadcasting/auth` HTTP endpoint proves nothing; retrieve the callback directly via the broadcaster's public `getChannels()` registry and invoke it through `app()->call()` instead.

**Next immediate step:** the React frontend (Vite) — still not scaffolded at all, mentioned as pending since Phase 1. Every phase so far has been a tested backend API with zero UI consuming it. All 6 planned phases are backend-complete; the natural next milestone is standing up the Vite app and wiring Sanctum cookie auth + Echo from the client side, in roughly phase order (login screen first, since Phase 1 already flagged it as the one piece left undone there).

## Phase plan (Phase 0-6 done)

- ✅ Phase 0: DB schema, models, Sanctum foundation
- ✅ Phase 1: Auth — refactored into Clean Architecture, feature-tested. React login screen still not built.
- ✅ Phase 2: Workspace dashboard (workspace CRUD + membership, boards CRUD) — refactored, feature-tested.
- ✅ Phase 3: Kanban board core (lists/cards CRUD, drag-and-drop reorder) — refactored, feature-tested.
- ✅ Phase 4: Card detail modal (checklist items, comments, labels, assignees) — refactored, feature-tested.
- ✅ Phase 5: Invite teammates (token-based, admin-only) — refactored, feature-tested.
- ✅ Phase 6: Real-time sync — backend broadcasting (Pusher-protocol via Laravel Reverb, private board channels, Card events, feature-tested) and frontend Echo wiring both done and live-verified (see `../PROJECT_HANDOFF.md`).

## What to do with this file

Read this alongside the actual codebase. All 6 backend phases from the original plan are done and feature-tested — 40 tests, 177 assertions, all passing (`php artisan test`). The project's remaining major milestone is the React (Vite) frontend, which hasn't been started. When picking that up: scaffold Vite, wire Sanctum's cookie/CSRF dance from the client (the gotchas documented under "Auth" above — Origin/Referer headers, `SANCTUM_STATEFUL_DOMAINS` — apply to the real frontend too, not just tests/Postman), build the login screen first, then work through the phases' UI in order. Keep explaining why each file and step exists so the developer learns clean code while building the project. Write a feature test before calling anything done — this has held for every backend phase and should hold for whatever comes next too.
