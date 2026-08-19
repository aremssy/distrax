<?php

namespace App\Support\Exports\Profiles;

use App\Models\ContactMessage;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ContactMessageExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'contact-messages';
    }

    public function permission(): string
    {
        return 'contact_messages.view';
    }

    public function title(): string
    {
        return 'Contact Messages';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'subject' => 'Subject',
            'message' => 'Message',
            'status' => 'Status',
            'created_at' => 'Received',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors ContactMessageController@index filters.
     */
    public function query(Request $request): Builder
    {
        return ContactMessage::query()
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
            ))
            ->latest();
    }

    /**
     * @param  ContactMessage  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'email' => $row->email,
            'phone' => $row->phone,
            'subject' => $row->subject,
            'message' => $row->message,
            'status' => $row->status,
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
