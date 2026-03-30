<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('departments', 'view'), 403);

        $departments = Department::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('admin.departments.index', compact('departments'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('departments', 'create'), 403);

        return view('admin.departments.create', [
            'screens' => PermissionsController::$screens,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('departments', 'create'), 403);

        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:departments,name'],
        ]);

        Department::create([
            'name'        => $request->name,
            'permissions' => $this->buildPermissions($request),
        ]);

        return redirect()
            ->route('admin.departments.index')
            ->with('success', '"' . $request->name . '" departmanı oluşturuldu.');
    }

    public function edit(Department $department): View
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('departments', 'edit'), 403);

        $department->load('users');

        return view('admin.departments.edit', [
            'department' => $department,
            'screens'    => PermissionsController::$screens,
        ]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('departments', 'edit'), 403);

        $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($department)],
        ]);

        $department->update([
            'name'        => $request->name,
            'permissions' => $this->buildPermissions($request),
        ]);

        return redirect()
            ->route('admin.departments.index')
            ->with('success', '"' . $department->name . '" departmanı güncellendi.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->hasPermission('departments', 'delete'), 403);

        $name = $department->name;
        $department->users()->update(['department_id' => null]);
        $department->delete();

        return redirect()
            ->route('admin.departments.index')
            ->with('success', '"' . $name . '" departmanı silindi.');
    }

    private function buildPermissions(Request $request): array
    {
        $built = [];
        foreach (PermissionsController::$screens as $screenKey => $screen) {
            foreach ($screen['actions'] as $actionKey => $actionLabel) {
                $built[$screenKey][$actionKey] = $request->boolean("permissions.{$screenKey}.{$actionKey}");
            }
        }
        return $built;
    }
}
