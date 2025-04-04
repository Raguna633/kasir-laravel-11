@extends('layouts.master')

@section('title')
    Transaksi Penjualan
@endsection

@push('css')
    <style>
        .tampil-bayar {
            font-size: 5em;
            text-align: center;
            height: 100px;
        }

        .tampil-terbilang {
            padding: 10px;
            background: #f0f0f0;
        }

        .table-penjualan tbody tr:last-child {
            display: none;
        }

        @media(max-width: 768px) {
            .tampil-bayar {
                font-size: 3em;
                height: 70px;
                padding-top: 5px;
            }
        }
    </style>
@endpush

@section('breadcrumb')
    @parent
    <li class="active">Transaksi Penjualan</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="box">
                <div class="box-body">

                    <form class="form-produk">
                        @csrf
                        <div class="form-group row">
                            <label for="kode_produk" class="col-lg-2">Kode Produk</label>
                            <div class="col-lg-5">
                                <div class="input-group">
                                    <input type="hidden" name="id_penjualan" id="id_penjualan" value="{{ $id_penjualan }}">
                                    <input type="hidden" name="id_produk" id="id_produk">
                                    <input type="text" class="form-control" disabled name="kode_produk" id="kode_produk">
                                    <span class="input-group-btn">
                                        <button
                                            @if ($penjualan->ishutang != 0) onclick="tampilPesanHutang()"
                                        @else
                                            onclick="tampilProduk()" @endif
                                            class="btn btn-info btn-flat" type="button"><i class="fa fa-arrow-right"></i> |
                                            <span class=" badge bg-secondary">alt+c</span></button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-lg-2 d-flex justify-content-center align-items-center text-center">
                                <span class="input-group-button">
                                    <button type="button" onclick="tampilDraft()" class="btn btn-info btn-flat">
                                        <i class="fa fa-list"></i> Draft Transaksi |
                                        <span class="badge bg-secondary">alt+d</span>
                                    </button>
                                </span>
                            </div>
                            <div class="col-lg-2 d-flex justify-content-center align-items-center text-center">
                                <span class="input-group-button">
                                    <a type="button" href="{{ route('transaksi.baru') }}" class="btn btn-info btn-flat">
                                        <i class="fa fa-plus"></i> Transaksi Baru |
                                        <span class="badge bg-secondary">ctrl+c</span>
                                    </a>
                                </span>
                            </div>

                        </div>
                    </form>

                    <table class="table table-stiped table-bordered table-penjualan">
                        <thead>
                            <th width="5%">No</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Harga</th>
                            <th width="15%">Jumlah</th>
                            <th width="15%">Stok Tersedia</th>
                            <th>Diskon</th>
                            <th>Subtotal</th>
                            <th width="15%"><i class="fa fa-cog"></i></th>
                        </thead>
                    </table>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="tampil-bayar bg-primary"></div>
                            <div class="tampil-terbilang"></div>
                        </div>
                        {{-- <div class="col-lg-8">
                            <div class="form-group row">
                                <label for="hutang" class="col-lg-2 control-label">Jumlah Belum Dibayar</label>
                                <div class="col-lg-8">
                                    <input type="text" id="hutang" name="hutang" class="form-control"
                                        value="{{ $penjualan->hutang ?? 0 }}" readonly>
                                </div>
                            </div>
                        </div> --}}
                        <div class="col-lg-4">
                            <form action="{{ route('transaksi.simpan') }}" class="form-penjualan" id="form-penjualan"
                                method="post">
                                @csrf
                                <input type="hidden" name="id_penjualan" id="id_penjualan"
                                    value="{{ $id_penjualan ?? '' }}">
                                <input type="hidden" name="total" id="total">
                                <input type="hidden" name="total_item" id="total_item">
                                <input type="hidden" name="bayar" id="bayar">
                                <input type="hidden" name="id_member" id="id_member"
                                    value="{{ $memberSelected->id_member }}">

                                <div class="form-group row">
                                    <label for="kode_member" class="col-lg-2 control-label">Nama Pembeli</label>
                                    <div class="col-lg-8">
                                        <div class="input-group">
                                            <input type="text" name="nama_pembeli" id="nama_pembeli" class="form-control"
                                                value="{{ $penjualan->nama_pembeli ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="tipe_pembeli" class="col-lg-2 control-label">Tipe Pembelian</label>
                                    <div class="col-lg-8">
                                        <select name="tipe_pembeli" id="tipe_pembeli" class="form-control"
                                            onchange="tipePembeli()">
                                            <option value="eceran"
                                                {{ $penjualan->tipe_pembeli == 'eceran' ? 'selected' : '' }}>Eceran</option>
                                            <option value="borongan"
                                                {{ $penjualan->tipe_pembeli == 'borongan' ? 'selected' : '' }}>Borongan
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="totalrp" class="col-lg-2 control-label">Total</label>
                                    <div class="col-lg-8">
                                        <input type="text" id="totalrp" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="kode_member" class="col-lg-2 control-label">Member</label>
                                    <div class="col-lg-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="kode_member"
                                                value="{{ $memberSelected->kode_member }}">
                                            <span class="input-group-btn">
                                                <button onclick="tampilMember()" class="btn btn-info btn-flat"
                                                    type="button"><i class="fa fa-arrow-right"></i> | <span
                                                        class=" badge bg-secondary">alt+v</span></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="diskon" class="col-lg-2 control-label">Diskon</label>
                                    <div class="col-lg-8">
                                        <input type="number" name="diskon" id="diskon" class="form-control"
                                            value="{{ !empty($memberSelected->id_member) ? $diskon : 0 }}" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="bayar" class="col-lg-2 control-label">Bayar</label>
                                    <div class="col-lg-8">
                                        <input type="text" id="bayarrp" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="diterima" class="col-lg-2 control-label">Diterima</label>
                                    <div class="col-lg-8">
                                        <input type="number" id="diterima" class="form-control" name="diterima"
                                            value="{{ $penjualan->diterima ?? 0 }}">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="kembali" class="col-lg-2 control-label">Kembali</label>
                                    <div class="col-lg-8">
                                        <input type="text" id="kembali" name="kembali" class="form-control"
                                            value="0" readonly>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary btn-sm btn-flat pull-right btn-simpan"><i
                            class="fa fa-floppy-o"></i> Simpan Transaksi | <span
                            class=" badge bg-secondary">shift+enter</span></button>
                    <button type="button" @if ($penjualan->ishutang != 0) disabled @endif
                        class="btn btn-warning btn-sm btn-flat pull-right btn-hutang" style="margin-right: 10px;">
                        <i class="fa fa-money"></i> Simpan sbg Hutang
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script>
        //script sortcut
        Mousetrap.bind('alt+c', function() {
            @if ($penjualan->ishutang != 0)
                tampilPesanHutang()
            @else
                tampilProduk()
            @endif
        });
        Mousetrap.bind('alt+d', function() {
            event.preventDefault();
            tampilDraft();
        });
        Mousetrap.bind('alt+v', function() {
            tampilMember();
        });
        document.addEventListener('keydown', function(event) {
            if (event.shiftKey && event.key === 'Enter') {
                event.preventDefault();
                document.getElementById('form-penjualan').submit();
            }
        });
        Mousetrap.bind('enter', function() {
            const diterimaInput = $('.form-penjualan').find('input#diterima');

            diterimaInput.focus().select();
        })
    </script>

    <script>
        $(document).ready(function() {
            const showLoading = () => $('#loading-indicator').show();
            const hideLoading = () => $('#loading-indicator').hide();

            const waitForElement = (selector, callback, interval = 1000, timeout = 5000) => {
                const startTime = Date.now();
                const checkExist = setInterval(() => {
                    if ($(selector).length > 0) {
                        clearInterval(checkExist);
                        callback();
                    } else if (Date.now() - startTime > timeout) {
                        clearInterval(checkExist);
                        console.error(`Element ${selector} not found within timeout.`);
                        alert('Data tidak dapat dimuat. Coba lagi nanti.');
                    }
                }, interval);
            };

            $('#modal-produk').on('shown.bs.modal', function() {
                waitForElement('input[type="search"]', () => {
                    $(this).find('input[type="search"]').focus();
                });
            });

            $(document).on('keydown', function(event) {
                if (event.key === 'Enter' && $('#modal-produk').hasClass('in')) {
                    const firstProductButton = $('.table-produk tbody tr').first().find('a.btn-primary');

                    if (firstProductButton.length > 0) {
                        showLoading();

                        const onclickValue = firstProductButton.attr('onclick');
                        const params = onclickValue.match(/'([^']+)'/g).map(param => param.replace(/'/g,
                            ''));

                        pilihProduk(params[0], params[1]);

                        waitForElement('.table-penjualan tbody tr input.quantity', function() {
                            hideLoading();
                            const quantityInput = $('.table-penjualan tbody tr').find(
                                'input.quantity');

                            if (quantityInput.length > 0) {
                                quantityInput.focus().select();

                                quantityInput.off('keydown').on('keydown', function(e) {
                                    if (e.key === 'Enter') {
                                        e.preventDefault();
                                        table.ajax.reload(() => {
                                            loadForm($('#diskon').val());
                                            hideLoading();
                                        });
                                    }
                                });
                            }
                        });
                    } else {
                        console.warn('Tidak ada produk yang tersedia untuk dipilih.');
                        alert('Produk tidak ditemukan. Pastikan data telah dimuat.');
                    }
                }
            });

            $(document).ajaxStart(showLoading).ajaxStop(hideLoading).ajaxError((event, jqxhr, settings,
                thrownError) => {
                hideLoading();
                console.error('Ajax error:', thrownError);
                alert('Terjadi masalah saat memuat data. Periksa koneksi internet Anda.');
            });

            $('body').append(
                '<div id="loading-indicator" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;text-align:center;color:#fff;font-size:20px;line-height:100vh;">Loading...</div>'
            );
        });
    </script>


    @includeIf('penjualan_detail.produk')
    @include('penjualan_detail.draft')
    @includeIf('penjualan_detail.member')
@endsection

@push('scripts')
    <script>
        let table, table2;

        document.querySelector('.btn-hutang').addEventListener('click', function() {
            if (confirm('Yakin ingin menyimpan transaksi ini sebagai hutang?')) {
                const formData = new FormData(document.querySelector('#form-penjualan'));

                formData.append('simpan_sebagai_hutang', true);

                fetch('{{ route('transaksi.simpan') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Transaksi disimpan sebagai hutang.');
                            window.location.href =
                                '{{ route('transaksi.selesai') }}';
                        } else {
                            alert(data.message || 'Terjadi kesalahan.');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });

        $('#total_item, #total_harga, #bayar, #diterima, #diskon').on('change', function() {
            updateDraftTransaksi();
        });

        $(function() {
            $('body').addClass('sidebar-collapse');

            table = $('.table-penjualan').DataTable({
                    processing: true,
                    autoWidth: false,
                    ajax: {
                        url: '{{ route('transaksi.data', $id_penjualan) }}',
                    },
                    columns: [{
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
                            data: 'produk_satuan'
                        },
                        {
                            data: 'jumlah'
                        },
                        {
                            data: 'max'
                        },
                        {
                            data: 'diskon'
                        },
                        {
                            data: 'subtotal'
                        },
                        {
                            data: 'aksi',
                            searchable: false,
                            sortable: false
                        },
                    ],
                    dom: 'Brt',
                    bSort: false,
                })
                .on('draw.dt', function() {
                    loadForm($('#diskon').val());
                    setTimeout(() => {
                        $('#diterima').trigger('input');
                    }, 300);
                });
            table2 = $('.table-produk').DataTable();

            $(document).on('input', '.quantity', function() {
                let id = $(this).data('id');
                let jumlah = parseInt($(this).val());

                if (jumlah < 1) {
                    $(this).val(1);
                    alert('Jumlah tidak boleh kurang dari 1');
                    return;
                }
                if (jumlah > 10000) {
                    $(this).val(10000);
                    alert('Jumlah tidak boleh lebih dari 10000');
                    return;
                }

                $.post(`{{ url('/transaksi') }}/${id}`, {
                        '_token': $('[name=csrf-token]').attr('content'),
                        '_method': 'put',
                        'jumlah': jumlah
                    })
                    .done(response => {
                        $(this).on('mouseout', function() {
                            table.ajax.reload(() => loadForm($('#diskon').val()));
                        });
                    })
                    .fail(errors => {
                        alert('Jumlah melebihi stok yang tersedia');
                        return;
                    });
            });

            $(document).on('input', '#diskon', function() {
                if ($(this).val() == "") {
                    $(this).val(0).select();
                }

                loadForm($(this).val());
            });

            $('#diterima').on('input', function() {
                if ($(this).val() == "") {
                    $(this).val(0).select();
                }

                loadForm($('#diskon').val(), $(this).val());
            }).focus(function() {
                $(this).select();
            });

            $('.btn-simpan').on('click', function() {
                $('.form-penjualan').submit();
            });
        });

        function tampilPesanHutang() {
            alert("Tidak bisa menambah item, transaksi adalah hutang!");
        }

        function tampilProduk() {
            $('#modal-produk').modal('show');
        }

        function tampilDraft() {
            $('#modal-draft-transaksi').modal('show');
        }

        function hideDraft() {
            $('#modal-draft-transaksi').modal('hide');
        }

        function hideProduk() {
            $('#modal-produk').modal('hide');
        }

        function pilihProduk(id, kode) {
            $('#id_produk').val(id);
            $('#kode_produk').val(kode);
            hideProduk();
            tambahProduk();
        }

        function tambahProduk() {
            $.post('{{ route('transaksi.store') }}', $('.form-produk').serialize())
                .done(response => {
                    $('#kode_produk').focus();
                    table.ajax.reload(() => loadForm($('#diskon').val()));
                })
                .fail(errors => {
                    alert('Tidak dapat menyimpan data');
                    return;
                });
        }

        function tipePembeli() {
            let tipe = $('#tipe_pembeli').val();
            let id_penjualan = $('#id_penjualan').val();

            $.post("{{ route('transaksi.updateTipePembeli') }}", {
                _token: "{{ csrf_token() }}",
                id_penjualan: id_penjualan,
                tipe_pembeli: tipe
            }, function(response) {
                $('.table-penjualan').DataTable().ajax.reload();
            });
        }


        function tampilMember() {
            $('#modal-member').modal('show');
        }

        function pilihMember(id, kode) {
            $('#id_member').val(id);
            $('#kode_member').val(kode);
            $('#diskon').val('{{ $diskon }}');
            loadForm($('#diskon').val());
            $('#diterima').val(0).focus().select();
            hideMember();
        }

        function hideMember() {
            $('#modal-member').modal('hide');
        }

        function deleteData(url) {
            if (confirm('Yakin ingin menghapus data terpilih?')) {
                $.post(url, {
                        '_token': $('[name=csrf-token]').attr('content'),
                        '_method': 'delete'
                    })
                    .done((response) => {
                        table.ajax.reload(() => loadForm($('#diskon').val()));
                    })
                    .fail((errors) => {
                        alert('Tidak dapat menghapus data');
                        return;
                    });
            }
        }

        $(document).on('change', '.produk-satuan', function() {
            let idPenjualanDetail = $(this).data('id');
            let idSatuan = $(this).val(); // Ambil nilai satuan yang dipilih

            // Kirim data ke server untuk memperbarui satuan dan harga jual
            $.post(`{{ url('/transaksi/update-satuan') }}/${idPenjualanDetail}`, {
                    '_token': $('[name=csrf-token]').attr('content'),
                    '_method': 'put',
                    'id_satuan': idSatuan
                })
                .done(response => {
                    table.ajax.reload(() => loadForm($('#diskon').val()));
                })
                .fail(errors => {
                    alert('Gagal memperbarui satuan');
                    return;
                });
        });

        function loadForm(diskon = 0, diterima = 0) {
            $('#total').val($('.total').text());
            $('#total_item').val($('.total_item').text());

            $.get(`{{ url('/transaksi/loadform') }}/${diskon}/${$('.total').text()}/${diterima}`)
                .done(response => {
                    $('#totalrp').val('Rp. ' + response.totalrp);
                    $('#bayarrp').val('Rp. ' + response.bayarrp);
                    $('#bayar').val(response.bayar);
                    $('#kembali').val('Rp.' + response.kembalirp);

                    $('.tampil-bayar').text(response.bayar_text); // Tampilkan teks dari controller
                    $('.tampil-terbilang').text(response.terbilang); // Tampilkan teks dari controller
                })
                .fail(errors => {
                    alert('Tidak dapat menampilkan data');
                    return;
                })
        }
    </script>
@endpush
