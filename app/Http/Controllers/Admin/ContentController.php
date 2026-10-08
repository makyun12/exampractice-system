<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\Module;
use App\Models\PracticePackage;
use App\Models\Program;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentController extends Controller
{
    public const MODELS = ['programs' => Program::class, 'modules' => Module::class, 'packages' => PracticePackage::class, 'questions' => Question::class, 'materials' => LearningMaterial::class];

    private function model(string $kind): string
    {
        abort_unless(isset(self::MODELS[$kind]), 404);

        return self::MODELS[$kind];
    }

    private function options(): array
    {
        return ['programs' => Program::orderBy('title')->get(), 'modules' => Module::with('program')->orderBy('title')->get(), 'packages' => PracticePackage::with('module.program')->orderBy('title')->get()];
    }

    public function index(Request $request, string $kind): View
    {
        $model = $this->model($kind);
        $query = $model::query();
        $searchField = $kind === 'questions' ? 'question' : 'title';
        $query->when($request->filled('q'), fn ($q) => $q->where($searchField, 'like', '%'.$request->string('q').'%'))
            ->when(in_array($request->status, ['active', 'draft', 'archived']), fn ($q) => $q->where('status', $request->status));
        $parent = match ($kind) {
            'modules' => 'program_id', 'packages', 'materials' => 'module_id', 'questions' => 'practice_package_id', default => null
        };
        if ($parent && $request->filled('parent')) {
            $query->where($parent, $request->integer('parent'));
        }
        match ($kind) {
            'modules' => $query->with('program'),
            'packages', 'materials' => $query->with('module.program'),
            'questions' => $query->with('package.module'),
            default => $query->withCount('modules'),
        };

        return view('admin.content.index', ['kind' => $kind, 'items' => $query->latest()->paginate(15)->withQueryString()] + $this->options());
    }

    public function create(string $kind): View
    {
        $model = $this->model($kind);

        return view('admin.content.form', ['kind' => $kind, 'item' => new $model] + $this->options());
    }

    public function edit(string $kind, int $id): View
    {
        return view('admin.content.form', ['kind' => $kind, 'item' => $this->model($kind)::findOrFail($id)] + $this->options());
    }

    private function validated(Request $request, string $kind): array
    {
        $rules = ['status' => ['required', Rule::in(['active', 'draft', 'archived'])]];
        if ($kind !== 'questions') {
            $rules['title'] = 'required|string|max:180';
        }
        if (in_array($kind, ['programs', 'modules', 'packages'])) {
            $rules['description'] = 'nullable|string|max:3000';
        }
        $rules += match ($kind) {
            'programs' => ['cover' => 'required|in:japanese,english,workplace'],
            'modules' => ['program_id' => 'required|integer|exists:programs,id'],
            'packages' => ['module_id' => 'required|integer|exists:modules,id', 'duration_minutes' => 'required|integer|min:1|max:240', 'passing_score' => 'required|integer|min:0|max:100', 'show_explanations' => 'required|boolean', 'copy_protection' => 'required|boolean'],
            'questions' => ['practice_package_id' => 'required|integer|exists:practice_packages,id', 'question' => 'required|string|max:10000', 'option_a' => 'required|string|max:2000', 'option_b' => 'required|string|max:2000', 'option_c' => 'required|string|max:2000', 'option_d' => 'required|string|max:2000', 'correct_answer' => 'required|in:A,B,C,D', 'explanation' => 'nullable|string|max:10000'],
            'materials' => ['module_id' => 'required|integer|exists:modules,id', 'content' => 'required|string|max:100000', 'reading_minutes' => 'required|integer|min:1|max:240'],
        };

        return $request->validate($rules);
    }

    public function store(Request $request, string $kind): RedirectResponse
    {
        $model = $this->model($kind);
        $item = $model::create($this->validated($request, $kind));

        return redirect()->route('admin.content.edit', [$kind, $item->id])->with('success', __('ui.saved'));
    }

    public function update(Request $request, string $kind, int $id): RedirectResponse
    {
        $item = $this->model($kind)::findOrFail($id);
        $item->update($this->validated($request, $kind));

        return back()->with('success', __('ui.saved'));
    }

    public function archive(string $kind, int $id): RedirectResponse
    {
        $this->model($kind)::findOrFail($id)->update(['status' => 'archived']);

        return back()->with('success', __('ui.archived_success'));
    }
}
