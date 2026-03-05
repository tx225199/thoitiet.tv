<h2>Chạy Artisan Command</h2>
<form id="cmd-form">
    <input type="text" name="command" placeholder="vd: migrate, queue:work, crawl:run ..." style="width: 300px">
    <button type="submit">Chạy</button>
</form>
<pre id="result"></pre>

<script>
document.getElementById('cmd-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);

    const res = await fetch('/admin/artisan', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: data
    });

    const result = await res.json();
    document.getElementById('result').innerText = result.output || result.error;
});
</script>
