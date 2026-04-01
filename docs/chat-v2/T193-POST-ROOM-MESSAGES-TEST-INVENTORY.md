# T193 — Inventory: `POST /api/v1/rooms/{room}/messages`

## Scope

Feature-test inventory for critical branches of `ChatMessageController::store()` after T189/T190 refactor.

Status legend:
- `YES` — branch is covered by at least one feature test.
- `NO` — branch is currently not covered.

## Branch Coverage Matrix

| Branch | Existing test(s) | Status |
|---|---|---|
| Duplicate `client_message_id` in the same room returns existing message (`meta.duplicate=true`) | `ChatApiTest::test_post_public_message_with_duplicate_client_id_returns_existing_message`, `ChatApiTest::test_post_message_slash_me_and_idempotent_duplicate` | YES |
| Duplicate `client_message_id` used in another room returns HTTP 422 | `ChatApiTest::test_same_client_id_in_different_room_returns_422` | YES |
| Inline private `/msg` success path | `ChatApiTest::test_post_msg_inline_private_dispatches_user_channel_broadcast_not_room` | YES |
| Inline private `/msg` errors: unknown peer | `ChatApiTest::test_post_msg_unknown_peer_returns_422` | YES |
| Inline private `/msg` errors: blocked by private-message idempotency conflict | `ChatApiTest::test_inline_msg_rejects_client_id_already_used_by_private_api` | YES |
| Public message automoderation reject path | `StopWordAutomoderationTest::test_reject_action_blocks_public_message` | YES |
| Client-only slash unknown command path | `ChatApiTest::test_slash_unknown_command_is_client_only_and_hidden_from_others` | YES |

## Result

Critical branches listed in T193 are covered (`NO` gaps not detected for this checklist slice).
