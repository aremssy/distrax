<?php

namespace App\Support\Exports\Profiles;

use App\Models\EmailTemplate;
use App\Support\Exports\AbstractExportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EmailTemplateExportProfile extends AbstractExportProfile
{
    public function key(): string
    {
        return 'email-templates';
    }

    public function permission(): string
    {
        return 'settings.view';
    }

    public function title(): string
    {
        return 'Email & SMS Templates';
    }

    public function columns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'key' => 'Key',
            'channel' => 'Channel',
            'subject' => 'Subject',
            'is_active' => 'Active',
            'created_at' => 'Created',
        ];
    }

    /**
     * Mirrors EmailTemplateController@index (no request filters).
     */
    public function query(Request $request): Builder
    {
        return EmailTemplate::orderBy('channel')->orderBy('name');
    }

    /**
     * @param  EmailTemplate  $row
     */
    public function map(Model $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'key' => $row->key,
            'channel' => $row->channel,
            'subject' => $row->subject,
            'is_active' => $row->is_active ? 'Yes' : 'No',
            'created_at' => $row->created_at?->toDateTimeString(),
        ];
    }
}
