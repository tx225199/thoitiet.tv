<?php

namespace App\Listeners;

use App\Models\Adv;
use Illuminate\Support\Facades\File;

class GeneratePopunderJsListener
{
    public function __construct() {}

    public function handle($event)
    {
        $this->generatePopunderJs();
    }

    private function generatePopunderJs(): void
    {
        // Lấy 1 bản ghi popunder đang bật
        $pop = Adv::where('type', 'LIKE', '%popunder%')
            ->where('status', 1)
            ->latest('updated_at')
            ->first();

        // Nếu không có → ghi file tắt chức năng (an toàn)
        if (!$pop) {
            $this->writeFile($this->fallbackDisabled());
            return;
        }

        // Parse config
        $cfg = $pop->script ? (json_decode($pop->script, true) ?: []) : [];
        $firstDelayMs = (int) (($cfg['first_delay'] ?? 10) * 1000);
        $cooldownMs   = (int) (($cfg['cooldown']    ?? 30) * 1000);
        $maxTimes     = (int) ($cfg['max_times']    ?? 3);
        $ttlSeconds   = (int) ($cfg['ttl_seconds']  ?? 3600); // nếu chưa có, mặc định 1h

        // Danh sách URL (mỗi dòng một link)
        $urls = [];
        if (!empty($pop->link)) {
            $lines = preg_split('/\r\n|\n|\r/', $pop->link);
            $urls  = array_values(array_filter(array_map('trim', $lines)));
        }

        if (empty($urls)) {
            $this->writeFile($this->fallbackDisabled());
            return;
        }

        // Build nội dung file JS — “bake” thẳng config & URL từ DB
        $jsonUrls = json_encode($urls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $js = <<<JS
// === Auto-generated: DO NOT EDIT MANUALLY ===
(function(){
  // ==== CONFIG (from DB) ==== //
  var POPUNDER_URLS  = {$jsonUrls};
  var FIRST_DELAY_MS = {$firstDelayMs};
  var COOLDOWN_MS    = {$cooldownMs};
  var COOKIE_NAME    = "pu_meta";
  var COOKIE_TTL_S   = {$ttlSeconds};
  var MAX_TIMES_CONF = {$maxTimes};

  // ==== Cookie helpers ==== //
  function setCookie(name, value, ttlSeconds) {
    try {
      var d = new Date();
      d.setTime(d.getTime() + ttlSeconds * 1000);
      document.cookie = name + "=" + encodeURIComponent(value)
        + ";expires=" + d.toUTCString() + ";path=/;SameSite=Lax";
    } catch(e){}
  }
  function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i].trim();
      if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length));
    }
    return null;
  }

  // ==== Utils ==== //
  function pick(arr){ return arr[Math.floor(Math.random()*arr.length)]; }
  function saveMeta(meta){ setCookie(COOKIE_NAME, JSON.stringify(meta), COOKIE_TTL_S); }
  function loadMeta(){ try { return JSON.parse(getCookie(COOKIE_NAME) || "null"); } catch(e){ return null; } }

  // ==== Init per-user ==== //
  var meta = loadMeta();
  var now  = Date.now();
  if (!meta) {
    meta = { max: (MAX_TIMES_CONF || (Math.random() < 0.5 ? 2 : 3)), count:0, last:0, readyAt: now + FIRST_DELAY_MS };
    saveMeta(meta);
  } else {
    meta.max    = Number(meta.max)    || (MAX_TIMES_CONF || (Math.random() < 0.5 ? 2 : 3));
    meta.count  = Number(meta.count)  || 0;
    meta.last   = Number(meta.last)   || 0;
    meta.readyAt= Number(meta.readyAt)|| (now + FIRST_DELAY_MS);
    saveMeta(meta);
  }

  function tryPopUnder(){
    var t = Date.now();
    if (meta.count >= meta.max) return;
    if (t < meta.readyAt) return;
    if (t - meta.last < COOLDOWN_MS) return;

    var url = pick(POPUNDER_URLS);
    var w = window.open(url, "_blank", "noopener");
    meta.count += 1;
    meta.last   = t;
    meta.readyAt= t + COOLDOWN_MS;
    saveMeta(meta);
    try { if (w) { w.blur(); window.focus(); setTimeout(function(){ window.focus(); }, 50); } } catch(e){}
  }

  // jQuery optional — dùng thuần DOM để khỏi phụ thuộc
  document.addEventListener("click", tryPopUnder, true);
})();
JS;

        $this->writeFile($js);
    }

    private function fallbackDisabled(): string
    {
        return <<<JS
// === Auto-generated (disabled) ===
(function(){
  // popunder disabled (no active config)
})();
JS;
    }

    private function writeFile(string $content): void
    {
        $folder = public_path('assets/adv');
        if (!File::isDirectory($folder)) {
            File::makeDirectory($folder, 0755, true, true);
        }
        File::put($folder . '/popunder.js', $content);
    }
}
