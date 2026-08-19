<?php

namespace App\Http\Controllers\Website;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Chat\SendMessageRequest;
use App\Models\Block;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index(Request $request, ?Conversation $conversation = null): View
    {
        $userId = $request->user()->id;
        $conversations = Conversation::query()
            ->where(fn (Builder $query) => $query->where('starter_id', $userId)->orWhere('recipient_id', $userId))
            ->with(['listing:id,slug,title,status', 'starter:id,name,avatar', 'recipient:id,name,avatar', 'latestMessage.attachments'])
            ->withCount(['messages as unread_count' => fn (Builder $query) => $query->where('sender_id', '!=', $userId)->whereNull('read_at')])
            ->orderByDesc('last_message_at')
            ->paginate(30)
            ->withQueryString();

        $activeConversation = $conversation ?? $conversations->first();
        $messages = collect();
        $isOtherBlocked = false;
        $existingBlock = null;

        if ($activeConversation) {
            $this->assertParticipant($activeConversation, $userId);
            Message::query()->whereBelongsTo($activeConversation)->where('sender_id', '!=', $userId)->whereNull('read_at')->update(['read_at' => now()]);
            $messages = $activeConversation->messages()->with(['sender:id,name,avatar', 'attachments'])->latest()->limit(50)->get()->reverse()->values();
            $activeConversation->load(['listing:id,slug,title,status', 'starter:id,name,avatar,phone,phone_visibility', 'recipient:id,name,avatar,phone,phone_visibility']);

            $otherId = $activeConversation->starter_id === $userId ? $activeConversation->recipient_id : $activeConversation->starter_id;
            $existingBlock = Block::where('blocker_id', $userId)->where('blocked_id', $otherId)->first();
            $isOtherBlocked = (bool) $existingBlock;
        }

        return view('website.dashboard.messages', compact('conversations', 'activeConversation', 'messages', 'isOtherBlocked', 'existingBlock'));
    }

    public function store(SendMessageRequest $request, Conversation $conversation): RedirectResponse
    {
        $this->assertParticipant($conversation, $request->user()->id);
        $otherUserId = $conversation->starter_id === $request->user()->id ? $conversation->recipient_id : $conversation->starter_id;
        abort_if(Block::query()->where(fn (Builder $query) => $query
            ->where(['blocker_id' => $request->user()->id, 'blocked_id' => $otherUserId])
            ->orWhere(['blocker_id' => $otherUserId, 'blocked_id' => $request->user()->id]))->exists(), 403);

        $message = DB::transaction(function () use ($request, $conversation) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $request->user()->id,
                'body' => $request->validated('message') ?? '',
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store('message-attachments/'.$conversation->id, 'public');
                $message->attachments()->create([
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            $conversation->update(['last_message_at' => $message->created_at]);

            return $message;
        });

        MessageSent::dispatch($message);

        return redirect()->route('dashboard.messages.show', $conversation);
    }

    private function assertParticipant(Conversation $conversation, int $userId): void
    {
        abort_unless(in_array($userId, [$conversation->starter_id, $conversation->recipient_id], true), 403);
    }
}
