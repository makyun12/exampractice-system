<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use App\Models\Program;
use App\Services\LearningAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request, LearningAccess $access): View
    {
        $packages = $access->packages($request->user());
        $materials = $access->materials($request->user());
        $programs = $packages->map(fn ($p) => $p->module->program)->merge($materials->map(fn ($m) => $m->module->program))->unique('id');

        return view('student.programs', compact('programs', 'packages', 'materials'));
    }

    public function program(Request $request, Program $program, LearningAccess $access): View
    {
        $packages = $access->packages($request->user())->filter(fn ($p) => $p->module->program_id === $program->id);
        $materials = $access->materials($request->user())->filter(fn ($m) => $m->module->program_id === $program->id);
        abort_if($packages->isEmpty() && $materials->isEmpty(), 404);
        $modules = $packages->pluck('module')->merge($materials->pluck('module'))->unique('id');

        return view('student.program', compact('program', 'modules', 'packages', 'materials'));
    }

    public function materials(Request $request, LearningAccess $access): View
    {
        $materials = $access->materials($request->user());
        $completed = DB::table('material_completions')->where('user_id', $request->user()->id)->pluck('learning_material_id');

        return view('student.materials', compact('materials', 'completed'));
    }

    public function material(Request $request, LearningMaterial $material, LearningAccess $access): View
    {
        abort_unless($access->material($request->user(), $material), 404);
        $completed = DB::table('material_completions')->where('user_id', $request->user()->id)->where('learning_material_id', $material->id)->exists();

        return view('student.material', compact('material', 'completed'));
    }

    public function complete(Request $request, LearningMaterial $material, LearningAccess $access): RedirectResponse
    {
        abort_unless($access->material($request->user(), $material), 404);
        DB::table('material_completions')->updateOrInsert(['user_id' => $request->user()->id, 'learning_material_id' => $material->id], ['completed_at' => now()]);

        return back()->with('success', __('ui.material_completed'));
    }
}
