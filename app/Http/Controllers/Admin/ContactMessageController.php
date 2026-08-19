<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContactMessageStatusRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $messages = ContactMessage::query()
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.contact-messages.index', compact('messages'));
    }

    public function updateStatus(UpdateContactMessageStatusRequest $request, ContactMessage $contactMessage): RedirectResponse
    {
        $data = $request->validated();

        $contactMessage->update($data);

        return redirect()->route('admin.contact-messages.index')->with('success', 'Message updated.');
    }
}
