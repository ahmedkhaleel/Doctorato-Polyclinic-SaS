<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModuleSettingController extends Controller
{
    public function index()
    {
        $modules = [];
        foreach (ModuleManager::getAllModules() as $slug => $module) {
            $info = ModuleManager::getModuleInfo($slug);
            $settings = ModuleManager::getSettings($slug);
            $modules[] = array_merge($info, [
                'settings' => $settings,
                // Drives the Medical vs Administrative split in the UI.
                // Derived from MEDICAL_MODULES so new specialties never drift
                // into the administrative bucket.
                'is_medical' => in_array($slug, ModuleManager::MEDICAL_MODULES, true),
            ]);
        }

        return Inertia::render('Admin/Settings/Modules', [
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, string $module)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string|max:500',
        ]);

        ModuleManager::updateSettings($module, $data['settings']);

        AuditLogger::log('updated', null, $data['settings'], "Updated settings for module: {$module}");

        return redirect()->back()->with('success', 'تم تحديث إعدادات القسم بنجاح');
    }

    public function toggle(Request $request, string $module)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        if ($request->enabled) {
            ModuleManager::enable($module);
            AuditLogger::log('enabled', null, ['module' => $module], "Enabled module: {$module}");
        } else {
            $result = ModuleManager::disable($module);
            if (! $result) {
                return redirect()->back()->with('error', 'لا يمكن تعطيل آخر تخصص طبي فعّال — يجب أن يبقى تخصص واحد على الأقل');
            }
            AuditLogger::log('disabled', null, ['module' => $module], "Disabled module: {$module}");
        }

        return redirect()->back()->with('success', $request->enabled ? 'تم تفعيل القسم بنجاح' : 'تم تعطيل القسم بنجاح');
    }

    public function moduleSettings(string $module)
    {
        $info = ModuleManager::getModuleInfo($module);
        $settings = ModuleManager::getSettings($module);

        return Inertia::render('Admin/Settings/ModuleDetail', [
            'module' => array_merge($info, ['settings' => $settings]),
        ]);
    }
}
