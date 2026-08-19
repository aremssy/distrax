<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Events\ConversationStarted;
use App\Events\MessageSent;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Chat\SendMessageRequest;
use App\Http\Requests\Api\V1\Chat\StartConversationRequest;
use App\Http\Resources\Api\V1\ConversationResource;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Block;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\PropertyListing;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends ApiController
{
    /**
     * GET /api/v1/conversations
     *
     * All conversations for the authenticated user (as starter or recipient),
     * ordered by latest activity. Includes per-conversation unread counts.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $conversations = Conversation::query()
            ->where(fn ($q) => $q
                ->where('starter_id', $userId)
                ->orWhere('recipient_id', $userId)
            )
            ->with(['listing', 'starter', 'recipient', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at'),
            ])
            ->orderByDesc('last_message_at')
            ->get();

        // Filter out conversations where either party has blocked the other
        $blockedIds = $this->blockedUserIds($userId);

        $conversations = $conversations->filter(fn ($conv) => ! (
            in_array($conv->starter_id, $blockedIds, true)
            || in_array($conv->recipient_id, $blockedIds, true)
        ));

        return $this->success(ConversationResource::collection($conversations));
    }

    /**
     * POST /api/v1/conversations
     *
     * Start a conversation about a listing. Returns the existing conversation
     * if one already exists between this pair for this listing.
     * The opening message is sent in the same request.
     */
    public function store(StartConversationRequest $request): JsonResponse
    {
        $user = $request->user();
        $listing = PropertyListing::findOrFail($request->integer('listing_id'));

        if ($listing->owner_id === $user->id) {
            return $this->error('You cannot start a conversation about your own listing.', 422);
        }

        $recipientId = $listing->owner_id;

        $this->guardBlocked($user->id, $recipientId);

        // Re-use an existing conversation rather than creating duplicates
        $conversation = Conversation::withTrashed()
            ->where('property_listing_id', $listing->id)
            ->where('starter_id', $user->id)
            ->where('recipient_id', $recipientId)
            ->first();

        $isNew = false;

        if ($conversation) {
            // Restore soft-deleted conversation if user re-initiates
            if ($conversation->trashed()) {
                $conversation->restore();
            }
        } else {
            $conversation = Conversation::create([
                'property_listing_id' => $listing->id,
                'starter_id' => $user->id,
                'recipient_id' => $recipientId,
                'last_message_at' => now(),
            ]);
            $isNew = true;
        }

        // Send the opening message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $request->input('message'),
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        broadcast(new MessageSent($message))->toOthers();

        if ($isNew) {
            broadcast(new ConversationStarted($conversation))->toOthers();
        }

        $conversation->load(['listing', 'starter', 'recipient', 'latestMessage']);

        return $this->created(new ConversationResource($conversation), 'Conversation started.');
    }

    /**
     * GET /api/v1/conversations/{conversation}/messages
     *
     * Paginated messages (newest first). Marks all incoming unread messages
     * as read as a side effect of loading.
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $userId = $request->user()->id;
        $this->assertParticipant($conversation, $userId);

        // Mark all messages FROM the other party as read (bulk)
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $perPage = min((int) $request->input('per_page', 20), 50);

        $paginator = $conversation->messages()
            ->with('sender')
            ->latest()
            ->paginate($perPage);

        return $this->paginated($paginator, MessageResource::class);
    }

    /**
     * POST /api/v1/conversations/{conversation}/messages
     *
     * Send a message. Broadcasts to the private channel immediately.
     */
    public function sendMessage(
        SendMessageRequest $request,
        Conversation $conversation,
        NotificationDispatcher $dispatcher,
    ): JsonResponse {
        $user = $request->user();
        $this->assertParticipant($conversation, $user->id);

        $otherId = $conversation->starter_id === $user->id
            ? $conversation->recipient_id
            : $conversation->starter_id;

        $this->guardBlocked($user->id, $otherId);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $request->input('message'),
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        broadcast(new MessageSent($message->load('sender')))->toOthers();

        // In-app + push notification to the other participant
        $recipient = User::find($otherId);

        if ($recipient) {
            $dispatcher->send(
                user: $recipient,
                type: 'new_message',
                title: 'New message from '.$user->name,
                body: mb_strimwidth($request->input('message'), 0, 120, '…'),
                data: ['conversation_id' => $conversation->id],
                notifiable: $message,
            );
        }

        return $this->created(new MessageResource($message->load('sender')), 'Message sent.');
    }

    /**
     * POST /api/v1/conversations/{conversation}/read
     *
     * Explicitly mark all incoming messages as read (useful for cross-device sync).
     */
    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $userId = $request->user()->id;
        $this->assertParticipant($conversation, $userId);

        $updated = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(['marked_read' => $updated]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function assertParticipant(Conversation $conversation, int $userId): void
    {
        if (! in_array($userId, [$conversation->starter_id, $conversation->recipient_id], true)) {
            abort(403, 'You are not a participant in this conversation.');
        }
    }

    /**
     * Abort with 403 if either user has blocked the other.
     */
    private function guardBlocked(int $userA, int $userB): void
    {
        $blocked = Block::where(fn ($q) => $q
            ->where('blocker_id', $userA)->where('blocked_id', $userB)
        )->orWhere(fn ($q) => $q
            ->where('blocker_id', $userB)->where('blocked_id', $userA)
        )->exists();

        if ($blocked) {
            abort(403, 'You cannot message this user.');
        }
    }

    /**
     * Return all user IDs that $userId has blocked OR that have blocked $userId.
     *
     * @return list<int>
     */
    private function blockedUserIds(int $userId): array
    {
        $blockerIds = Block::where('blocked_id', $userId)->pluck('blocker_id')->toArray();
        $blockedIds = Block::where('blocker_id', $userId)->pluck('blocked_id')->toArray();

        return array_values(array_unique(array_merge($blockerIds, $blockedIds)));
    }
}
