<?php

namespace App\Support\Exports\Profiles;

use App\Models\Dispute;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DisputeExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'disputes';
    }

    public function permission(): string
    {
        return 'reports.view';
    }

    public function title(): string
    {
        return 'Disputes';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'subject' => 'Subject',
            'raised_by' => 'Raised By',
            'against_user' => 'Against',
            'assigned_to' => 'Assigned To',
            'status' => 'Status',
            'outcome' => 'Outcome',
            'resolved_at' => 'Resolved',
            'created_at' => 'Created',
        ];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * Mirrors DisputeController@index filters.
     */
    public function query(Request $request): Builder
    {
        return Dispute::with(['raisedBy:id,name,email', 'againstUser:id,name,email', 'assignedTo:id,name,email', 'payment', 'refund'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where(
                fn ($q) => $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('raisedBy', fn ($r) => $r->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ))
            ->latest();
    }

    /**
     * @param  Dispute  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'subject' => $row->subject,
            'raised_by' => $row->raisedBy?->name,
            'against_user' => $row->againstUser?->name,
            'assigned_to' => $row->assignedTo?->name,
            'status' => $row->status,
            'outcome' => $row->outcome,
            'resolved_at' => $row->resolved_at?->toDateTimeString(),
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
