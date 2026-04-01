<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Models\Tag;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends AdminTagController
{
    public function index(Request $request): Response
    {
        $tags = Tag::withCount('posts')
            ->when($request->search, function ($query, $search) {
                $query->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Webmaster/Tags/Index', [
            'tags' => $tags,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Webmaster/Tags/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ]);

        $data['slug'] = Str::slug($data['name_en']);

        $tag = Tag::create($data);

        AuditLogger::log('created', $tag);

        return redirect()->route('webmaster.tags.index')->with('success', 'Tag created successfully.');
    }

    public function edit(Tag $tag): Response
    {
        return Inertia::render('Webmaster/Tags/Edit', [
            'tag' => $tag,
        ]);
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ]);

        $data['slug'] = Str::slug($data['name_en']);

        $tag->update($data);

        AuditLogger::log('updated', $tag);

        return redirect()->route('webmaster.tags.index')->with('success', 'Tag updated successfully.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        AuditLogger::log('deleted', $tag);
        $tag->delete();

        return redirect()->route('webmaster.tags.index')->with('success', 'Tag deleted successfully.');
    }
}
