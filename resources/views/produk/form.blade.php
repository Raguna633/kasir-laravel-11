<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modal-form">
    <div class="modal-dialog modal-lg" role="document">
        <input type="hidden" name="original_kode_produk" id="original_kode_produk" value="">
        <input type="hidden" name="original_nama_produk" id="original_nama_produk" value="">
        <form action="" method="post" class="form-horizontal" id="form-produk">
            @csrf
            @method('post')

            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group row" id="field_kode_produk">
                        <label for="kode_produk" class="col-lg-2 col-lg-offset-1 control-label">Kode Produk</label>
                        <div class="col-lg-6">
                            <input type="number" id="kode_produk" name="kode_produk" kode="kode_produk"
                                placeholder="Isi dengan nomor barcode produk" class="form-control" autofocus>
                            {{-- <span class="help-block with-errors"></span> --}}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="nama_produk" class="col-lg-2 col-lg-offset-1 control-label">Nama</label>
                        <div class="col-lg-6">
                            <input type="text" name="nama_produk" id="nama_produk" class="form-control" required
                                autofocus>
                            {{-- <span class="help-block with-errors"></span> --}}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="id_kategori" class="col-lg-2 col-lg-offset-1 control-label">Kategori</label>
                        <div class="col-lg-6">
                            <select name="id_kategori" id="id_kategori" class="form-control" required>
                                <option value="">Pilih Kategori</option>
                                @foreach ($kategori as $key => $item)
                                    <option value="{{ $key }}">{{ $item }}</option>
                                @endforeach
                            </select>
                            <span class="help-block with-errors"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="merk" class="col-lg-2 col-lg-offset-1 control-label">Merk</label>
                        <div class="col-lg-6">
                            <input type="text" name="merk" id="merk" class="form-control">
                            <span class="help-block with-errors"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="harga_beli" class="col-lg-2 col-lg-offset-1 control-label">Harga Beli</label>
                        <div class="col-lg-6">
                            <input type="number" name="harga_beli" id="harga_beli" class="form-control" required>
                            <span class="help-block with-errors"></span>
                        </div>
                    </div>

                    {{-- Input satuan dinamis --}}
                    <div id="produk-satuan-container"></div>

                    <!-- Tombol untuk menambah satuan baru -->
                    <div class="form-group row text-center">
                        <button type="button" id="add-satuan" class="btn btn-primary">Tambah Harga Jual/Satuan
                            Lain</button>
                        <span class="help-block with-errors"></span>
                    </div>
                    <div class="form-group row">
                        <label for="diskon" class="col-lg-2 col-lg-offset-1 control-label">Diskon</label>
                        <div class="col-lg-6">
                            <input type="number" name="diskon" id="diskon" class="form-control" value="0">
                            <span class="help-block with-errors"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="stok" class="col-lg-2 col-lg-offset-1 control-label">Stok</label>
                        <div class="col-lg-6">
                            <input type="number" name="stok" id="stok" class="form-control" required
                                value="0">
                            <span class="help-block with-errors"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-sm btn-flat btn-primary"><i class="fa fa-save"></i> Simpan</button>
                    {{-- <button type="button" class="btn btn-secondary" id="btn-refresh-satuan">
                        <i class="fa fa-refresh"></i> Refresh Daftar Satuan
                    </button> --}}
                    <button type="button" class="btn btn-sm btn-flat btn-warning" data-dismiss="modal"><i
                            class="fa fa-arrow-circle-left"></i> Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let satuanCount = 1;

    function updateSatuanCount() {
        let lastIndex = 0;
        $('#produk-satuan-container .form-group').each(function() {
            const nameAttr = $(this)
                .find('select[name^="produk_satuan["], input[name^="produk_satuan["]')
                .attr('name');
            if (nameAttr) {
                const match = nameAttr.match(/\[([0-9]+)\]/);
                if (match) {
                    const idx = parseInt(match[1], 10);
                    lastIndex = Math.max(lastIndex, idx);
                }
            }
        });
        satuanCount = lastIndex + 1;
    }

    function getSelectedSatuan() {
        return $('.satuan-select').toArray()
            .map(el => $(el).val())
            .filter(v => v && v !== 'custom');
    }

    function buildSatuanOptions(selected = '') {
        const opts = ['<option value="">Pilih Satuan</option>']
            .concat(
                window.availableSatuan.map(s => `<option value="${s}" ${s===selected?'selected':''}>${s}</option>`)
            )
            .concat('<option value="custom">Custom</option>');
        return opts.join('');
    }

    function renderAllSatuanSelects() {
        $('.satuan-select').each(function() {
            const cur = $(this).val();
            $(this).html(buildSatuanOptions(cur));
        });
    }

    $('#add-satuan').on('click', function() {
        updateSatuanCount();
        const idx = satuanCount;
        const $row = $(`
    <div class="form-group row" id="satuan-${idx}">
      <label class="col-lg-2 col-lg-offset-1 control-label"></label>
      <div class="col-lg-2">
        <select class="form-control satuan-select"
                name="produk_satuan[${idx}][satuan]" required>
          ${buildSatuanOptions()}
        </select>
        <span class="help-block with-errors"></span>
      </div>
      <div class="col-lg-4">
        <input type="number" class="form-control"
               name="produk_satuan[${idx}][harga_jual_eceran]"
               placeholder="Harga Jual Eceran" required>
        <input type="number" class="form-control"
               name="produk_satuan[${idx}][harga_jual_borongan]"
               placeholder="Harga Jual Borongan" required>
        <span class="help-block with-errors"></span>
      </div>
      <div class="col-lg-2">
        <button type="button" class="btn btn-danger btn-remove-satuan"
                data-id="${idx}">Hapus</button>
      </div>
    </div>
  `);
        $('#produk-satuan-container').append($row);
        satuanCount++;
    });

    // Jangan biarkan duplikat pilihan
    $(document).on('change', '.satuan-select', function() {
        const val = $(this).val();
        const dupCount = getSelectedSatuan().filter(s => s === val).length;
        if (val !== 'custom' && dupCount > 1) {
            alert('Satuan sudah dipilih, silakan pilih yang lain.');
            $(this).val('');
            return;
        }
        if (val === 'custom') {
            const name = $(this).attr('name');
            $(this).parent().html(`
            <input type="text" id="satuan-custom" class="form-control satuan-custom"
                    name="${name}" placeholder="Satuan (Custom)" required>
            <button type="button" class="btn btn-warning btn-restore-dropdown">↺</button>
    `);
        }
    });

    $(document).on('click', '.btn-remove-satuan', function() {
        const id = $(this).data('id');
        $(`#satuan-${id}`).remove();
        updateSatuanCount();
    });

    $(document).on('click', '.btn-restore-dropdown', function() {
        const $p = $(this).parent();
        const name = $p.find('.satuan-custom').attr('name');
        $p.html(`
        <select class="form-control satuan-select" name="${name}" required>
        ${buildSatuanOptions()}
        </select>
    `);
    });
</script>
