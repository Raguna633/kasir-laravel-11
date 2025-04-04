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
                    </div>
                </div>
                <div id="import-message"></div>
                <div class="box-body table-responsive">
                    <form action="" method="post" class="form-produk">
                        @csrf
                        <table class="table table-stiped table-bordered">
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
@endsection

@push('scripts')
    <script>
        let table;

        $.ajax({
            url: '{{ route('produk.data') }}',
            success: function(response) {
                console.log(response); // Periksa apakah data valid
            },
            error: function(xhr) {
                console.error('Error:', xhr.responseText);
            }
        });


        $(function() {
            table = $('.table').DataTable({
                processing: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('produk.data') }}',
                },
                columns: [{
                        data: 'select_all',
                        searchable: false,
                        sortable: false
                    },
                    {
                        data: 'DT_RowIndex',
                        searchable: false,
                        sortable: false
                    },
                    {
                        data: 'kode_produk'
                    },
                    {
                        data: 'nama_produk'
                    },
                    {
                        data: 'nama_kategori'
                    },
                    {
                        data: 'merk'
                    },
                    {
                        data: 'harga_beli'
                    },
                    {
                        data: 'produk_satuan_eceran'
                    },
                    {
                        data: 'produk_satuan_borongan'
                    },
                    {
                        data: 'diskon'
                    },
                    {
                        data: 'stok'
                    },
                    {
                        data: 'aksi',
                        searchable: false,
                        sortable: false
                    },
                ]
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
            const kodeOri = $('#original_kode_produk');
            kodeOri.val('');
            const satuanContainer = $('#produk-satuan-container');
            satuanContainer.empty();
            satuanContainer.append(`
            <div class="form-group row" id="satuan-0">
                            <label class="col-lg-2 col-lg-offset-1 control-label">Satuan</label>
                            <div class="col-lg-2">
                                <input type="text" class="form-control" name="produk_satuan[0][satuan]" value="pcs" readonly>
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
            const satuanContainer = $('#produk-satuan-container');
            satuanContainer.empty();
            satuanContainer.append(`
        <h5 class="text-center"><strong>Edit Harga Satuan</strong></h5>
        <span class="help-block with-errors"></span>
    `);

            $('#modal-form').modal('show');
            $('#modal-form .modal-title').text('Edit Produk');

            $('#modal-form form')[0].reset();
            $('#modal-form form').attr('action', url);
            $('#modal-form [name=_method]').val('put');
            $('#modal-form [name=nama_produk]').focus();

            $.get(url)
                .done((response) => {
                    console.log('Respons dari server:', response);

                    // Isi data produk
                    $('#modal-form [name=kode_produk]').val(response.kode_produk);
                    // Set kode produk asli
                    $('#original_kode_produk').val(response.kode_produk);
                    $('#modal-form [name=nama_produk]').val(response.nama_produk);
                    $('#modal-form [name=id_kategori]').val(response.id_kategori);
                    $('#modal-form [name=merk]').val(response.merk);
                    $('#modal-form [name=harga_beli]').val(response.harga_beli);
                    $('#modal-form [name=diskon]').val(response.diskon);
                    $('#modal-form [name=stok]').val(response.stok);

                    // Daftar satuan bawaan
                    const satuanBawaan = ["renteng", "lusin", "dus", "pak", "gross", "ball"];

                    // Iterasi data satuan
                    if (Array.isArray(response.produk_satuan) && response.produk_satuan.length > 0) {
                        response.produk_satuan.forEach((satuan, index) => {
                            const isDefault = satuan.satuan === 'pcs'; // Satuan default
                            const isCustom = !satuanBawaan.includes(satuan
                                .satuan); // Cek apakah satuan adalah custom

                            let satuanField;
                            if (isDefault) {
                                satuanField =
                                    `<input type="text" class="form-control" name="produk_satuan[${index}][satuan]" value="pcs" readonly>`;
                            } else if (isCustom) {
                                satuanField = `
                            <input type="text" class="form-control custom-satuan" name="produk_satuan[${index}][satuan]" value="${satuan.satuan}" required>
                        `;
                            } else {
                                satuanField = `
                            <select class="form-control satuan-select" name="produk_satuan[${index}][satuan]" required>
                                <option value="">Pilih Satuan</option>
                                <option value="renteng" ${satuan.satuan === 'renteng' ? 'selected' : ''}>Renteng</option>
                                <option value="lusin" ${satuan.satuan === 'lusin' ? 'selected' : ''}>Lusin</option>
                                <option value="dus" ${satuan.satuan === 'dus' ? 'selected' : ''}>Dus</option>
                                <option value="pak" ${satuan.satuan === 'pak' ? 'selected' : ''}>Pak</option>
                                <option value="gross" ${satuan.satuan === 'gross' ? 'selected' : ''}>Gross</option>
                                <option value="custom">Custom</option>
                            </select>
                        `;
                            }

                            satuanContainer.append(`
                        <div class="form-group row" id="satuan-${index}">
                            <label class="col-lg-2 col-lg-offset-1 control-label"></label>
                            <div class="col-lg-2">
                                ${satuanField}
                            </div>
                            <div class="col-lg-4">
                                <input type="number" class="form-control" name="produk_satuan[${index}][harga_jual_eceran]" value="${satuan.harga_jual_eceran}" placeholder="Harga Jual Eceran" required>
                                <input type="number" class="form-control" name="produk_satuan[${index}][harga_jual_borongan]" value="${satuan.harga_jual_borongan}" placeholder="Harga Jual Borongan" required>
                            </div>
                            ${!isDefault ? `
                                                                                                                            <div class="col-lg-2">
                                                                                                                                <button type="button" class="btn btn-danger btn-remove-satuan" data-id="${index}">Hapus</button>
                                                                                                                            </div>` : ''}
                        </div>
                    `);

                            // Jika satuan adalah custom, ubah dropdown menjadi input teks
                            if (isCustom) {
                                $(`#satuan-${index} .satuan-select`).replaceWith(`
                            <input type="text" class="form-control custom-satuan" name="produk_satuan[${index}][satuan]" value="${satuan.satuan}" required>
                        `);
                            }
                        });
                    }
                })
                .fail((errors) => {
                    alert('Tidak dapat menampilkan data');
                    return;
                });
        }

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
                        $('#nama-produk-error, #nama-produk-valid').remove();

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
    </script>
@endpush
