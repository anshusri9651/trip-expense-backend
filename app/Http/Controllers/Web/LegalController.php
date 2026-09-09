<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function show(string $slug): View
    {
        abort_unless(in_array($slug, ['privacy-policy', 'terms'], true), 404);
        $document = LegalDocument::firstOrCreate(['slug' => $slug], [
            'title' => $slug === 'terms' ? 'Terms and Conditions' : 'Privacy Policy',
            'content' => '<h2>Welcome</h2><p>This document is being prepared. Please check back soon.</p>',
        ]);
        return view('legal.show', compact('document'));
    }

    public function edit(Request $request, string $slug): View
    {
        $this->ensureAdmin($request); abort_unless(in_array($slug, ['privacy-policy', 'terms'], true), 404);
        $document = LegalDocument::firstOrCreate(['slug' => $slug], ['title' => $slug === 'terms' ? 'Terms and Conditions' : 'Privacy Policy', 'content' => '<p>Start writing here...</p>']);
        return view('legal.edit', compact('document'));
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $this->ensureAdmin($request); abort_unless(in_array($slug, ['privacy-policy', 'terms'], true), 404);
        $data = $request->validate(['title' => ['required', 'string', 'max:160'], 'content' => ['required', 'string', 'max:100000']]);
        $safeTags = '<p><br><strong><b><em><i><u><h2><h3><h4><ul><ol><li><blockquote><a><hr>';
        $content = strip_tags($data['content'], $safeTags);
        $content = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $content) ?? $content;
        $content = preg_replace('/javascript\s*:/i', '', $content) ?? $content;
        LegalDocument::updateOrCreate(['slug' => $slug], ['title' => $data['title'], 'content' => $content, 'updated_by' => $request->user()->id]);
        return back()->with('status', 'Document saved successfully.');
    }

    private function ensureAdmin(Request $request): void { abort_unless($request->user()?->is_admin, 403); }
}
