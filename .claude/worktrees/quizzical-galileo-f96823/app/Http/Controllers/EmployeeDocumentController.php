<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::orderBy('full_name')->get();
        $selected  = $request->employee ? Employee::with('documents')->find($request->employee) : null;
        $documents = $selected
            ? $selected->documents()->latest()->get()
            : EmployeeDocument::with('employee')->latest()->paginate(20);
        return view('employee-documents.index', compact('employees', 'selected', 'documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'document_type' => 'required|string',
            'document_name' => 'required|string|max:255',
            'file'          => 'required|file|max:10240',
            'remarks'       => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $path = $file->store('employee-docs/' . $request->employee_id, 'public');

        $doc = EmployeeDocument::create([
            'employee_id'   => $request->employee_id,
            'document_type' => $request->document_type,
            'document_name' => $request->document_name,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size'     => $file->getSize(),
            'mime_type'     => $file->getMimeType(),
            'remarks'       => $request->remarks,
        ]);

        $employee = Employee::find($request->employee_id);
        ActivityLog::record('created', 'Document',
            "Uploaded document \"{$doc->document_name}\" ({$doc->document_type})" .
            " for {$employee->full_name} ({$employee->employee_code})" .
            " — File: {$doc->original_name}"
        );

        return redirect()->back()->with('success', 'Document uploaded successfully.');
    }

    public function download(EmployeeDocument $document)
    {
        $path = Storage::disk('public')->path($document->file_path);
        if (!file_exists($path)) {
            return back()->with('error', 'File not found.');
        }
        return response()->download($path, $document->original_name ?? basename($document->file_path));
    }

    public function preview(EmployeeDocument $document)
    {
        $path = Storage::disk('public')->path($document->file_path);
        if (!file_exists($path)) {
            abort(404, 'File not found.');
        }
        $mime = $document->mime_type ?? mime_content_type($path);
        return response()->file($path, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . ($document->original_name ?? basename($document->file_path)) . '"',
        ]);
    }

    public function destroy(EmployeeDocument $document)
    {
        $document->load('employee');
        $docName  = $document->document_name;
        $docType  = $document->document_type;
        $empName  = $document->employee->full_name;
        $empCode  = $document->employee->employee_code;

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        ActivityLog::record('deleted', 'Document',
            "Deleted document \"{$docName}\" ({$docType})" .
            " for {$empName} ({$empCode})"
        );

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Document deleted.']);
        }
        return redirect()->back()->with('success', 'Document deleted.');
    }
}
