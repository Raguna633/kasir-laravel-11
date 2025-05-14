@extends('layouts.master')

@section('title')
    Daftar Produk
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Daftar Produk</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="box">
                <div class="box-header with-border">
                    <div class="btn-group">
                        <button onclick="addForm('{{ route('produk.store') }}')" class="btn btn-success btn-xs btn-flat"><i
                                class="fa fa-plus-circle"></i> Tambah</button>
                        <button onclick="deleteSelected('{{ route('produk.delete_selected') }}')"
                            class="btn btn-danger btn-xs btn-flat"><i class="fa fa-trash"></i> Hapus</button>
                        <button onclick="cetakBarcode('{{ route('produk.cetak_barcode') }}')"
                            class="btn btn-info btn-xs btn-flat"><i class="fa fa-barcode"></i> Cetak Barcode</button>
                        <a href="{{ route('produk.export') }}" class="btn btn-warning btn-xs btn-flat"><i
                                class="fa fa-upload"></i> Eksport Data Produk</a>
                        <button type="button" class="btn btn-primary btn-xs btn-flat" data-toggle="modal"
                            data-target="#modal-import">
                            <i class="fa fa-download"></i> Impor Data Produk
                        </button>
                        <button type="button" class="btn btn-primary btn-xs btn-flat" data-toggle="modal"
                            data-target="#modal-satuan-produk" id="btn-manage-satuan">
                            <i class="fa fa-circle"></i> Kelola Daftar Satuan
                        </button>
                    </div>
                </div>
                <div id="import-message"></div>
                <div class="box-body table-responsive">
                    <form action="" method="post" class="form-produk">
                        @csrf
                        <table class="table table-stiped table-bordered" id="table-produk">
                            <thead>
                                <th width="5%">
                                    <input type="checkbox" name="select_all" id="select_all">
                                </th>
                                <th width="5%">No</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Merk</th>
                                <th>Harga Beli</th>
                                <th>Harga Jual Eceran</th>
                                <th>Harga Jual Borongan</th>
                                <th>Diskon</th>
                                <th>Stok</th>
                                <th width="15%"><i class="fa fa-cog"></i></th>
                            </thead>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @includeIf('produk.form')
    @includeIf('produk.import')
    @includeIf('produk.satuan_form')
@endsection

@push('scripts')
    <script>
        window.availableSatuan = @json($allSatuan);
        let table;

        $(function() {
            table = $('#table-produk').DataTable({
                processing: true,
                serverSide: false,
                autoWidth: false,
                ajax: {
                    url: '{{ route('produk.data') }}',
                },
                columns: [{
                        data: 'select_all',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'kode_produk'
                    },
                    {
                        data: 'nama_produk'
                    },
                    // kolom kategori
                    {
                        data: 'nama_kategori',
                        title: 'Kategori'
                    },
                    {
                        data: 'merk'
                    },
                    {
                        data: 'harga_beli'
                    },
                    // kolom satuan eceran & borongan sekarang berisi HTML dengan <br>
                    {
                        data: 'produk_satuan_eceran',
                        title: 'Satuan Eceran'
                    },
                    {
                        data: 'produk_satuan_borongan',
                        title: 'Satuan Borongan'
                    },
                    {
                        data: 'diskon'
                    },
                    {
                        data: 'stok'
                    },
                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false
                    },
                ],
                columnDefs: [{
                    // pastikan cell satuan dibiarkan render HTML
                    targets: [7, 8],
                    render: function(data, type, row) {
                        return data;
                    }
                }]
            });

            $('#modal-form').validator().on('submit', function(e) {
                if (!e.preventDefault()) {
                    $.post($('#modal-form form').attr('action'), $('#modal-form form').serialize())
                        .done((response) => {
                            $('#modal-form').modal('hide');
                            table.ajax.reload();
                        })
                        .fail((errors) => {
                            alert('Sepertinya ada yang salah silahkan cek kembali form');
                            return;
                        });
                }
            });

            $('[name=select_all]').on('click', function() {
                $(':checkbox').prop('checked', this.checked);
            });
        });

        function addForm(url) {
            const fieldKodeProduk = $('#kode_produk');
            fieldKodeProduk.removeClass('is-invalid is-valid');
            $('#kode-produk-error, #kode-produk-valid').remove();
            $('#nama-produk-error, #nama-produk-valid').remove();
            const kodeOri = $('#original_kode_produk');
            const namaOri = $('#original_nama_produk');
            kodeOri.val('');
            namaOri.val('');
            refreshSatuanList();
            const satuanContainer = $('#produk-satuan-container');
            satuanContainer.empty();
            satuanContainer.append(`
            <div class="form-group row" id="satuan-0">
                            <label class="col-lg-2 col-lg-offset-1 control-label">Satuan</label>
                            <div class="col-lg-2">
                                <select class="form-control satuan-select" name="produk_satuan[0][satuan]" required>
                                    ${buildSatuanOptions()}
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <input type="number" class="form-control" name="produk_satuan[0][harga_jual_eceran]" placeholder="Harga Jual Eceran" required>
                                <input type="number" class="form-control" name="produk_satuan[0][harga_jual_borongan]" placeholder="Harga Jual Borongan" required>
                                <span class="help-block with-errors"></span>
                            </div>
                        </div>
            `)
            $('#modal-form').modal('show');
            $('#modal-form .modal-title').text('Tambah Produk');

            $('#modal-form form')[0].reset();
            $('#modal-form form').attr('action', url);
            $('#modal-form [name=_method]').val('post');
            $('#modal-form [name=nama_produk]').focus();
        }

        $('#modal-form').on('click', '.btn-add-satuan', function() {
            const satuanField = $(this).closest('.satuan-group').clone();
            satuanField.find('input').val('');
            $('#satuan-container').append(satuanField);
        });

        function editForm(url) {
            const fieldKodeProduk = $('#kode_produk');
            fieldKodeProduk.removeClass('is-invalid is-valid');
            $('#kode-produk-error, #kode-produk-valid').remove();
            $('#nama-produk-error, #nama-produk-valid').remove();
            const $satuanContainer = $('#produk-satuan-container').empty()
            .append(`<h5 class="text-center"><strong>Edit Harga Satuan</strong></h5>
            <span class="help-block with-errors"></span>`);
            
            refreshSatuanList();
            $('#modal-form').modal('show')
                .find('.modal-title').text('Edit Produk');

            $('#modal-form form')
                .attr('action', url)
                .trigger('reset')
                .find('[name=_method]').val('put');


            $.get(url)
                .done(response => {
                    $('[name=kode_produk]').val(response.kode_produk);
                    $('#original_kode_produk').val(response.kode_produk);
                    $('[name=nama_produk]').val(response.nama_produk);
                    $('[name=id_kategori]').val(response.id_kategori);
                    $('[name=merk]').val(response.merk);
                    $('[name=harga_beli]').val(response.harga_beli);
                    $('[name=diskon]').val(response.diskon);
                    $('[name=stok]').val(response.stok);
                    const listSatuan = response.produk_satuan; // array hasil transformasi di controller
                    let satuanDefault = window.availableSatuan; // kalau masih pakai dynamic list

                    $('#produk-satuan-container').empty();
                    listSatuan.forEach((ps, idx) => {
                        const namaSatuan = ps.satuan; // <— sekarang pasti ada
                        const isDefault = namaSatuan === 'null';
                        const isCustom = !satuanDefault.includes(namaSatuan);

                        // Bangun field select/input
                        let satuanField;
                        if (isCustom) {
                            satuanField = `
                            <input type="text" class="form-control custom-satuan"
                                    name="produk_satuan[${idx}][satuan]"
                                    value="${namaSatuan}" required>`;
                        } else {
                            satuanField = `
                            <select class="form-control satuan-select"
                                    name="produk_satuan[${idx}][satuan]" required>
                                <option value="">Pilih Satuan</option>
                                ${satuanDefault.map(s => `
                                                        <option value="${s}"
                                                            ${s === namaSatuan ? 'selected' : ''}>${s}</option>
                                                        `).join('')}
                                <option value="custom">Custom</option>
                            </select>`;
                        }

                        // Append ke container
                        $('#produk-satuan-container').append(`
                                                <div class="form-group row" id="satuan-${idx}">
                                                <label class="col-lg-2 col-lg-offset-1 control-label"></label>
                                                <div class="col-lg-2">${satuanField}</div>
                                                <div class="col-lg-4">
                                                    <input type="number" class="form-control"
                                                        name="produk_satuan[${idx}][harga_jual_eceran]"
                                                        value="${ps.harga_jual_eceran}" placeholder="Harga Jual Eceran" required>
                                                    <input type="number" class="form-control"
                                                        name="produk_satuan[${idx}][harga_jual_borongan]"
                                                        value="${ps.harga_jual_borongan}" placeholder="Harga Jual Borongan" required>
                                                </div>
                                                ${!isDefault ? `
                                                        <div class="col-lg-2">
                                                        <button type="button" class="btn btn-danger btn-remove-satuan" data-id="${idx}">
                                                            Hapus
                                                        </button>
                                                        </div>` : ''}
                                                </div>
                        `);
                    });

                    $('#modal-form').modal('show');
                })
                .fail(() => alert('Tidak dapat menampilkan data'));
        }

        // restore dropdown ketika memilih "custom"
        $(document).on('change', '.satuan-select', function() {
            if (this.value === 'custom') {
                const name = this.name;
                $(this).replaceWith(`
            <input type="text" class="form-control custom-satuan"
                   name="${name}" placeholder="Satuan (Custom)" required>
        `);
            }
        });

        // hapus satuan
        $(document).on('click', '.btn-remove-satuan', function() {
            const id = $(this).data('id');
            $(`#satuan-${id}`).remove();
        });


        // Event untuk menangani input manual jika opsi "Custom" dipilih
        $(document).on('change', 'select[name^="produk_satuan"]', function() {
            const selectedValue = $(this).val();
            if (selectedValue === 'custom') {
                // Ganti dropdown dengan input teks jika "Custom" dipilih
                const customInput = `
            <input type="text" class="form-control" name="${$(this).attr('name')}" placeholder="Satuan (Custom)" required>
        `;
                $(this).replaceWith(customInput);
            }
        });

        // Event listener untuk menghapus satuan
        $(document).on('click', '.btn-remove-satuan', function() {
            const id = $(this).data('id');
            $(`#satuan-${id}`).remove();
        });

        // Validasi form sebelum submit
        $('#modal-form').on('submit', function(e) {
            let isValid = true;
            $('#produk-satuan-container input').each(function() {
                if ($(this).val().trim() === '') {
                    isValid = false;
                    alert('Harap isi semua field produk satuan.');
                    $(this).focus();
                    return false;
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });

        // validasi kode produk
        $(document).on('blur', '[name=kode_produk]', function() {
            var kode = $(this).val().trim();
            var originalKode = $('#original_kode_produk').val().trim();
            var inputField = $(this);

            // Jika kode tidak kosong dan berbeda dengan kode asli, lakukan validasi
            if (kode !== '' && kode !== originalKode) {
                $.ajax({
                    url: '{{ route('produk.checkKode') }}',
                    type: 'GET',
                    data: {
                        kode_produk: kode
                    },
                    success: function(response) {
                        $('#kode-produk-error, #kode-produk-valid')
                            .remove(); // Hapus pesan validasi lama

                        if (response.exists) {
                            // Jika kode produk sudah ada, tampilkan nama produk dari response
                            var errorMessage =
                                'Kode produk sudah digunakan untuk produk dengan nama "' + response
                                .nama_produk + '"!';
                            inputField.addClass('is-invalid').removeClass('is-valid');
                            $('#field_kode_produk').addClass('has-error');
                            $('<span id="kode-produk-error" class="help-block with-errors" style="color: red;">' +
                                    errorMessage + '</span>')
                                .insertAfter(inputField);
                            inputField.focus();
                        } else {
                            // Jika kode produk belum ada
                            inputField.addClass('is-valid').removeClass('is-invalid');
                            $('#field_kode_produk').addClass('has-success');
                            $('<span id="kode-produk-valid" class="help-block with-errors" style="color: green;">Kode produk dapat digunakan!</span>')
                                .insertAfter(inputField);
                        }
                    },
                    error: function(xhr) {
                        console.error('Terjadi kesalahan saat memeriksa kode produk:', xhr
                            .responseText);
                        inputField.removeClass('is-invalid is-valid');
                        $('#kode-produk-error, #kode-produk-valid').remove();
                    }
                });
            } else {
                // Jika input dikosongkan atau tidak berubah, hapus pesan validasi
                inputField.removeClass('is-invalid is-valid');
                $('#kode-produk-error, #kode-produk-valid').remove();
            }
        });

        // Validasi nama produk
        $(document).on('blur', '[name=nama_produk]', function() {
            var nama = $(this).val().trim();
            var originalNama = $('#original_nama_produk').val() ? $('#original_nama_produk').val().trim() : '';
            var inputField = $(this);

            // Lakukan validasi jika nama tidak kosong dan berbeda dari nilai asli (saat edit)
            if (nama !== '' && nama !== originalNama) {
                $.ajax({
                    url: '{{ route('produk.checkNama') }}',
                    type: 'GET',
                    data: {
                        nama_produk: nama
                    },
                    success: function(response) {
                        $('#nama-produk-error, #nama-produk-valid')
                            .remove();

                        if (response.exists) {
                            var errorMessage =
                                'Nama produk sudah digunakan untuk produk dengan kode "' + response
                                .kode_produk + '"!';
                            inputField.addClass('is-invalid').removeClass('is-valid');
                            $('#field_nama_produk').addClass('has-error');
                            $('<span id="nama-produk-error" class="help-block with-errors" style="color: red;">' +
                                    errorMessage + '</span>')
                                .insertAfter(inputField);
                            inputField.focus();
                        } else {
                            inputField.addClass('is-valid').removeClass('is-invalid');
                            $('#field_nama_produk').addClass('has-success');
                            $('<span id="nama-produk-valid" class="help-block with-errors" style="color: green;">Nama produk dapat digunakan!</span>')
                                .insertAfter(inputField);
                        }
                    },
                    error: function(xhr) {
                        console.error('Terjadi kesalahan saat memeriksa nama produk:', xhr
                            .responseText);
                        inputField.removeClass('is-invalid is-valid');
                        $('#nama-produk-error, #nama-produk-valid').remove();
                    }
                });
            } else {
                inputField.removeClass('is-invalid is-valid');
                $('#nama-produk-error, #nama-produk-valid').remove();
            }
        });

        $('#modal-form').on('click', '.btn-remove-satuan', function() {
            $(this).closest('.satuan-group').remove();
        });

        function deleteData(url) {
            if (confirm('Yakin ingin menghapus data terpilih?')) {
                $.post(url, {
                        '_token': $('[name=csrf-token]').attr('content'),
                        '_method': 'delete'
                    })
                    .done((response) => {
                        table.ajax.reload();
                    })
                    .fail((errors) => {
                        alert('Tidak dapat menghapus data');
                        return;
                    });
            }
        }

        function deleteSelected(url) {
            if ($('input:checked').length > 1) {
                if (confirm('Yakin ingin menghapus data terpilih?')) {
                    $.post(url, $('.form-produk').serialize())
                        .done((response) => {
                            table.ajax.reload();
                        })
                        .fail((errors) => {
                            alert('Tidak dapat menghapus data');
                            return;
                        });
                }
            } else {
                alert('Pilih data yang akan dihapus');
                return;
            }
        }

        function cetakBarcode(url) {
            if ($('input:checked').length < 1) {
                alert('Pilih data yang akan dicetak');
                return;
            } else if ($('input:checked').length < 3) {
                alert('Pilih minimal 3 data untuk dicetak');
                return;
            } else {
                $('.form-produk')
                    .attr('target', '_blank')
                    .attr('action', url)
                    .submit();
            }
        }

        $(function() {
            $('#import-form').on('submit', function(e) {
                e.preventDefault();

                let formData = new FormData(this);

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        $('#modal-import').modal('hide');
                        $('#import-message').addClass('alert alert-success alert-dismissible')
                            .text(response.message).fadeIn();

                        table.ajax.reload();

                        setTimeout(() => {
                            $('#import-message').fadeOut();
                        }, 5000);
                    },
                    error: function(xhr, status, error) {
                        let errorMessage = 'Terjadi kesalahan saat mengimpor data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert(errorMessage);
                    }
                });
            });
        });

        function fetchSemuaSatuan() {
                return $.getJSON('/api/satuan-produk');
            }

        // Fungsi fetch ulang dan render ulang semua dropdown satuan
        function refreshSatuanList() {
            // $('#btn-refresh-satuan').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memuat...');
            fetchSemuaSatuan().then(list => {
                window.availableSatuan = list;
                renderAllSatuanSelects();
                // $('#btn-refresh-satuan').prop('disabled', false).html(
                //     '<i class="fa fa-refresh"></i> Refresh Daftar Satuan');
            }).catch(() => {
                alert('Gagal memuat daftar satuan');
                // $('#btn-refresh-satuan').prop('disabled', false).html(
                //     '<i class="fa fa-refresh"></i> Refresh Daftar Satuan');
            });
        }

        // script untuk form satuan
        $(function() {
            // buka modal master
            $('#btn-manage-satuan').on('click', () => loadSatuan());

            function loadSatuan() {
                $.getJSON("satuan-produk", data => {
                    const $tbody = $('#table-satuan-produk tbody').empty();
                    data.forEach((item, i) => {
                        $tbody.append(`
                <tr>
                    <td>${i+1}</td>
                    <td>${item.nama}</td>
                    <td>
                    <button class="btn btn-sm btn-info btn-edit-satuan" data-id="${item.id}" data-nama="${item.nama}">Edit</button>
                    <button class="btn btn-sm btn-danger btn-delete-satuan" data-id="${item.id}">Hapus</button>
                    </td>
                </tr>
                `);
                    });
                    $('#modal-satuan-produk').modal('show');
                });
            }

            // Tambah
            $('#btn-add-new-satuan').on('click', () => {
                $('#form-satuan-produk')[0].reset();
                $('#satuan-id').val('');
                $('#modal-form-satuan-label').text('Tambah Satuan Baru');
                $('#modal-form-satuan').modal('show');
            });

            // Edit
            $(document).on('click', '.btn-edit-satuan', function() {
                $('#satuan-id').val(this.dataset.id);
                $('#satuan-nama').val(this.dataset.nama);
                $('#modal-form-satuan-label').text('Edit Satuan');
                $('#modal-form-satuan').modal('show');
            });

            // Delete
            $(document).on('click', '.btn-delete-satuan', function() {
                if (!confirm('Yakin hapus satuan ini?')) return;
                $.ajax({
                        url: `/api/satuan-produk/${this.dataset.id}`,
                        method: 'DELETE'
                    })
                    .done((response) => {
                        loadSatuan();
                        renderAllSatuanSelects();
                        table.ajax.reload();
                    })
                    .fail(() => alert('Gagal menghapus'));
            });

            

            // Submit form
            $('#form-satuan-produk').on('submit', function(e) {
                e.preventDefault();
                const id = $('#satuan-id').val();
                const nama = $('#satuan-nama').val().trim();
                if (!nama) return $('#satuan-nama').addClass('is-invalid');
                const method = id ? 'PUT' : 'POST';
                const url = id ? `/api/satuan-produk/${id}` : '/api/satuan-produk';
                $.ajax({
                        url,
                        method,
                        data: {
                            nama
                        },
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .done(({
                        data
                    }) => {
                        $('#modal-form-satuan').modal('hide');
                        loadSatuan();
                        window.availableSatuan.push(data.nama);
                        renderAllSatuanSelects();
                        table.ajax.reload();
                    })
                    .fail(() => alert('Gagal menyimpan'));
            });
        });
    </script>
@endpush
