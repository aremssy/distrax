<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEmailTemplateRequest;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        $templates = EmailTemplate::orderBy('channel')->orderBy('name')->get();

        return view('admin.email-templates.index', compact('templates'));
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('admin.email-templates.edit', compact('emailTemplate'));
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active');
        $emailTemplate->update($validated);

        return redirect()->route('admin.email-templates.index')->with('success', 'Template saved.');
    }
}
