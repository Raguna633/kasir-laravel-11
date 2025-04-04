<div class="modal fade" id="modal-produk" tabindex="-1" role="dialog" aria-labelledby="modal-produk">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Pilih Produk</h4>
            </div>
            <div class="modal-body">
                <table class="table table-striped table-bordered table-produk">
                    <thead>
                        <th width="5%">No</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Harga Jual Eceran</th>
                        <th>Harga Jual Borongan</th>
                        <th>Stok</th>
                        <th><i class="fa fa-cog"></i></th>
                    </thead>
                    <tbody>
                        @foreach ($produk as $key => $item)
                            <tr>
                                <td width="5%">{{ $key + 1 }}</td>
                                <td><span class="label label-success">{{ $item->kode_produk }}</span></td>
                                <td>{{ $item->nama_produk }}</td>
                                <td>
                                    @foreach ($item->produkSatuan as $satuan)
                                        <div>
                                            {{ $satuan->satuan }}: Rp. {{ $satuan->harga_jual_eceran }}
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach ($item->produkSatuan as $satuan)
                                        <div>
                                            {{ $satuan->satuan }}: Rp. {{ $satuan->harga_jual_borongan }}
                                        </div>
                                    @endforeach
                                </td>
                                <td>{{ $item->stok }}</td>
                                <td>
                                    @php
                                        $satuanDefault = $item->produkSatuan->firstWhere('satuan', 'pcs');
                                    @endphp
                                    <a href="#"
                                        class="btn btn-primary btn-xs btn-flat {{ $item->stok == 0 ? 'disabled' : '' }}"
                                        onclick="event.preventDefault(); {{ $item->stok == 0 || !$satuanDefault ? '' : "pilihProduk('$item->id_produk', '$item->kode_produk', '$satuanDefault->id_satuan')" }}">
                                        <i class="fa fa-check-circle"></i>
                                        Pilih
                                    </a>
                                </td>                                
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
