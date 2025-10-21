<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelayan;

class PelayanController extends Controller
{
    public function index()
    {
        return view('pelayan.index');
    }

    public function data()
    {
        $pelayan = Pelayan::orderBy('id', 'desc')->get();

        return datatables()
            ->of($pelayan)
            ->addIndexColumn()
            ->addColumn('aksi', function ($pelayan) {
                return '
                <div class="btn-group">
                    <button onclick="editForm(`' . route('pelayan.update', $pelayan->id) . '`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-pencil"></i></button>
                    <button onclick="deleteData(`' . route('pelayan.destroy', $pelayan->id) . '`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:pelayan,nama',
        ]);

        $pelayan = new Pelayan();
        $pelayan->nama = $request->nama;
        $pelayan->poin = 0; // Default poin
        $pelayan->save();

        return response()->json('Data berhasil disimpan', 200);
    }

    public function show($id)
    {
        $pelayan = Pelayan::findOrFail($id);
        return response()->json($pelayan);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:pelayan,nama,' . $id,
        ]);

        $pelayan = Pelayan::find($id);
        $pelayan->nama = $request->nama;
        $pelayan->update();

        return response()->json('Data berhasil disimpan', 200);
    }

    public function destroy($id)
    {
        $pelayan = Pelayan::find($id);
        $pelayan->delete();

        return response(null, 204);
    }
}
