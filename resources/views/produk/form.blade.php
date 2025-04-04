<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-labelledby="modal-form">
    <div class="modal-dialog modal-lg" role="document">
        <input type="hidden" name="original_kode_produk" id="original_kode_produk" value="">
        <input type="hidden" name="original_nama_produk" id="original_nama_produk" value="">
        <form action="" method="post" class="form-horizontal">
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
                            <span class="help-block with-errors"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="nama_produk" class="col-lg-2 col-lg-offset-1 control-label">Nama</label>
                        <div class="col-lg-6">
                            <input type="text" name="nama_produk" id="nama_produk" class="form-control" required
                                autofocus>
                            <span class="help-block with-errors"></span>
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
                    <button type="button" class="btn btn-sm btn-flat btn-warning" data-dismiss="modal"><i
                            class="fa fa-arrow-circle-left"></i> Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let satuanCount = 1; // Awalnya mulai dari 1 karena satuan default sudah ada

    // Fungsi untuk memperbarui satuanCount agar tetap berurutan
    function updateSatuanCount() {
        let lastIndex = 0;

        $('#produk-satuan-container .form-group').each(function() {
            const nameAttr = $(this).find('select[name^="produk_satuan["], input[name^="produk_satuan["]').attr(
                'name');
            if (nameAttr) {
                const match = nameAttr.match(/\[([0-9]+)\]/);
                if (match) {
                    const index = parseInt(match[1], 10);
                    if (index > lastIndex) {
                        lastIndex = index;
                    }
                }
            }
        });

        satuanCount = lastIndex + 1;
    }

    // Fungsi untuk mendapatkan daftar satuan yang sudah dipilih
    function getSelectedSatuan() {
        let selectedSatuan = [];
        $('.satuan-select').each(function() {
            const val = $(this).val();
            if (val && val !== "custom") {
                selectedSatuan.push(val);
            }
        });
        return selectedSatuan;
    }

    // Tambahkan satuan baru
    $('#add-satuan').on('click', function() {
        updateSatuanCount();

        const satuanInput = `
    <div class="form-group row" id="satuan-${satuanCount}">
        <label class="col-lg-2 col-lg-offset-1 control-label"></label>
        <div class="col-lg-2">
            <select class="form-control satuan-select" name="produk_satuan[${satuanCount}][satuan]" required>
                <option value="">Pilih Satuan</option>
                <option value="renteng">Renteng</option>
                <option value="lusin">Lusin</option>
                <option value="dus">Dus</option>
                <option value="pak">Pak</option>
                <option value="gross">Gross</option>
                <option value="custom">Custom</option>
            </select>
            <span class="help-block with-errors"></span>
        </div>
        <div class="col-lg-4">
            <input type="number" class="form-control" name="produk_satuan[${satuanCount}][harga_jual_eceran]" placeholder="Harga Jual Eceran" required>
            <input type="number" class="form-control" name="produk_satuan[${satuanCount}][harga_jual_borongan]" placeholder="Harga Jual Borongan" required>
            <span class="help-block with-errors"></span>
        </div>
        <div class="col-lg-2">
            <button type="button" class="btn btn-danger btn-remove-satuan" data-id="${satuanCount}">Hapus</button>
        </div>
    </div>
    `;

        $('#produk-satuan-container').append(satuanInput);
        satuanCount++;
    });

    // Validasi saat memilih satuan agar tidak duplikat
    $(document).on('change', '.satuan-select', function() {
        const selectedValue = $(this).val();
        const selectedSatuan = getSelectedSatuan();

        // Jika satuan sudah dipilih sebelumnya (kecuali "custom"), reset ke "Pilih Satuan" dan beri alert
        if (selectedValue !== "custom" && selectedSatuan.filter(s => s === selectedValue).length > 1) {
            alert("Satuan sudah dipilih! Pilih satuan lain.");
            $(this).val(""); // Reset dropdown ke default
        }

        // Jika "Custom" dipilih, ubah dropdown menjadi input teks
        if (selectedValue === 'custom') {
            const inputName = $(this).attr('name');
            const customInput = `
        <input type="text" class="form-control satuan-custom" name="${inputName}" placeholder="Satuan (Custom)" required>
        <button type="button" class="btn btn-warning btn-restore-dropdown">↺</button>
        `;
            $(this).parent().html(customInput);
        }
    });

    // Hapus field satuan
    $(document).on('click', '.btn-remove-satuan', function() {
        const id = $(this).data('id');
        $(`#satuan-${id}`).remove();
        updateSatuanCount(); // Update satuanCount setelah penghapusan
    });

    // Event untuk mengembalikan dropdown jika input custom dihapus
    $(document).on('click', '.btn-restore-dropdown', function() {
        const parentDiv = $(this).parent();
        const inputName = parentDiv.find('.satuan-custom').attr('name');

        const dropdown = `
    <select class="form-control satuan-select" name="${inputName}" required>
        <option value="">Pilih Satuan</option>
        <option value="renteng">Renteng</option>
        <option value="lusin">Lusin</option>
        <option value="dus">Dus</option>
        <option value="pak">Pak</option>
        <option value="gross">Gross</option>
        <option value="custom">Custom</option>
    </select>
    `;

        parentDiv.html(dropdown);
    });
</script>
