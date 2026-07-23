/**
 * =====================================================
 * FILE: assets/js/script.js
 * FUNGSI: Interaktivitas frontend - dropzone upload,
 *         dan modal detail riwayat soal.
 * =====================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    // ===================== DROPZONE UPLOAD FILE =====================
    const dropzone = document.getElementById('dropzone');
    const materiInput = document.getElementById('materiInput');
    const fileNameLabel = document.getElementById('fileNameLabel');

    if (dropzone && materiInput) {
        // Klik dropzone -> trigger input file
        dropzone.addEventListener('click', function () {
            materiInput.click();
        });

        // Update label saat file dipilih
        materiInput.addEventListener('change', function () {
            if (materiInput.files.length > 0) {
                const file = materiInput.files[0];
                const sizeKb = (file.size / 1024).toFixed(1);
                fileNameLabel.textContent = file.name + ' (' + sizeKb + ' KB)';
                fileNameLabel.classList.add('fw-semibold', 'text-dark');
            }
        });

        // Drag & drop file ke dropzone
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', function (e) {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                materiInput.files = files;
                const sizeKb = (files[0].size / 1024).toFixed(1);
                fileNameLabel.textContent = files[0].name + ' (' + sizeKb + ' KB)';
                fileNameLabel.classList.add('fw-semibold', 'text-dark');
            }
        });
    }

    // Validasi sederhana sebelum submit form upload
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            if (materiInput.files.length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'File belum dipilih',
                    text: 'Silakan pilih file TXT terlebih dahulu.',
                    confirmButtonColor: '#2C3E50'
                });
                return;
            }

            const ext = materiInput.files[0].name.split('.').pop().toLowerCase();
            if (ext !== 'txt') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Format file salah',
                    text: 'Hanya file berformat .txt yang diperbolehkan.',
                    confirmButtonColor: '#2C3E50'
                });
            }
        });
    }

    // ===================== MODAL DETAIL RIWAYAT =====================
    const jenisLabelMap = {
        'pilihan_ganda': 'Pilihan Ganda',
        'essay': 'Essay',
        'benar_salah': 'Benar / Salah'
    };

    document.querySelectorAll('.view-detail-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            // Ambil data JSON yang dititipkan PHP di atribut data-soal (lihat history.php),
            // lalu ubah dari string JSON menjadi array/object JavaScript biasa.
            const soalData = JSON.parse(this.getAttribute('data-soal'));
            const jenis = this.getAttribute('data-jenis');
            const fileName = this.getAttribute('data-file');

            document.getElementById('modalFileName').textContent = fileName;

            let html = '';
            soalData.forEach(function (q, index) {
                html += '<div class="question-item">';
                html += '<div class="question-number">Soal ' + (index + 1) + '</div>';

                if (q.type === 'pilihan_ganda') {
                    html += '<p class="question-text">' + escapeHtml(q.question) + '</p>';
                    html += '<div class="options-list">';
                    for (const label in q.options) {
                        const isCorrect = label === q.correct_answer;
                        html += '<div class="option-row ' + (isCorrect ? 'correct-option' : '') + '">';
                        html += '<span class="option-label">' + label + '</span>';
                        html += '<span>' + escapeHtml(q.options[label]) + '</span>';
                        if (isCorrect) {
                            html += '<i class="bi bi-check-circle-fill text-success ms-auto"></i>';
                        }
                        html += '</div>';
                    }
                    html += '</div>';
                } else if (q.type === 'essay') {
                    html += '<p class="question-text">' + escapeHtml(q.question) + '</p>';
                    html += '<span class="badge bg-secondary">Soal Essay</span>';
                } else if (q.type === 'benar_salah') {
                    html += '<p class="question-text">' + escapeHtml(q.statement) + '</p>';
                    const badgeClass = q.answer === 'Benar' ? 'bg-success' : 'bg-danger';
                    html += '<span class="badge ' + badgeClass + '">Jawaban: ' + q.answer + '</span>';
                }

                html += '</div>';
            });

            document.getElementById('modalBody').innerHTML = html;

            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();
        });
    });

    // Konfirmasi sebelum hapus riwayat
    document.querySelectorAll('.delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const targetUrl = this.getAttribute('href');

            Swal.fire({
                title: 'Hapus riwayat ini?',
                text: 'Data soal yang sudah dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = targetUrl;
                }
            });
        });
    });

    // Fungsi bantu untuk mencegah XSS saat menampilkan data via JS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
