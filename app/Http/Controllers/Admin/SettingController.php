<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $settings      = Setting::all();
        $arrSettings   = array();

        foreach ($settings as $item) {
            $arrSettings[$item->key] = $item->value;
        }

        return view('admin.setting.index', [
            'arrSettings'     => $arrSettings
        ]);
    }

    public function store(Request $request)
    {
        $inputs = $request->except('_token');
        $arrayKeys = array_keys($inputs);

        try {
            DB::beginTransaction();

            // Chỉ xóa những key thực sự có trong inputs (không xóa bừa)
            if (!empty($arrayKeys)) {
                \App\Models\Setting::whereIn('key', $arrayKeys)->delete();
            }

            foreach ($inputs as $key => $val) {
                // Nếu là field file, chỉ xử lý khi thực sự có file upload
                if (in_array($key, ['logo', 'favicon', 'logo_footer']) && $request->hasFile($key)) {
                    $val = $this->uploadFileSetting($request, $key);
                }

                // Tạo mới setting
                $setting = new \App\Models\Setting();
                $setting->key   = $key;
                $setting->value = $val;
                $setting->save();
            }

            DB::commit();
            return back()->with('success', 'Settings updated successfully!');
        } catch (\Throwable $e) {

            dd($e);
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function uploadFileSetting($request, $key)
    {
        $path  = optional(Setting::where(['key' => $key])->first())->value;
        if (isset($request->$key) && $request->$key != null) {
            $file       = $request->$key;
            $image      = $request->file($key);
            $name       = $key;
            $path       = uploadForSetting($file, $image, $name);
        }

        return $path;
    }

    public function editCustomCss()
    {
        $row = DB::table('settings')->where('key', 'custom_css')->first();
        $customCss = $row->value ?? '';

        return view('admin.setting.css', compact('customCss'));
    }


    public function updateCustomCss(Request $request)
    {
        $request->validate([
            'custom_css' => ['nullable', 'string', 'max:500000'],
        ]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'custom_css'],                 // điều kiện tìm
            ['value' => (string) $request->input('custom_css', '')] // dữ liệu cập nhật/tạo
        );

        return back()->with('success', 'Đã lưu Custom CSS.');
    }
}
