<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceRecord;
use App\Models\LearningMaterial;
use App\Models\Module;
use App\Models\PracticePackage;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $students = User::where('role', 'student')->withCount('attempts')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->q.'%')->orWhere('username', 'like', '%'.$request->q.'%')))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.students.index', compact('students'));
    }

    public function create(): View
    {
        return $this->form(new User(['active' => true, 'locale' => 'en']));
    }

    public function edit(User $student): View
    {
        abort_unless($student->role === 'student', 404);

        return $this->form($student);
    }

    private function form(User $student): View
    {
        return view('admin.students.form', ['student' => $student, 'programs' => Program::with(['modules.packages', 'modules.materials'])->get(), 'grants' => $student->grants->map(fn ($g) => $g->resource_type.':'.$g->resource_id)->all()]);
    }

    private function data(Request $request, ?User $student = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'username' => ['required', 'alpha_dash:ascii', 'min:3', 'max:50', Rule::unique('users')->ignore($student?->id)],
            'password' => [$student ? 'nullable' : 'required', Password::min(10)->letters()->numbers()],
            'active' => 'required|boolean', 'device_lock' => 'required|boolean', 'locale' => 'required|in:en,ja,id',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->data($request);
        $student = User::create($data + ['role' => 'student', 'email' => Str::uuid().'@students.example.invalid']);

        return redirect()->route('admin.students.edit', $student)->with('success', __('ui.student_created'));
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);
        $data = $this->data($request, $student);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        DB::transaction(function () use ($student, $data) {
            $locked = User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            if (isset($data['password']) || (bool) $data['active'] !== $locked->active || (bool) $data['device_lock'] !== $locked->device_lock) {
                $data['session_version'] = $locked->session_version + 1;
            }
            if ((bool) $data['device_lock'] !== $locked->device_lock) {
                $data['device_hash'] = null;
            }
            $locked->update($data);
        });

        return back()->with('success', __('ui.saved'));
    }

    public function access(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);
        $data = $request->validate(['grants' => 'nullable|array|max:2000', 'grants.*' => ['required', 'string', 'regex:/^(program|module|package|material):[0-9]+$/']]);
        $rows = [];
        foreach (array_unique($data['grants'] ?? []) as $grant) {
            [$type, $id] = explode(':', $grant);
            $model = match ($type) {
                'program' => Program::class, 'module' => Module::class, 'package' => PracticePackage::class, 'material' => LearningMaterial::class
            };
            abort_unless($model::whereKey($id)->exists(), 422);
            $rows[] = ['resource_type' => $type, 'resource_id' => (int) $id];
        }
        DB::transaction(function () use ($student, $rows) {
            User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $student->grants()->delete();
            $student->grants()->createMany($rows);
        });

        return back()->with('success', __('ui.access_saved'));
    }

    public function devices(Request $request): View
    {
        $devices = DeviceRecord::with('user')->whereHas('user', fn ($q) => $q->where('role', 'student'))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('username', 'like', '%'.$request->q.'%')->orWhere('name', 'like', '%'.$request->q.'%')))
            ->latest('last_seen_at')->paginate(15)->withQueryString();

        return view('admin.devices', compact('devices'));
    }

    public function resetDevice(User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);
        DB::transaction(function () use ($student) {
            $locked = User::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $locked->update(['device_hash' => null, 'session_version' => $locked->session_version + 1]);
            $locked->devices()->update(['revoked_at' => now()]);
        });

        return back()->with('success', __('ui.device_reset'));
    }
}
