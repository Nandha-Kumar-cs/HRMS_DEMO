<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EntityController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(Entity::query())
                ->addColumn('action', function ($e) {
                    return '
                        <a href="' . route('entities.edit', $e) . '" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>
                        <button class="btn btn-sm btn-outline-danger btn-delete-entity" data-id="' . $e->id . '"><i class="fa fa-trash"></i></button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('entities.index');
    }

    public function create()
    {
        return view('entities.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'logo'            => 'nullable|image|max:2048',
            'address'         => 'nullable|string',
            'city'            => 'nullable|string|max:100',
            'state'           => 'nullable|string|max:100',
            'pincode'         => 'nullable|string|max:20',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:255',
            'website'         => 'nullable|string|max:255',
            'signatory_name'  => 'nullable|string|max:255',
            'signatory_title' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('entities', 'public');
            $data['logo'] = basename($data['logo']);
        }

        Entity::create($data);
        return redirect()->route('entities.index')->with('success', 'Entity created successfully.');
    }

    public function edit(Entity $entity)
    {
        return view('entities.edit', compact('entity'));
    }

    public function update(Request $request, Entity $entity)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'logo'            => 'nullable|image|max:2048',
            'address'         => 'nullable|string',
            'city'            => 'nullable|string|max:100',
            'state'           => 'nullable|string|max:100',
            'pincode'         => 'nullable|string|max:20',
            'phone'           => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:255',
            'website'         => 'nullable|string|max:255',
            'signatory_name'  => 'nullable|string|max:255',
            'signatory_title' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('logo')) {
            if ($entity->logo) {
                @unlink(public_path('storage/entities/' . $entity->logo));
            }
            $data['logo'] = basename($request->file('logo')->store('entities', 'public'));
        } else {
            unset($data['logo']);
        }

        $entity->update($data);
        return redirect()->route('entities.index')->with('success', 'Entity updated successfully.');
    }

    public function destroy(Entity $entity)
    {
        if ($entity->logo) {
            @unlink(public_path('storage/entities/' . $entity->logo));
        }
        $entity->delete();
        return response()->json(['success' => true, 'message' => 'Entity deleted.']);
    }
}
