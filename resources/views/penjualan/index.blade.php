@extends('layouts.master')

@section('title')
    Daftar Penjualan
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Daftar Penjualan</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-body table-responsive">
                <table class="table table-stiped table-bordered table-penjualan" style=" text-transform: capitalize;">
                    <thead>
                        <th width="5%">No</th>
                        <th>Tanggal</th>
                        <th>Kode Member</th>
                        <th>Nama Pembeli</th>
                        <th>Total Item</th>
                        <th>Satuan</th>
                        <th>Total Harga</th>
                        <th>Diskon</th>
                        <th>Total Bayar</th>
                        <th>Belum Terbayar</th>
                        <th>Tipe Pembelian</th>
                        <th>Kasir</th>
                        <th>Status</th>
                        <th width="15%"><i class="fa fa-cog"></i></th>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>



@includeIf('penjualan.detail')
@endsection

@push('scripts')
<script>
    let table, table1;

    $(function () {
        table = $('.table-penjualan').DataTable({
            processing: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('penjualan.data') }}',
                type: 'GET',
                dataSrc: function(json) {
                    console.log(json); // Menampilkan response di console

                    // Jika json adalah objek, ambil array dari properti yang sesuai, misalnya 'data'
                    if (Array.isArray(json.data)) {
                        return json.data.filter(function(item) {
                            return item.total_item > 0; // Filter item dengan total_item > 0
                        });
                    }
                    // Jika json adalah array, langsung filter
                    return json.filter(function(item) {
                        return item.total_item > 0;
                    });
                }
            },
            columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'tanggal'},
                {data: 'kode_member'},
                {data: 'nama_pembeli'},
                {data: 'total_item'},
                {data: 'satuan'},
                {data: 'total_harga'},
                {data: 'diskon'},
                {data: 'bayar'},
                {data: 'hutang'},
                {data: 'tipe'},
                {data: 'kasir'},
                {data: 'status', render: function(data) {
                    if (data == 1) {
                        return '<span class="label label-success">Selesai</span>';
                    } else {
                        return '<span class="label label-default">Belum Lunas</span>';
                    }
                }},
                {data: 'aksi', searchable: false, sortable: false},
            ]
        });

        table1 = $('.table-detail').DataTable({
            processing: true,
            bSort: false,
            dom: 'Brt',
            columns: [
                {data: 'DT_RowIndex', searchable: false, sortable: false},
                {data: 'kode_produk'},
                // {data: 'nama_pembeli'},
                // {data: 'tipe'},
                {data: 'nama_produk'},
                {data: 'harga_jual'},
                {data: 'jumlah'},
                {data: 'subtotal'},
            ]
        })
    });

    function showDetail(url) {
        $('#modal-detail').modal('show');

        table1.ajax.url(url);
        table1.ajax.reload();
    }

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
</script>
@endpush
