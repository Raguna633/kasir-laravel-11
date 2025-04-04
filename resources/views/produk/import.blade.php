<div class="modal fade" id="modal-import" tabindex="-1" role="dialog" aria-labelledby="modal-form" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-form">Impor Data Produk</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="import-form" action="{{ route('produk.import') }}" method="post" enctype="multipart/form-data">
                @csrf
                @method('post')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="file">Pilih File Excel</label>
                        <input type="file" name="file" id="file" class="form-control" required>
                        <small class="form-text text-muted">Hanya file dengan format <strong>.xls, .xlsx</strong> yang didukung.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Impor Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
