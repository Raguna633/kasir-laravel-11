<!-- Modal Master Satuan Produk -->
<div class="modal fade" id="modal-satuan-produk" tabindex="-1" role="dialog" aria-labelledby="modal-satuan-produk-label">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-satuan-produk-label">Kelola Satuan Produk</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <button id="btn-add-new-satuan" class="btn btn-primary btn-sm mb-2">Tambah Satuan</button>
                <table class="table table-striped" id="table-satuan-produk">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Satuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Tambah/Edit -->
<div class="modal fade" id="modal-form-satuan" tabindex="-1" role="dialog" aria-labelledby="modal-form-satuan-label">
    <div class="modal-dialog" role="document">
        <form id="form-satuan-produk" novalidate>
            @csrf
            <input type="hidden" name="id_satuan" id="satuan-id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-form-satuan-label"></h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="satuan-nama">Nama Satuan</label>
                        <input type="text" class="form-control" id="satuan-nama" name="nama" required>
                        <div class="invalid-feedback">Nama satuan wajib diisi.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        
    </script>
@endpush
