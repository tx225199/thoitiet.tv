<?php

namespace App\Http\Controllers\Admin;

use App\Events\MakeAdvsEvent;
use App\Http\Controllers\Controller;
use App\Models\Adv;
use App\Models\AdvType;
use Illuminate\Http\Request;

class AdvController extends Controller
{
    /**
     * Index function
     *
     * @return void
     */
    public function index()
    {
        $data =  Adv::orderBy('created_at', 'asc')->get();
        $advTypes = AdvType::where(['status' => 1])->get();

        return view('admin.adv.index', [
            'data'        => $data,
            'advTypes'    => $advTypes
        ]);
    }

    /**
     * Banner function
     *
     * @return void
     */
    public function banner()
    {
        $data =  Adv::where('type', 'LIKE', '%banner%')->orderBy('created_at', 'asc')->get();
        $advTypes = AdvType::where(['status' => 1])->whereIn('slug', ['banner'])->get();

        return view('admin.adv.banner', [
            'data'        => $data,
            'advTypes'    => $advTypes
        ]);
    }

    /**
     * Banner script function
     *
     * @return void
     */
    public function bannerScript()
    {
        $data =  Adv::where('type', 'LIKE', '%banner-script%')->orderBy('created_at', 'asc')->get();
        $advTypes = AdvType::where(['status' => 1])->whereIn('slug', ['banner-script'])->get();

        return view('admin.adv.banner-script', [
            'data'        => $data,
            'advTypes'    => $advTypes
        ]);
    }

    /**
     * Catfish function
     *
     * @return void
     */
    public function catfish()
    {
        $data =  Adv::where('type', 'LIKE', '%catfish%')->orderBy('created_at', 'asc')->get();
        $advTypes = AdvType::where(['status' => 1])->whereIn('slug', ['catfish'])->get();

        return view('admin.adv.catfish', [
            'data'        => $data,
            'advTypes'    => $advTypes
        ]);
    }

    /**
     * Preload function
     *
     * @return void
     */
    public function preload()
    {
        $data =  Adv::where('type', 'LIKE', '%preload%')->orderBy('created_at', 'asc')->get();
        $advTypes = AdvType::where(['status' => 1])->whereIn('slug', ['preload'])->get();

        return view('admin.adv.preload', [
            'data'        => $data,
            'advTypes'    => $advTypes
        ]);
    }

    /**
     * Push js function
     *
     * @return void
     */
    public function pushJs()
    {
        $data =  Adv::where('type', 'LIKE', '%push-js%')->orderBy('created_at', 'asc')->get();
        $advTypes = AdvType::where(['status' => 1])->whereIn('slug', ['push-js'])->get();

        return view('admin.adv.pushjs', [
            'data'        => $data,
            'advTypes'    => $advTypes
        ]);
    }


    /**
     * Popup js function
     *
     * @return void
     */
    public function popupJs()
    {
        $data =  Adv::where('type', 'LIKE', '%popup-js%')->orderBy('created_at', 'asc')->get();
        $advTypes = AdvType::where(['status' => 1])->whereIn('slug', ['popup-js'])->get();

        return view('admin.adv.popupjs', [
            'data'        => $data,
            'advTypes'    => $advTypes
        ]);
    }

    /**
     * textLink js function
     *
     * @return void
     */
    public function textLink()
    {
        $data =  Adv::where('type', 'LIKE', '%textlink%')->orderBy('created_at', 'asc')->get();
        $advTypes = AdvType::where(['status' => 1])->whereIn('slug', ['textlink'])->get();

        return view('admin.adv.textlink', [
            'data'        => $data,
            'advTypes'    => $advTypes
        ]);
    }

    /**
     * header js function
     *
     * @return void
     */
    public function header()
    {
        $data =  Adv::where('type', 'LIKE', '%header%')->orderBy('created_at', 'asc')->get();
        $advTypes = AdvType::where(['status' => 1])->whereIn('slug', ['header'])->get();

        return view('admin.adv.header', [
            'data'        => $data,
            'advTypes'    => $advTypes
        ]);
    }


    /**
     * bottom js function
     *
     * @return void
     */
    public function bottom()
    {
        $data =  Adv::where('type', 'LIKE', '%bottom%')->orderBy('created_at', 'asc')->get();
        $advTypes = AdvType::where(['status' => 1])->whereIn('slug', ['bottom'])->get();

        return view('admin.adv.bottom', [
            'data'        => $data,
            'advTypes'    => $advTypes
        ]);
    }

    public function popunder()
    {
        $item = Adv::where('type', 'popunder')->latest('updated_at')->first();

        $advTypes = AdvType::where('status', 1)
            ->whereIn('slug', ['popunder'])
            ->get();

        return view('admin.adv.popunder', [
            'data'      => $item,
            'advTypes'  => $advTypes
        ]);
    }


    public function refresh()
    {
        event(new MakeAdvsEvent());
        return true;
    }

    public function active(Request $request)
    {
        $adv = Adv::where(['id' => $request->id])->first();

        if (!isset($adv)) {
            return response()->json(['error' => true, 'message' => 'Advertisement does not exist']);
        }

        $adv->status = $request->status == "true" ? 1 : 0;
        $adv->save();

        event(new MakeAdvsEvent());

        return response()->json(['error' => false, 'message' => 'Updated successfully']);
    }

    public function delete(Request $request)
    {
        $adv = Adv::where(['id' => $request->id])->first();

        if (!isset($adv)) {
            return response()->json(['error' => true, 'message' => 'Advertisement does not exist']);
        }

        $adv->delete();

        event(new MakeAdvsEvent());

        return response()->json(['error' => false, 'message' => 'Deleted successfully']);
    }

    public function store(Request $request)
    {
        $item = Adv::findOrNew($request->id);

        $currentDesMedia = $item->des_media;
        $currentMobMedia = $item->mob_media;

        $item->title = $request->title;

        if (!empty($request->type)) {
            $item->type = implode(', ', (array)$request->type);
        }

        if (!empty($request->position)) {
            $item->position = implode(', ', (array)$request->position);
        }

        $item->link       = $request->link ?? null;
        $item->other_link = $request->other_link ?? null;
        $item->supplier   = $request->supplier ?? null;
        $item->status     = $request->status ?? 1;
        $item->sort       = $request->sort ?? (Adv::max('sort') + 1);

        // --- Merge script JSON ---
        $oldScript = [];
        if (!empty($item->script)) {
            $decoded = json_decode($item->script, true);
            if (is_array($decoded)) {
                $oldScript = $decoded;
            }
        }

        $newScript = $request->input('script', []);
        if (isset($newScript['preload_auto_redirect'])) {
            $newScript['preload_auto_redirect'] = (int) !!$newScript['preload_auto_redirect'];
        }

        $mergedScript = array_merge($oldScript, $newScript);
        $item->script = !empty($mergedScript) ? json_encode($mergedScript, JSON_UNESCAPED_UNICODE) : null;

        // --- Upload media ---
        if ($request->hasFile('des_media')) {
            $desMediaFile = $request->file('des_media');
            $desMediaPath = uploadFileAdv($desMediaFile, makeSlug($request->supplier) . '-des', 'uploads/advs');
            $item->des_media = $desMediaPath;
        } else {
            $item->des_media = $currentDesMedia;
        }

        if ($request->hasFile('mob_media')) {
            $mobMediaFile = $request->file('mob_media');
            $mobMediaPath = uploadFileAdv($mobMediaFile, makeSlug($request->supplier) . '-mob', 'uploads/advs');
            $item->mob_media = $mobMediaPath;
        } else {
            $item->mob_media = $currentMobMedia;
        }

        $item->save();

        event(new MakeAdvsEvent());

        return redirect()->back()->with('success', 'Lưu quảng cáo thành công!');
    }


    public function storePopunder(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'status'      => 'nullable|in:0,1',
            'first_delay' => 'nullable|integer|min:0',
            'cooldown'    => 'nullable|integer|min:0',
            'max_times'   => 'nullable|integer|min:1',
            'links'       => 'required|string',
            'id'          => 'nullable|integer', // nếu có => update
        ]);

        // Chuẩn hóa link (mỗi dòng 1 URL)
        $lines = preg_split('/\r\n|\n|\r/', (string) $request->input('links', ''));
        $links = array_values(array_unique(array_filter(array_map('trim', $lines))));
        if (empty($links)) {
            return back()->withErrors(['links' => 'Vui lòng nhập ít nhất 1 URL'])->withInput();
        }

        // Cấu hình lưu JSON trong script
        $config = [
            'first_delay' => (int) $request->input('first_delay', 10),
            'cooldown'    => (int) $request->input('cooldown', 30),
            'max_times'   => (int) $request->input('max_times', 3),
        ];

        // Nếu gửi id => update, ngược lại dùng bản ghi popunder đầu tiên (nếu có) để tránh tạo trùng
        $item = null;
        if ($request->filled('id')) {
            $item = Adv::find($request->input('id'));
        }
        if (!$item) {
            $item = Adv::where('type', 'LIKE', '%popunder%')->first() ?? new Adv();
        }

        $item->title      = $request->input('title');
        $item->type       = 'popunder';
        $item->position   = 'global';
        $item->link       = implode("\n", $links);
        $item->other_link = null;
        $item->script     = json_encode($config, JSON_UNESCAPED_UNICODE);
        $item->supplier   = null;
        $item->status     = (int) $request->input('status', 1);

        // sort: nếu bản ghi mới -> gán max+1, nếu cũ -> giữ nguyên
        if (!$item->exists) {
            $item->sort = (Adv::max('sort') ?? 0) + 1;
        }

        // Không dùng media cho popunder
        $item->des_media = null;
        $item->mob_media = null;

        $item->save();

        event(new MakeAdvsEvent());
        return redirect()->route('admin.adv.popunder')->with('success', 'Đã lưu Pop-under thành công!');
    }
}
